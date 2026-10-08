<?php

namespace App\Http\Controllers;

use App\Models\AddonIngredient;
use App\Models\Ingredient;
use App\Models\Inventory;
use App\Models\Supplier;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IngredientController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive'],
            'stock_status' => ['nullable', 'in:low_stock,out_of_stock'],
        ]);
        $query = Ingredient::with(['inventory', 'supplier'])->orderBy('name');

        if ($search = $filters['search'] ?? null) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }
        if ($stockStatus = $filters['stock_status'] ?? null) {
            if ($stockStatus === 'out_of_stock') {
                $query->where(fn ($q) => $q
                    ->whereDoesntHave('inventory')
                    ->orWhereHas('inventory', fn ($inventory) => $inventory->where('current_stock', '<=', 0)));
            } elseif ($stockStatus === 'low_stock') {
                $query->whereHas('inventory', fn ($q) => $q
                    ->whereRaw('inventories.current_stock <= CASE WHEN ingredients.reorder_level > 0 THEN ingredients.reorder_level ELSE ingredients.minimum_stock END')
                    ->where('inventories.current_stock', '>', 0));
            }
        }

        $ingredients = $query->paginate(25)->withQueryString();

        return view('ingredients.index', compact('ingredients'));
    }

    public function create()
    {
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();

        return view('ingredients.create', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150|unique:ingredients,name',
            'unit' => 'required|in:ml,g,kg,L,pc,tbsp,tsp',
            'minimum_stock' => 'required|numeric|decimal:0,3|min:0',
            'reorder_level' => 'nullable|numeric|decimal:0,3|min:0',
            'cost' => 'nullable|numeric|decimal:0,2|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'expiration_date' => 'nullable|date',
            'status' => 'required|in:active,inactive',
        ]);

        $ingredient = Ingredient::create($data);

        // Create inventory record
        Inventory::create(['ingredient_id' => $ingredient->id, 'current_stock' => 0]);

        AuditService::logFromUser($request->user(), 'created_ingredient', 'Inventory', [
            'ingredient' => $ingredient->name, 'unit' => $ingredient->unit,
        ], $ingredient);

        return redirect()->route('ingredients.index')->with('success', "Ingredient \"{$ingredient->name}\" added.");
    }

    public function edit(Ingredient $ingredient)
    {
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();

        return view('ingredients.edit', compact('ingredient', 'suppliers'));
    }

    public function update(Request $request, Ingredient $ingredient)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150|unique:ingredients,name,'.$ingredient->id,
            'unit' => 'required|in:ml,g,kg,L,pc,tbsp,tsp',
            'minimum_stock' => 'required|numeric|decimal:0,3|min:0',
            'reorder_level' => 'nullable|numeric|decimal:0,3|min:0',
            'cost' => 'nullable|numeric|decimal:0,2|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'expiration_date' => 'nullable|date',
            'status' => 'required|in:active,inactive',
        ]);

        if ($data['unit'] !== $ingredient->unit && (
            $ingredient->recipeIngredients()->exists()
            || AddonIngredient::where('ingredient_id', $ingredient->id)->exists()
            || $ingredient->inventoryTransactions()->exists()
        )) {
            throw ValidationException::withMessages([
                'unit' => 'The measurement unit cannot be changed after this ingredient has been used in a recipe or inventory movement. Create a new ingredient and adjust stock through the inventory ledger instead.',
            ]);
        }

        $ingredient->update($data);

        AuditService::logFromUser($request->user(), 'updated_ingredient', 'Inventory', [
            'ingredient' => $ingredient->name,
        ], $ingredient);

        return redirect()->route('ingredients.index')->with('success', 'Ingredient updated.');
    }

    public function destroy(Ingredient $ingredient)
    {
        $inUse = $ingredient->recipeIngredients()->exists()
            || AddonIngredient::where('ingredient_id', $ingredient->id)->exists()
            || $ingredient->inventoryTransactions()->exists()
            || $ingredient->getCurrentStock() > 0;
        if ($inUse) {
            return back()->with('error', 'Cannot delete an ingredient with recipe usage, stock, or inventory history. Archive it instead.');
        }
        AuditService::logFromUser(request()->user(), 'deleted_ingredient', 'Inventory', ['ingredient' => $ingredient->name]);
        $ingredient->delete();

        return redirect()->route('ingredients.index')->with('success', 'Ingredient deleted.');
    }

    public function toggle(Ingredient $ingredient)
    {
        $ingredient->update(['status' => $ingredient->status === 'active' ? 'inactive' : 'active']);

        $status = $ingredient->status === 'active' ? 'unarchived' : 'archived';

        return back()->with('success', "Ingredient \"{$ingredient->name}\" is now {$status}.");
    }
}
