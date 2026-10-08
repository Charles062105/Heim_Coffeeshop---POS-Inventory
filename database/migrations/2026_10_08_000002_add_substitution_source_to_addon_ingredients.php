<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_addon_ingredients', function (Blueprint $table) {
            $table->foreignId('replaces_ingredient_id')
                ->nullable()
                ->after('ingredient_id')
                ->constrained('ingredients')
                ->restrictOnDelete();
            $table->unique(
                ['product_addon_id', 'replaces_ingredient_id'],
                'addon_ingredients_replacement_unique'
            );
        });

        $addonId = DB::table('product_addons')->where('name', 'Oat Milk Substitute')->value('id');
        $oatMilkId = DB::table('ingredients')->where('name', 'Oat Milk')->value('id');
        $freshMilkId = DB::table('ingredients')->where('name', 'Fresh Milk')->value('id');

        if ($addonId && $oatMilkId && $freshMilkId) {
            DB::table('product_addon_ingredients')
                ->where('product_addon_id', $addonId)
                ->where('ingredient_id', $oatMilkId)
                ->whereNull('replaces_ingredient_id')
                ->update(['replaces_ingredient_id' => $freshMilkId]);
        }
    }

    public function down(): void
    {
        Schema::table('product_addon_ingredients', function (Blueprint $table) {
            $table->dropUnique('addon_ingredients_replacement_unique');
            $table->dropForeign(['replaces_ingredient_id']);
            $table->dropColumn('replaces_ingredient_id');
        });
    }
};
