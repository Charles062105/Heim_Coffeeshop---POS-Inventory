<?php

namespace Database\Seeders;

use App\Models\AddonIngredient;
use App\Models\Ingredient;
use App\Models\ProductAddon;
use Illuminate\Database\Seeder;

/**
 * AddonIngredientSeeder
 *
 * Maps every ProductAddon to the ingredient(s) it consumes.
 * This enables InventoryService::deductFromSale() to correctly
 * deduct addon ingredient consumption on every POS order.
 *
 * Mapping logic:
 *  - "Extra Shot"            → Espresso Shot: 30 ml
 *  - "Oat Milk Substitute"   → Oat Milk: 180 ml, replacing Fresh Milk in the base recipe
 *  - "Extra Syrup"           → Simple Syrup: 15 ml
 *  - "Extra Cream"           → Heavy Cream: 30 ml
 *  - "Less Ice"              → (no ingredient deduction — free modifier)
 *  - "Extra Rice"            → Rice Portion: 150 g
 *  - "Cheesy Buffalo Drip"   → Cheesy Buffalo Drip: 30 ml
 *  - "Double Cheese Drip"    → Double Cheese Drip: 30 ml
 *  - Flavor addons (₱0)      → (no ingredient deduction — flavor selection only)
 */
class AddonIngredientSeeder extends Seeder
{
    public function run(): void
    {
        // ── Ingredient lookup helper ─────────────────────────────────────────
        // We look up by name so this seeder is idempotent and order-independent.
        $ing = fn (string $name) => Ingredient::where('name', $name)->first();

        // ── Addon → ingredient mappings ──────────────────────────────────────
        // Format: [ 'Addon Name' => [ ['Ingredient Name', quantity], ... ] ]
        $mappings = [
            // Coffee add-ons
            'Extra Shot' => [
                ['Espresso Shot', 30],     // 30 ml extra espresso
            ],
            'Oat Milk Substitute' => [
                ['Oat Milk', 180, 'Fresh Milk'],
            ],
            'Extra Syrup' => [
                ['Simple Syrup', 15],      // 15 ml extra syrup
            ],
            'Extra Cream' => [
                ['Heavy Cream', 30],       // 30 ml extra heavy cream
            ],
            // 'Less Ice'    → no deduction (modifier only, no ingredient)

            // Food add-ons
            'Extra Rice' => [
                ['Rice Portion', 150],     // 150 g extra rice
            ],

            // Creamy Cheese Drips
            'Cheesy Buffalo (Creamy Cheese Drip)' => [
                ['Cheesy Buffalo Drip', 30],  // 30 ml cheese drip
            ],
            'Double Cheese (Creamy Cheese Drip)' => [
                ['Double Cheese Drip', 30],   // 30 ml double cheese drip
            ],

            // Flavor add-ons — these are selection modifiers only.
            // No ingredient is consumed directly because the wing sauce
            // is already accounted for in the recipe OR is customer-chosen
            // from existing sauce stock managed separately.
            // Uncomment the lines below if you want to track sauce consumption:
            //
            // 'Flavor: Buffalo Wings'    => [['Buffalo Sauce', 30]],
            // 'Flavor: Sour Cream'       => [['Sour Cream Dip', 30]],
            // 'Flavor: Salted Egg'       => [['Salted Egg Sauce', 30]],
            // 'Flavor: Cheese'           => [['Cheese Sauce Dip', 30]],
            // 'Flavor: Honey Glazed'     => [['Honey Glaze Sauce', 30]],
            // 'Flavor: Garlic Parmesan'  => [['Garlic Parmesan Sauce', 30]],
        ];

        $linked = 0;
        $skipped = 0;
        $notFound = [];

        foreach ($mappings as $addonName => $ingredientList) {
            $addon = ProductAddon::where('name', $addonName)->first();

            if (! $addon) {
                $notFound[] = "Addon not found: {$addonName}";
                $skipped++;

                continue;
            }

            foreach ($ingredientList as $mapping) {
                [$ingredientName, $quantity] = $mapping;
                $replacesIngredientName = $mapping[2] ?? null;
                $ingredient = $ing($ingredientName);

                if (! $ingredient) {
                    $notFound[] = "Ingredient not found: {$ingredientName} (for addon: {$addonName})";
                    $skipped++;

                    continue;
                }

                $values = [
                    'quantity' => $quantity,
                    'replaces_ingredient_id' => null,
                ];
                if ($replacesIngredientName) {
                    $replacedIngredient = $ing($replacesIngredientName);
                    if (! $replacedIngredient) {
                        $notFound[] = "Ingredient not found: {$replacesIngredientName} (replaced by addon: {$addonName})";
                        $skipped++;

                        continue;
                    }
                    $values['replaces_ingredient_id'] = $replacedIngredient->id;
                }

                AddonIngredient::firstOrCreate(
                    [
                        'product_addon_id' => $addon->id,
                        'ingredient_id' => $ingredient->id,
                    ],
                    $values
                );

                $linked++;
            }
        }

        $this->command->info("AddonIngredientSeeder: {$linked} addon→ingredient links created/verified.");

        if (! empty($notFound)) {
            foreach ($notFound as $msg) {
                $this->command->warn("  ⚠ {$msg}");
            }
        }

        // ── Summary ──────────────────────────────────────────────────────────
        $total = AddonIngredient::count();
        $this->command->info("  Total addon ingredient records in DB: {$total}");
    }
}
