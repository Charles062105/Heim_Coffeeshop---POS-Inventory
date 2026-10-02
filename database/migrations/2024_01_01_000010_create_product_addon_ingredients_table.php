<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates product_addon_ingredients table.
 *
 * This links each ProductAddon to one or more Ingredients with a quantity,
 * enabling the InventoryService to deduct addon-specific ingredient consumption
 * whenever an addon is selected in a POS order.
 *
 * Examples:
 *   - "Extra Shot"  → Espresso Shot: 30 ml
 *   - "Oat Milk"    → Oat Milk: 180 ml
 *   - "Extra Cream" → Heavy Cream: 30 ml
 *   - "Cheesy Buffalo Drip" → Cheesy Buffalo Drip: 30 ml
 *   - "Double Cheese Drip" → Double Cheese Drip: 30 ml
 *   - Flavor addons (₱0) → no ingredient link needed (free modifier)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_addon_ingredients')) {
            Schema::create('product_addon_ingredients', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_addon_id')
                    ->constrained('product_addons')
                    ->cascadeOnDelete();
                $table->foreignId('ingredient_id')
                    ->constrained('ingredients')
                    ->restrictOnDelete();
                $table->decimal('quantity', 10, 3)->comment('Amount consumed per 1 addon unit');
                $table->timestamps();

                // An addon can only map to a given ingredient once
                $table->unique(['product_addon_id', 'ingredient_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_addon_ingredients');
    }
};
