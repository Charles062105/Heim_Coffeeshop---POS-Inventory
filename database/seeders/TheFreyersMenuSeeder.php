<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductSize;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

/**
 * TheFreyersMenuSeeder
 *
 * Seeds all menu items for The Fryers according to original menus:
 *   - Chicken Wings (8 items, regular prices)
 *   - Spicy Korean Wings (8 items, spicy menu)
 *   - Buldak Series (4 items)
 *   - Chicken Burgers (8 items)
 *   - Quesadillas (2 items)
 *   - Desserts & Bakery (7 items including dine-in brownies ala mode)
 *   - Drinks & Sodas (14 items including Pink Guava & Blueberry Lemonade)
 *   - Chicken Flavors & Cheese Drip Add-ons
 */
class TheFreyersMenuSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Supplier ────────────────────────────────────────────────────────
        $supplier = Supplier::firstOrCreate(
            ['name' => 'The Fryers Food Logistics'],
            ['contact' => '09181234567', 'address' => 'Quezon City, Philippines', 'status' => 'active']
        );

        // ─── Ingredients & Inventory ─────────────────────────────────────────
        $ingredientsList = [
            // Wings & Meats
            ['name' => 'Chicken Wing',               'unit' => 'pc', 'minimum_stock' => 100, 'stock' => 1500],
            ['name' => 'Burger Patty',               'unit' => 'pc', 'minimum_stock' => 50,  'stock' => 500],
            ['name' => 'Burger Bun',                 'unit' => 'pc', 'minimum_stock' => 50,  'stock' => 500],
            ['name' => 'Cheese Slice',               'unit' => 'pc', 'minimum_stock' => 50,  'stock' => 500],
            ['name' => 'Burger Mayo',                'unit' => 'ml', 'minimum_stock' => 500, 'stock' => 5000],
            ['name' => 'Hashbrown',                  'unit' => 'pc', 'minimum_stock' => 50,  'stock' => 400],
            ['name' => 'French Fries',               'unit' => 'g',  'minimum_stock' => 1000, 'stock' => 15000],
            ['name' => 'Quesadilla Tortilla',        'unit' => 'pc', 'minimum_stock' => 50,  'stock' => 500],
            ['name' => 'Rice Portion',               'unit' => 'g',  'minimum_stock' => 1000, 'stock' => 20000],
            // Sauces & Seasonings
            ['name' => 'Korean Spice Mix',           'unit' => 'g',  'minimum_stock' => 200, 'stock' => 3000],
            ['name' => 'Buldak Spicy Cheese Sauce',  'unit' => 'ml', 'minimum_stock' => 300, 'stock' => 3000],
            ['name' => 'Buldak Extra Spicy Sauce',   'unit' => 'ml', 'minimum_stock' => 300, 'stock' => 3000],
            ['name' => 'Buldak Carbonara Sauce',     'unit' => 'ml', 'minimum_stock' => 300, 'stock' => 3000],
            ['name' => 'Buffalo Sauce',              'unit' => 'ml', 'minimum_stock' => 300, 'stock' => 4000],
            ['name' => 'Garlic Parmesan Sauce',      'unit' => 'ml', 'minimum_stock' => 300, 'stock' => 4000],
            ['name' => 'Honey Glaze Sauce',          'unit' => 'ml', 'minimum_stock' => 300, 'stock' => 4000],
            ['name' => 'Salted Egg Sauce',           'unit' => 'ml', 'minimum_stock' => 300, 'stock' => 3000],
            ['name' => 'Sour Cream Dip',             'unit' => 'ml', 'minimum_stock' => 300, 'stock' => 3000],
            ['name' => 'Cheese Sauce Dip',           'unit' => 'ml', 'minimum_stock' => 300, 'stock' => 4000],
            ['name' => 'Cheesy Buffalo Drip',        'unit' => 'ml', 'minimum_stock' => 200, 'stock' => 2000],
            ['name' => 'Double Cheese Drip',         'unit' => 'ml', 'minimum_stock' => 200, 'stock' => 2000],
            // Bakery & Desserts
            ['name' => 'Brownie Slice',              'unit' => 'pc', 'minimum_stock' => 30,  'stock' => 300],
            ['name' => 'Ice Cream Scoop',            'unit' => 'pc', 'minimum_stock' => 20,  'stock' => 200],
            ['name' => 'Mini Egg Tart',              'unit' => 'pc', 'minimum_stock' => 30,  'stock' => 300],
            ['name' => 'Matcha Cheese Tart Portion', 'unit' => 'pc', 'minimum_stock' => 20,  'stock' => 200],
            ['name' => 'Guava Puree',                'unit' => 'ml', 'minimum_stock' => 300, 'stock' => 3000],
            ['name' => 'Blueberry Syrup',            'unit' => 'ml', 'minimum_stock' => 300, 'stock' => 3000],
            ['name' => 'Lemon Juice',                'unit' => 'ml', 'minimum_stock' => 300, 'stock' => 3000],
            ['name' => 'Popping Bobba',              'unit' => 'g',  'minimum_stock' => 200, 'stock' => 2000],
            // Packaged Drinks & Sodas
            ['name' => 'Coke 1.75L Bottle',          'unit' => 'pc', 'minimum_stock' => 20,  'stock' => 100],
            ['name' => 'Coke 1.5L Bottle',           'unit' => 'pc', 'minimum_stock' => 20,  'stock' => 100],
            ['name' => 'Mountain Dew 1.5L Bottle',   'unit' => 'pc', 'minimum_stock' => 20,  'stock' => 100],
            ['name' => 'Coke Can 330ml',             'unit' => 'pc', 'minimum_stock' => 50,  'stock' => 300],
            ['name' => 'Coke Zero Can 330ml',        'unit' => 'pc', 'minimum_stock' => 30,  'stock' => 200],
            ['name' => 'Sprite Can 330ml',           'unit' => 'pc', 'minimum_stock' => 50,  'stock' => 300],
            ['name' => 'Royal Can 330ml',            'unit' => 'pc', 'minimum_stock' => 50,  'stock' => 300],
            ['name' => 'Mountain Dew Can 330ml',     'unit' => 'pc', 'minimum_stock' => 50,  'stock' => 300],
            ['name' => 'Sola Lemon Can 330ml',       'unit' => 'pc', 'minimum_stock' => 30,  'stock' => 200],
            ['name' => 'Sola Raspberry Can 330ml',   'unit' => 'pc', 'minimum_stock' => 30,  'stock' => 200],
            ['name' => 'Sola Lemon Bottle',          'unit' => 'pc', 'minimum_stock' => 20,  'stock' => 150],
            ['name' => 'Sola Raspberry Bottle',      'unit' => 'pc', 'minimum_stock' => 20,  'stock' => 150],
            ['name' => 'Bottled Water 500ml',        'unit' => 'pc', 'minimum_stock' => 50,  'stock' => 500],
        ];

        $ingredients = [];
        foreach ($ingredientsList as $data) {
            $stock = $data['stock'];
            unset($data['stock']);
            $ingredient = Ingredient::firstOrCreate(
                ['name' => $data['name']],
                array_merge($data, ['status' => 'active'])
            );
            Inventory::firstOrCreate(
                ['ingredient_id' => $ingredient->id],
                ['current_stock' => $stock]
            );
            $ingredients[$ingredient->name] = $ingredient;
        }

        // ─── Recipe Builder Helper ───────────────────────────────────────────
        $makeRecipe = function (ProductSize $size, string $label, array $ingredientList) use ($ingredients) {
            $recipe = Recipe::firstOrCreate(
                ['product_size_id' => $size->id],
                ['name' => $label, 'status' => 'active']
            );
            foreach ($ingredientList as [$ingName, $qty]) {
                if (isset($ingredients[$ingName])) {
                    RecipeIngredient::firstOrCreate(
                        ['recipe_id' => $recipe->id, 'ingredient_id' => $ingredients[$ingName]->id],
                        ['quantity' => $qty]
                    );
                }
            }
        };

        $addSimpleProduct = function (string $name, int|float $price, Category $category, string $sizeName, array $recipe) use ($makeRecipe) {
            $product = Product::firstOrCreate(
                ['name' => $name],
                ['category_id' => $category->id, 'status' => 'active']
            );
            $size = ProductSize::firstOrCreate(
                ['product_id' => $product->id, 'size_name' => $sizeName],
                ['price' => $price, 'status' => 'active']
            );
            $makeRecipe($size, "{$name} {$sizeName}", $recipe);
        };

        // ─── Categories ─────────────────────────────────────────────────────
        $catWings = Category::firstOrCreate(['name' => 'Chicken Wings'], ['status' => 'active']);
        $catSpicyWings = Category::firstOrCreate(['name' => 'Spicy Korean Wings'], ['status' => 'active']);
        $catBuldak = Category::firstOrCreate(['name' => 'Buldak Series'], ['status' => 'active']);
        $catBurgers = Category::firstOrCreate(['name' => 'Chicken Burgers'], ['status' => 'active']);
        $catQuesadillas = Category::firstOrCreate(['name' => 'Quesadillas'], ['status' => 'active']);
        $catDesserts = Category::firstOrCreate(['name' => 'Desserts'], ['status' => 'active']);
        $catDrinks = Category::firstOrCreate(['name' => 'Drinks'], ['status' => 'active']);

        // ─── 1. CHICKEN WINGS (Classic Menu - Image 5) ──────────────────────
        $wingsMenu = [
            ['Lone',                      189,  [['Chicken Wing', 4],  ['Rice Portion', 150]]],
            ['Wingman',                   259,  [['Chicken Wing', 6]]],
            ['Wingstreak',                359,  [['Chicken Wing', 6],  ['French Fries', 150]]],
            ['Trio',                      529,  [['Chicken Wing', 12]]],
            ['Squad',                     699,  [['Chicken Wing', 16]]],
            ['Winner Chicken Dinner',    1199,  [['Chicken Wing', 30]]],
            ['Chicken Wings & Hashbrown', 259,  [['Chicken Wing', 4],  ['Hashbrown', 2]]],
            ['Chicken Wings & Fries',     259,  [['Chicken Wing', 4],  ['French Fries', 150]]],
        ];

        foreach ($wingsMenu as [$name, $price, $recipe]) {
            $addSimpleProduct($name, $price, $catWings, 'Regular', $recipe);
        }

        // ─── 2. SPICY KOREAN WINGS (Spicy Menu - Image 1) ────────────────────
        $spicyWingsMenu = [
            ['Spicy Korean Lone',                  209,  [['Chicken Wing', 4],  ['Rice Portion', 150], ['Korean Spice Mix', 15]]],
            ['Spicy Korean Wingman',               289,  [['Chicken Wing', 6],  ['Korean Spice Mix', 20]]],
            ['Spicy Korean Wingstreak',            389,  [['Chicken Wing', 6],  ['French Fries', 150], ['Korean Spice Mix', 20]]],
            ['Spicy Korean Trio',                  589,  [['Chicken Wing', 12], ['Korean Spice Mix', 35]]],
            ['Spicy Korean Squad',                 779,  [['Chicken Wing', 16], ['Korean Spice Mix', 45]]],
            ['Spicy Korean Winner Chicken Dinner', 1349, [['Chicken Wing', 30], ['Korean Spice Mix', 80]]],
            ['Spicy Korean Chicken Wings & Hashbrown', 279, [['Chicken Wing', 4], ['Hashbrown', 2], ['Korean Spice Mix', 15]]],
            ['Spicy Korean Chicken Wings & Fries',     279, [['Chicken Wing', 4], ['French Fries', 150], ['Korean Spice Mix', 15]]],
        ];

        foreach ($spicyWingsMenu as [$name, $price, $recipe]) {
            $addSimpleProduct($name, $price, $catSpicyWings, 'Regular', $recipe);
        }

        // ─── 3. BULDAK SERIES (Image 2) ──────────────────────────────────────
        $buldakMenu = [
            ['Buldak Spicy Cheese (yellow)',          189, [['Chicken Wing', 4], ['Buldak Spicy Cheese Sauce', 30]]],
            ['Buldak Extra Spicy (black)',           189, [['Chicken Wing', 4], ['Buldak Extra Spicy Sauce', 30]]],
            ['Buldak Spicy Carbonara (pink)',         189, [['Chicken Wing', 4], ['Buldak Carbonara Sauce', 30]]],
            ['Buldak with 4pcs Wings (yellow/black/pink)', 299, [['Chicken Wing', 4], ['Buldak Spicy Cheese Sauce', 30]]],
        ];

        foreach ($buldakMenu as [$name, $price, $recipe]) {
            $addSimpleProduct($name, $price, $catBuldak, 'Regular', $recipe);
        }

        // ─── 4. CHICKEN BURGERS (Image 3) ────────────────────────────────────
        $burgersMenu = [
            ['Chicken Burger',                                95,  [['Burger Patty', 1], ['Burger Bun', 1], ['Burger Mayo', 20]]],
            ['Chicken Burger Spicy',                          95,  [['Burger Patty', 1], ['Burger Bun', 1], ['Burger Mayo', 20], ['Korean Spice Mix', 5]]],
            ['Chicken Burger Spicy with Cheese',             115,  [['Burger Patty', 1], ['Burger Bun', 1], ['Cheese Slice', 1], ['Burger Mayo', 20], ['Korean Spice Mix', 5]]],
            ['Chicken Burger with Cheese',                   115,  [['Burger Patty', 1], ['Burger Bun', 1], ['Cheese Slice', 1], ['Burger Mayo', 20]]],
            ['Chicken Burger Plain Mayo',                     95,  [['Burger Patty', 1], ['Burger Bun', 1], ['Burger Mayo', 30]]],
            ['Chicken Burger Double Patty',                  205,  [['Burger Patty', 2], ['Burger Bun', 1], ['Burger Mayo', 25]]],
            ['Chicken Burger Double Patty & Cheese',         250,  [['Burger Patty', 2], ['Burger Bun', 1], ['Cheese Slice', 2], ['Burger Mayo', 25]]],
            ['Chicken Burger Double Patty, Cheese & Hashbrown', 299, [['Burger Patty', 2], ['Burger Bun', 1], ['Cheese Slice', 2], ['Hashbrown', 1], ['Burger Mayo', 25]]],
        ];

        foreach ($burgersMenu as [$name, $price, $recipe]) {
            $addSimpleProduct($name, $price, $catBurgers, 'Regular', $recipe);
        }

        // ─── 5. QUESADILLAS (Image 3) ────────────────────────────────────────
        $quesadillasMenu = [
            ['Triple Cheese Quesadilla', 115, [['Quesadilla Tortilla', 2], ['Cheese Slice', 3], ['Burger Mayo', 15]]],
            ['Chicken Quesadilla',       125, [['Quesadilla Tortilla', 2], ['Chicken Wing', 2], ['Cheese Slice', 2], ['Burger Mayo', 15]]],
        ];

        foreach ($quesadillasMenu as [$name, $price, $recipe]) {
            $addSimpleProduct($name, $price, $catQuesadillas, 'Regular', $recipe);
        }

        // ─── 6. DESSERTS (Images 3 & 5) ──────────────────────────────────────
        $dessertsMenu = [
            ['OG Fudgy Brownie',                        55,  [['Brownie Slice', 1]]],
            ['Matcha Brownie',                          65,  [['Brownie Slice', 1]]],
            ['Strawberry Brownie',                      65,  [['Brownie Slice', 1]]],
            ['Mini Dimsum Egg Tart',                    40,  [['Mini Egg Tart', 1]]],
            ['Matcha Cheese Tart',                      65,  [['Matcha Cheese Tart Portion', 1]]],
            ['OG Brownie Ala Mode',                    120,  [['Brownie Slice', 1], ['Ice Cream Scoop', 1]]],
            ['Matcha or Strawberry Brownie Ala Mode',  170,  [['Brownie Slice', 1], ['Ice Cream Scoop', 1]]],
        ];

        foreach ($dessertsMenu as [$name, $price, $recipe]) {
            $addSimpleProduct($name, $price, $catDesserts, 'Regular', $recipe);
        }

        // ─── 7. DRINKS (Images 2 & 5) ────────────────────────────────────────
        $drinksMenu = [
            ['Pink Guava',                              90,  'Iced 16oz',   [['Guava Puree', 80]]],
            ['Blueberry Lemonade (with popping bobba)', 90,  'Iced 16oz',   [['Blueberry Syrup', 30], ['Lemon Juice', 25], ['Popping Bobba', 25]]],
            ['Coke 1.75',                              120,  'Bottle 1.75L', [['Coke 1.75L Bottle', 1]]],
            ['Coke 1.5',                               100,  'Bottle 1.5L', [['Coke 1.5L Bottle', 1]]],
            ['Mountain Dew 1.5',                       100,  'Bottle 1.5L', [['Mountain Dew 1.5L Bottle', 1]]],
            ['Coke in can',                             55,  'Can',         [['Coke Can 330ml', 1]]],
            ['Coke Zero in can',                        55,  'Can',         [['Coke Zero Can 330ml', 1]]],
            ['Sprite in can',                           55,  'Can',         [['Sprite Can 330ml', 1]]],
            ['Royal in can',                            55,  'Can',         [['Royal Can 330ml', 1]]],
            ['Mountain Dew in can',                     55,  'Can',         [['Mountain Dew Can 330ml', 1]]],
            ['Sola Lemon in can',                       80,  'Can',         [['Sola Lemon Can 330ml', 1]]],
            ['Sola Raspberry in can',                   80,  'Can',         [['Sola Raspberry Can 330ml', 1]]],
            ['Sola Lemon in bottle',                   120,  'Bottle',      [['Sola Lemon Bottle', 1]]],
            ['Sola Raspberry in bottle',               120,  'Bottle',      [['Sola Raspberry Bottle', 1]]],
            ['Mineral Water',                           25,  'Bottle 500ml', [['Bottled Water 500ml', 1]]],
        ];

        foreach ($drinksMenu as [$name, $price, $sizeLabel, $recipe]) {
            $addSimpleProduct($name, $price, $catDrinks, $sizeLabel, $recipe);
        }

        // ─── 8. ADD-ONS (Rice, Flavors & Cheese Drips) ──────────────────────
        ProductAddon::firstOrCreate(['name' => 'Extra Rice'], ['price' => 20, 'status' => 'active']);
        ProductAddon::firstOrCreate(['name' => 'Cheesy Buffalo (Creamy Cheese Drip)'], ['price' => 50, 'status' => 'active']);
        ProductAddon::firstOrCreate(['name' => 'Double Cheese (Creamy Cheese Drip)'], ['price' => 50, 'status' => 'active']);

        // Chicken Flavors (free selection for wings)
        ProductAddon::firstOrCreate(['name' => 'Flavor: Classic (Plain)'], ['price' => 0, 'status' => 'active']);
        ProductAddon::firstOrCreate(['name' => 'Flavor: Buffalo Wings'], ['price' => 0, 'status' => 'active']);
        ProductAddon::firstOrCreate(['name' => 'Flavor: Sour Cream'], ['price' => 0, 'status' => 'active']);
        ProductAddon::firstOrCreate(['name' => 'Flavor: Salted Egg'], ['price' => 0, 'status' => 'active']);
        ProductAddon::firstOrCreate(['name' => 'Flavor: Cheese'], ['price' => 0, 'status' => 'active']);
        ProductAddon::firstOrCreate(['name' => 'Flavor: Honey Glazed'], ['price' => 0, 'status' => 'active']);
        ProductAddon::firstOrCreate(['name' => 'Flavor: Garlic Parmesan'], ['price' => 0, 'status' => 'active']);

        $this->command->info('TheFreyersMenuSeeder: Complete The Fryers menu items seeded successfully.');
    }
}
