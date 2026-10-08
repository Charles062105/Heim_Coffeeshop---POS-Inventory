<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductSize;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RecipeController extends Controller
{
    public function index()
    {
        $products = Product::with([
            'category',
            'sizes' => fn ($q) => $q->with('recipe.recipeIngredients.ingredient'),
        ])->orderBy('name')->get();

        // Load all active addons with their ingredient mappings
        $addons = ProductAddon::with('addonIngredients.ingredient', 'addonIngredients.replacesIngredient')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('recipes.index', compact('products', 'addons'));
    }

    public function edit(ProductSize $productSize)
    {
        $productSize->load(['product.category', 'recipe.recipeIngredients.ingredient']);
        $recipe = $productSize->recipe ?? null;
        $existingIngredientIds = $recipe?->recipeIngredients->pluck('ingredient_id') ?? collect();
        $ingredients = Ingredient::where('status', 'active')
            ->orWhereIn('id', $existingIngredientIds)
            ->orderBy('name')
            ->get();

        return view('recipes.edit', compact('productSize', 'recipe', 'ingredients'));
    }

    public function update(Request $request, ProductSize $productSize)
    {
        $existingIngredientIds = $productSize->recipe?->recipeIngredients()->pluck('ingredient_id') ?? collect();
        $request->validate([
            'name' => 'nullable|string|max:150',
            'ingredients' => 'array',
            'ingredients.*.ingredient_id' => [
                'required',
                'distinct',
                Rule::exists('ingredients', 'id')->where(fn ($query) => $query
                    ->where('status', 'active')
                    ->orWhereIn('id', $existingIngredientIds)),
            ],
            'ingredients.*.quantity' => 'required|numeric|decimal:0,3|min:0.001',
        ]);

        DB::transaction(function () use ($request, $productSize) {
            $recipe = Recipe::firstOrCreate(
                ['product_size_id' => $productSize->id],
                ['name' => $request->name ?: "{$productSize->product->name} {$productSize->size_name}", 'status' => 'active']
            );

            if ($request->name) {
                $recipe->update(['name' => $request->name]);
            }

            $recipe->recipeIngredients()->delete();

            foreach ($request->ingredients ?? [] as $ing) {
                if (empty($ing['ingredient_id']) || empty($ing['quantity'])) {
                    continue;
                }
                RecipeIngredient::create([
                    'recipe_id' => $recipe->id,
                    'ingredient_id' => $ing['ingredient_id'],
                    'quantity' => $ing['quantity'],
                ]);
            }

            AuditService::logFromUser($request->user(), 'updated_recipe', 'Recipes', [
                'product' => $productSize->product->name,
                'size' => $productSize->size_name,
                'recipe_id' => $recipe->id,
                'ingredients_count' => count($request->ingredients ?? []),
            ], $recipe);
        });

        return redirect()->route('recipes.index')->with('success', 'Recipe updated successfully.');
    }
}
