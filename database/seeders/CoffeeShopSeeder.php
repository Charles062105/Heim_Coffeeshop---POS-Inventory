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

class CoffeeShopSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Suppliers ───────────────────────────────────────────────────────
        $supplier = Supplier::firstOrCreate(
            ['name' => 'Metro Coffee Supplies'],
            ['contact' => '09171234567', 'address' => 'Manila, Philippines', 'status' => 'active']
        );

        // ─── Ingredients for Beverages ───────────────────────────────────────
        $ingredientData = [
            // Coffee base
            ['name' => 'Espresso Shot',        'unit' => 'ml',  'minimum_stock' => 500,  'stock' => 5000],
            ['name' => 'Cold Brew Concentrate', 'unit' => 'ml',  'minimum_stock' => 500,  'stock' => 5000],
            // Milks & Creams
            ['name' => 'Fresh Milk',            'unit' => 'ml',  'minimum_stock' => 2000, 'stock' => 15000],
            ['name' => 'Oat Milk',              'unit' => 'ml',  'minimum_stock' => 1000, 'stock' => 8000],
            ['name' => 'Heavy Cream',           'unit' => 'ml',  'minimum_stock' => 500,  'stock' => 4000],
            // Syrups & Sweeteners
            ['name' => 'Simple Syrup',          'unit' => 'ml',  'minimum_stock' => 500,  'stock' => 5000],
            ['name' => 'Caramel Syrup',         'unit' => 'ml',  'minimum_stock' => 300,  'stock' => 3000],
            ['name' => 'Hazelnut Syrup',        'unit' => 'ml',  'minimum_stock' => 300,  'stock' => 3000],
            ['name' => 'Cinnamon Syrup',        'unit' => 'ml',  'minimum_stock' => 300,  'stock' => 3000],
            ['name' => 'Honey',                 'unit' => 'ml',  'minimum_stock' => 300,  'stock' => 2000],
            ['name' => 'Condensed Milk',        'unit' => 'ml',  'minimum_stock' => 500,  'stock' => 4000],
            ['name' => 'Chocolate Powder',      'unit' => 'g',   'minimum_stock' => 200,  'stock' => 3000],
            ['name' => 'Matcha Powder',         'unit' => 'g',   'minimum_stock' => 200,  'stock' => 2000],
            ['name' => 'Milo Powder',           'unit' => 'g',   'minimum_stock' => 200,  'stock' => 2500],
            ['name' => 'Hojicha Powder',        'unit' => 'g',   'minimum_stock' => 200,  'stock' => 2000],
            ['name' => 'Cookie Crumb',          'unit' => 'g',   'minimum_stock' => 100,  'stock' => 1500],
            ['name' => 'Strawberry Puree',      'unit' => 'ml',  'minimum_stock' => 300,  'stock' => 3000],
            ['name' => 'Orange Juice',          'unit' => 'ml',  'minimum_stock' => 300,  'stock' => 3000],
            ['name' => 'Lychee Syrup',          'unit' => 'ml',  'minimum_stock' => 300,  'stock' => 2500],
            ['name' => 'Apple Juice',           'unit' => 'ml',  'minimum_stock' => 300,  'stock' => 2500],
            ['name' => 'Passion Fruit Syrup',   'unit' => 'ml',  'minimum_stock' => 300,  'stock' => 2500],
            ['name' => 'Salted Cream Foam',     'unit' => 'ml',  'minimum_stock' => 200,  'stock' => 2000],
            ['name' => 'Sea Salt',              'unit' => 'g',   'minimum_stock' => 50,   'stock' => 500],
            // Ice & Cups
            ['name' => 'Ice',                   'unit' => 'g',   'minimum_stock' => 5000, 'stock' => 30000],
            ['name' => 'Hot 8oz Cup',           'unit' => 'pc',  'minimum_stock' => 100,  'stock' => 1000],
            ['name' => 'Iced 12oz Cup',         'unit' => 'pc',  'minimum_stock' => 100,  'stock' => 1000],
            ['name' => 'Iced 16oz Cup',         'unit' => 'pc',  'minimum_stock' => 100,  'stock' => 1000],
            ['name' => 'Lid',                   'unit' => 'pc',  'minimum_stock' => 200,  'stock' => 2000],
            ['name' => 'Straw',                 'unit' => 'pc',  'minimum_stock' => 200,  'stock' => 2000],
            ['name' => 'Hot Cup Sleeve',        'unit' => 'pc',  'minimum_stock' => 100,  'stock' => 1000],
        ];

        $ingredients = [];
        foreach ($ingredientData as $data) {
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

        // ─── Categories ─────────────────────────────────────────────────────
        $catEspresso = Category::firstOrCreate(['name' => 'Espresso'], ['status' => 'active']);
        $catColdBrew = Category::firstOrCreate(['name' => 'Cold Brew'], ['status' => 'active']);
        $catMatcha = Category::firstOrCreate(['name' => 'Matcha Series'], ['status' => 'active']);
        $catNonCoffee = Category::firstOrCreate(['name' => 'Non-Coffee'], ['status' => 'active']);
        $catRefreshers = Category::firstOrCreate(['name' => 'Refreshers'], ['status' => 'active']);

        // ─── Coffee Add-ons ──────────────────────────────────────────────────
        ProductAddon::firstOrCreate(['name' => 'Extra Shot'], ['price' => 25, 'status' => 'active']);
        ProductAddon::firstOrCreate(['name' => 'Oat Milk Substitute'], ['price' => 30, 'status' => 'active']);
        ProductAddon::firstOrCreate(['name' => 'Extra Syrup'], ['price' => 15, 'status' => 'active']);
        ProductAddon::firstOrCreate(['name' => 'Less Ice'], ['price' => 0,  'status' => 'active']);
        ProductAddon::firstOrCreate(['name' => 'Extra Cream'], ['price' => 20, 'status' => 'active']);

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

        // ─── ESPRESSO MENU (Hot 8oz / Iced 12oz) ─────────────────────────────
        $espressoItems = [
            // [name, hotPrice, icedPrice, hotRecipe, icedRecipe]
            [
                'Americano', 130, 135,
                [['Espresso Shot', 60], ['Hot 8oz Cup', 1], ['Hot Cup Sleeve', 1]],
                [['Espresso Shot', 60], ['Ice', 150], ['Iced 12oz Cup', 1], ['Lid', 1], ['Straw', 1]],
            ],
            [
                'Latte', 140, 140,
                [['Espresso Shot', 60], ['Fresh Milk', 180], ['Hot 8oz Cup', 1], ['Hot Cup Sleeve', 1]],
                [['Espresso Shot', 60], ['Fresh Milk', 180], ['Ice', 150], ['Iced 12oz Cup', 1], ['Lid', 1], ['Straw', 1]],
            ],
            [
                'Mocha', 140, 150,
                [['Espresso Shot', 60], ['Fresh Milk', 150], ['Chocolate Powder', 15], ['Hot 8oz Cup', 1], ['Hot Cup Sleeve', 1]],
                [['Espresso Shot', 60], ['Fresh Milk', 150], ['Chocolate Powder', 15], ['Ice', 150], ['Iced 12oz Cup', 1], ['Lid', 1], ['Straw', 1]],
            ],
            [
                'Spanish Latte', 140, 150,
                [['Espresso Shot', 60], ['Fresh Milk', 120], ['Condensed Milk', 30], ['Hot 8oz Cup', 1], ['Hot Cup Sleeve', 1]],
                [['Espresso Shot', 60], ['Fresh Milk', 120], ['Condensed Milk', 30], ['Ice', 150], ['Iced 12oz Cup', 1], ['Lid', 1], ['Straw', 1]],
            ],
            [
                'Caramel Latte', 140, 150,
                [['Espresso Shot', 60], ['Fresh Milk', 160], ['Caramel Syrup', 20], ['Hot 8oz Cup', 1], ['Hot Cup Sleeve', 1]],
                [['Espresso Shot', 60], ['Fresh Milk', 160], ['Caramel Syrup', 20], ['Ice', 150], ['Iced 12oz Cup', 1], ['Lid', 1], ['Straw', 1]],
            ],
            [
                'Hazelnut Mocha', 140, 150,
                [['Espresso Shot', 60], ['Fresh Milk', 140], ['Chocolate Powder', 10], ['Hazelnut Syrup', 15], ['Hot 8oz Cup', 1], ['Hot Cup Sleeve', 1]],
                [['Espresso Shot', 60], ['Fresh Milk', 140], ['Chocolate Powder', 10], ['Hazelnut Syrup', 15], ['Ice', 150], ['Iced 12oz Cup', 1], ['Lid', 1], ['Straw', 1]],
            ],
            [
                'Cinnamon Latte', 150, 160,
                [['Espresso Shot', 60], ['Fresh Milk', 160], ['Cinnamon Syrup', 20], ['Hot 8oz Cup', 1], ['Hot Cup Sleeve', 1]],
                [['Espresso Shot', 60], ['Fresh Milk', 160], ['Cinnamon Syrup', 20], ['Ice', 150], ['Iced 12oz Cup', 1], ['Lid', 1], ['Straw', 1]],
            ],
        ];

        foreach ($espressoItems as [$name, $hotPrice, $icedPrice, $hotRecipe, $icedRecipe]) {
            $product = Product::firstOrCreate(['name' => $name], ['category_id' => $catEspresso->id, 'status' => 'active']);
            $sHot = ProductSize::firstOrCreate(['product_id' => $product->id, 'size_name' => 'Hot 8oz'], ['price' => $hotPrice, 'status' => 'active']);
            $sIced = ProductSize::firstOrCreate(['product_id' => $product->id, 'size_name' => 'Iced 12oz'], ['price' => $icedPrice, 'status' => 'active']);
            $makeRecipe($sHot, "{$name} Hot 8oz", $hotRecipe);
            $makeRecipe($sIced, "{$name} Iced 12oz", $icedRecipe);
        }

        // Single-size Espresso drinks (Iced 12oz only)
        $espressoSingleIced = [
            ['Cinnamon Oat Latte', 160, [['Espresso Shot', 60], ['Oat Milk', 160], ['Cinnamon Syrup', 20], ['Ice', 150], ['Iced 12oz Cup', 1], ['Lid', 1], ['Straw', 1]]],
            ['Honey Oat Latte',    150, [['Espresso Shot', 60], ['Oat Milk', 160], ['Honey', 20], ['Ice', 150], ['Iced 12oz Cup', 1], ['Lid', 1], ['Straw', 1]]],
            ['Orange Espresso',   150, [['Espresso Shot', 60], ['Orange Juice', 120], ['Simple Syrup', 10], ['Ice', 150], ['Iced 12oz Cup', 1], ['Lid', 1], ['Straw', 1]]],
            ['Salted Cream',      150, [['Espresso Shot', 60], ['Fresh Milk', 150], ['Salted Cream Foam', 30], ['Ice', 150], ['Iced 12oz Cup', 1], ['Lid', 1], ['Straw', 1]]],
            ['Cookie Latte',      160, [['Espresso Shot', 60], ['Fresh Milk', 150], ['Cookie Crumb', 15], ['Ice', 150], ['Iced 12oz Cup', 1], ['Lid', 1], ['Straw', 1]]],
            ['Solace',            160, [['Espresso Shot', 60], ['Fresh Milk', 140], ['Chocolate Powder', 15], ['Cinnamon Syrup', 15], ['Ice', 150], ['Iced 12oz Cup', 1], ['Lid', 1], ['Straw', 1]]],
        ];

        foreach ($espressoSingleIced as [$name, $price, $recipe]) {
            $product = Product::firstOrCreate(['name' => $name], ['category_id' => $catEspresso->id, 'status' => 'active']);
            $size = ProductSize::firstOrCreate(['product_id' => $product->id, 'size_name' => 'Iced 12oz'], ['price' => $price, 'status' => 'active']);
            $makeRecipe($size, "{$name} Iced 12oz", $recipe);
        }

        // ─── COLD BREW MENU (Iced 12oz) ──────────────────────────────────────
        $coldBrewItems = [
            ['Cold Brew',              135, [['Cold Brew Concentrate', 180], ['Simple Syrup', 10], ['Ice', 180], ['Iced 12oz Cup', 1], ['Lid', 1], ['Straw', 1]]],
            ['Cold Brew Latte',        140, [['Cold Brew Concentrate', 120], ['Fresh Milk', 80], ['Simple Syrup', 10], ['Ice', 150], ['Iced 12oz Cup', 1], ['Lid', 1], ['Straw', 1]]],
            ['Spanish Cold Brew',      150, [['Cold Brew Concentrate', 120], ['Fresh Milk', 60], ['Condensed Milk', 30], ['Ice', 150], ['Iced 12oz Cup', 1], ['Lid', 1], ['Straw', 1]]],
            ['Orange Cold Brew',       150, [['Cold Brew Concentrate', 120], ['Orange Juice', 80], ['Simple Syrup', 10], ['Ice', 150], ['Iced 12oz Cup', 1], ['Lid', 1], ['Straw', 1]]],
            ['Lychee Apple Cold Brew', 160, [['Cold Brew Concentrate', 120], ['Lychee Syrup', 20], ['Apple Juice', 60], ['Ice', 150], ['Iced 12oz Cup', 1], ['Lid', 1], ['Straw', 1]]],
        ];

        foreach ($coldBrewItems as [$name, $price, $recipe]) {
            $product = Product::firstOrCreate(['name' => $name], ['category_id' => $catColdBrew->id, 'status' => 'active']);
            $size = ProductSize::firstOrCreate(['product_id' => $product->id, 'size_name' => 'Iced 12oz'], ['price' => $price, 'status' => 'active']);
            $makeRecipe($size, "{$name} Iced 12oz", $recipe);
        }

        // ─── REFRESHERS (Iced 16oz) ──────────────────────────────────────────
        $refresherItems = [
            ['Lychee Apple',  130, [['Lychee Syrup', 40], ['Apple Juice', 140], ['Ice', 200], ['Iced 16oz Cup', 1], ['Lid', 1], ['Straw', 1]]],
            ['Strawberry',    130, [['Strawberry Puree', 50], ['Simple Syrup', 20], ['Ice', 200], ['Iced 16oz Cup', 1], ['Lid', 1], ['Straw', 1]]],
            ['Passion Fruit', 130, [['Passion Fruit Syrup', 50], ['Simple Syrup', 15], ['Ice', 200], ['Iced 16oz Cup', 1], ['Lid', 1], ['Straw', 1]]],
            ['Orange',        130, [['Orange Juice', 160], ['Simple Syrup', 20], ['Ice', 200], ['Iced 16oz Cup', 1], ['Lid', 1], ['Straw', 1]]],
        ];

        foreach ($refresherItems as [$name, $price, $recipe]) {
            $product = Product::firstOrCreate(['name' => $name], ['category_id' => $catRefreshers->id, 'status' => 'active']);
            $size = ProductSize::firstOrCreate(['product_id' => $product->id, 'size_name' => 'Iced 16oz'], ['price' => $price, 'status' => 'active']);
            $makeRecipe($size, "{$name} Iced 16oz", $recipe);
        }

        // ─── NON-COFFEE (Hot 8oz / Iced 16oz) ────────────────────────────────
        $nonCoffeeItems = [
            [
                'Choco', 140, 150,
                [['Chocolate Powder', 20], ['Fresh Milk', 180], ['Hot 8oz Cup', 1], ['Hot Cup Sleeve', 1]],
                [['Chocolate Powder', 25], ['Fresh Milk', 200], ['Ice', 200], ['Iced 16oz Cup', 1], ['Lid', 1], ['Straw', 1]],
            ],
        ];

        foreach ($nonCoffeeItems as [$name, $hotPrice, $icedPrice, $hotRecipe, $icedRecipe]) {
            $product = Product::firstOrCreate(['name' => $name], ['category_id' => $catNonCoffee->id, 'status' => 'active']);
            $sHot = ProductSize::firstOrCreate(['product_id' => $product->id, 'size_name' => 'Hot 8oz'], ['price' => $hotPrice, 'status' => 'active']);
            $sIced = ProductSize::firstOrCreate(['product_id' => $product->id, 'size_name' => 'Iced 16oz'], ['price' => $icedPrice, 'status' => 'active']);
            $makeRecipe($sHot, "{$name} Hot 8oz", $hotRecipe);
            $makeRecipe($sIced, "{$name} Iced 16oz", $icedRecipe);
        }

        // Single Iced 16oz Non-Coffee
        $nonCoffeeSingleIced = [
            ['Milo Dino',               150, [['Milo Powder', 30], ['Fresh Milk', 200], ['Ice', 200], ['Iced 16oz Cup', 1], ['Lid', 1], ['Straw', 1]]],
            ['Chocolate Cookie',        160, [['Chocolate Powder', 20], ['Cookie Crumb', 15], ['Fresh Milk', 200], ['Ice', 200], ['Iced 16oz Cup', 1], ['Lid', 1], ['Straw', 1]]],
            ['Strawberry Cream',        160, [['Strawberry Puree', 40], ['Heavy Cream', 80], ['Fresh Milk', 100], ['Ice', 200], ['Iced 16oz Cup', 1], ['Lid', 1], ['Straw', 1]]],
            ['Strawberry Cream Cookie', 180, [['Strawberry Puree', 40], ['Heavy Cream', 80], ['Cookie Crumb', 15], ['Fresh Milk', 100], ['Ice', 200], ['Iced 16oz Cup', 1], ['Lid', 1], ['Straw', 1]]],
            ['Hojicha',                 190, [['Hojicha Powder', 10], ['Fresh Milk', 200], ['Simple Syrup', 15], ['Ice', 200], ['Iced 16oz Cup', 1], ['Lid', 1], ['Straw', 1]]],
        ];

        foreach ($nonCoffeeSingleIced as [$name, $price, $recipe]) {
            $product = Product::firstOrCreate(['name' => $name], ['category_id' => $catNonCoffee->id, 'status' => 'active']);
            $size = ProductSize::firstOrCreate(['product_id' => $product->id, 'size_name' => 'Iced 16oz'], ['price' => $price, 'status' => 'active']);
            $makeRecipe($size, "{$name} Iced 16oz", $recipe);
        }

        // ─── MATCHA SERIES ───────────────────────────────────────────────────
        $matchaItems = [
            ['Hot Matcha Latte',         'Hot 8oz',   150, [['Matcha Powder', 8], ['Fresh Milk', 160], ['Hot 8oz Cup', 1], ['Hot Cup Sleeve', 1]]],
            ['Iced Matcha',              'Iced 16oz', 170, [['Matcha Powder', 8], ['Fresh Milk', 180], ['Ice', 200], ['Iced 16oz Cup', 1], ['Lid', 1], ['Straw', 1]]],
            ['Matcha Oat Latte',         'Iced 16oz', 180, [['Matcha Powder', 8], ['Oat Milk', 180], ['Ice', 200], ['Iced 16oz Cup', 1], ['Lid', 1], ['Straw', 1]]],
            ['Seasalt Matcha Oat Latte', 'Iced 16oz', 190, [['Matcha Powder', 8], ['Oat Milk', 180], ['Sea Salt', 2], ['Salted Cream Foam', 25], ['Ice', 200], ['Iced 16oz Cup', 1], ['Lid', 1], ['Straw', 1]]],
        ];

        foreach ($matchaItems as [$name, $sizeName, $price, $recipe]) {
            $product = Product::firstOrCreate(['name' => $name], ['category_id' => $catMatcha->id, 'status' => 'active']);
            $size = ProductSize::firstOrCreate(['product_id' => $product->id, 'size_name' => $sizeName], ['price' => $price, 'status' => 'active']);
            $makeRecipe($size, "{$name} {$sizeName}", $recipe);
        }

        $this->command->info('CoffeeShopSeeder: Coffee and beverage menu seeded successfully.');
    }
}
