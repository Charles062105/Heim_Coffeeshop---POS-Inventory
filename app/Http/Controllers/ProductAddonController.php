<?php

namespace App\Http\Controllers;

use App\Models\ProductAddon;
use App\Models\Ingredient;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductAddonController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $addons = ProductAddon::query()
            ->with('addonIngredients.ingredient', 'addonIngredients.replacesIngredient')
            ->withCount('orderItemAddons')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $ingredients = Ingredient::query()->orderBy('name')->get();

        return view('addons.index', compact('addons', 'ingredients'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('product_addons', 'name')],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
        ]);

        $addon = ProductAddon::create([
            ...$validated,
            'status' => 'active',
        ]);

        AuditService::logFromUser($request->user(), 'created_addon', 'Menu', [
            'addon' => $addon->name,
            'price' => (float) $addon->price,
        ], $addon);

        return redirect()->route('addons.index')->with('success', "Add-on \"{$addon->name}\" added.");
    }

    public function update(Request $request, ProductAddon $addon)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('product_addons', 'name')->ignore($addon->id)],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
        ]);

        $addon->update($validated);

        AuditService::logFromUser($request->user(), 'updated_addon', 'Menu', [
            'addon' => $addon->name,
            'price' => (float) $addon->price,
        ], $addon);

        return redirect()->route('addons.index')->with('success', "Add-on \"{$addon->name}\" updated.");
    }

    public function toggle(ProductAddon $addon)
    {
        $addon->update(['status' => $addon->status === 'active' ? 'inactive' : 'active']);
        $status = $addon->status === 'active' ? 'unarchived' : 'archived';

        AuditService::logFromUser(request()->user(), 'toggled_addon_status', 'Menu', [
            'addon' => $addon->name,
            'status' => $addon->status,
        ], $addon);

        return back()->with('success', "Add-on \"{$addon->name}\" {$status}.");
    }

    public function updateIngredients(Request $request, ProductAddon $addon)
    {
        $existingIngredientIds = $addon->addonIngredients()
            ->get(['ingredient_id', 'replaces_ingredient_id'])
            ->flatMap(fn ($mapping) => [$mapping->ingredient_id, $mapping->replaces_ingredient_id])
            ->filter()
            ->unique()
            ->values();
        $activeOrExistingIngredient = fn () => Rule::exists('ingredients', 'id')
            ->where(fn ($query) => $query->where('status', 'active')->orWhereIn('id', $existingIngredientIds));

        $validated = $request->validate([
            'ingredients' => ['nullable', 'array', 'max:30'],
            'ingredients.*.ingredient_id' => ['required', 'integer', 'distinct', $activeOrExistingIngredient()],
            'ingredients.*.quantity' => ['required', 'numeric', 'decimal:0,3', 'min:0.001'],
            'ingredients.*.mode' => ['required', Rule::in(['additive', 'substitute'])],
            'ingredients.*.replaces_ingredient_id' => ['nullable', 'integer', $activeOrExistingIngredient()],
        ]);

        $mappings = $validated['ingredients'] ?? [];
        $ingredientIds = collect($mappings)
            ->flatMap(fn ($mapping) => array_filter([
                $mapping['ingredient_id'] ?? null,
                $mapping['replaces_ingredient_id'] ?? null,
            ]))
            ->unique()
            ->all();
        $ingredientUnits = Ingredient::query()
            ->whereIn('id', $ingredientIds)
            ->pluck('unit', 'id');
        $replacementTargets = [];
        foreach ($mappings as $index => $mapping) {
            $replacesIngredientId = $mapping['replaces_ingredient_id'] ?? null;
            if ($mapping['mode'] === 'substitute' && ! $replacesIngredientId) {
                throw ValidationException::withMessages([
                    "ingredients.$index.replaces_ingredient_id" => 'Choose the base-recipe ingredient this add-on replaces.',
                ]);
            }
            if ($mapping['mode'] === 'additive' && $replacesIngredientId) {
                throw ValidationException::withMessages([
                    "ingredients.$index.replaces_ingredient_id" => 'Only substitute mappings can replace a base-recipe ingredient.',
                ]);
            }
            if ($replacesIngredientId && (int) $replacesIngredientId === (int) $mapping['ingredient_id']) {
                throw ValidationException::withMessages([
                    "ingredients.$index.replaces_ingredient_id" => 'A substitute must use a different ingredient from the one it replaces.',
                ]);
            }
            if ($replacesIngredientId
                && strcasecmp(trim((string) $ingredientUnits[$mapping['ingredient_id']]), trim((string) $ingredientUnits[$replacesIngredientId])) !== 0) {
                throw ValidationException::withMessages([
                    "ingredients.$index.replaces_ingredient_id" => 'A substitute and the ingredient it replaces must use the same stock unit.',
                ]);
            }
            if ($replacesIngredientId && in_array((int) $replacesIngredientId, $replacementTargets, true)) {
                throw ValidationException::withMessages([
                    "ingredients.$index.replaces_ingredient_id" => 'An add-on can only replace each base-recipe ingredient once.',
                ]);
            }
            if ($replacesIngredientId) {
                $replacementTargets[] = (int) $replacesIngredientId;
            }
        }

        DB::transaction(function () use ($request, $addon, $validated) {
            $addon->addonIngredients()->delete();
            foreach ($validated['ingredients'] ?? [] as $mapping) {
                $addon->addonIngredients()->create([
                    'ingredient_id' => $mapping['ingredient_id'],
                    'quantity' => $mapping['quantity'],
                    'replaces_ingredient_id' => $mapping['mode'] === 'substitute'
                        ? $mapping['replaces_ingredient_id']
                        : null,
                ]);
            }

            AuditService::logFromUser($request->user(), 'updated_addon_consumption', 'Menu', [
                'addon' => $addon->name,
                'ingredient_mappings' => count($validated['ingredients'] ?? []),
            ], $addon);
        });

        return redirect()->route('addons.index')->with('success', "Ingredient consumption for \"{$addon->name}\" updated.");
    }
}
