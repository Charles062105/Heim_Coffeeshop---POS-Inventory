<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->string('address')->nullable();
                $table->string('phone')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $mainBranchId = DB::table('branches')->where('code', 'MAIN')->value('id');

        if (! $mainBranchId) {
            DB::table('branches')->insert([
                'name' => 'Main Branch',
                'code' => 'MAIN',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $mainBranchId = DB::table('branches')->where('code', 'MAIN')->value('id');
        }

        foreach (['users', 'orders', 'inventory', 'inventory_transactions', 'cashier_shifts', 'notifications'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'branch_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                });
            } else {
                $hasBranchForeignKey = collect(Schema::getForeignKeys($tableName))->contains(
                    fn (array $foreignKey) => $foreignKey['columns'] === ['branch_id']
                        && $foreignKey['foreign_table'] === 'branches'
                        && $foreignKey['foreign_columns'] === ['id']
                );

                if (! $hasBranchForeignKey) {
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
                    });
                }
            }

            DB::table($tableName)->whereNull('branch_id')->update(['branch_id' => $mainBranchId]);
        }

        $inventoryIndexes = Schema::getIndexes('inventory');
        $hasBranchIngredientUniqueIndex = collect($inventoryIndexes)->contains(
            fn (array $index) => $index['unique'] && $index['columns'] === ['ingredient_id', 'branch_id']
        );

        if (! $hasBranchIngredientUniqueIndex) {
            Schema::table('inventory', function (Blueprint $table) {
                $table->unique(['ingredient_id', 'branch_id']);
            });
        }

        $ingredientUniqueIndex = collect(Schema::getIndexes('inventory'))->first(
            fn (array $index) => $index['unique'] && $index['columns'] === ['ingredient_id']
        );

        if ($ingredientUniqueIndex) {
            Schema::table('inventory', function (Blueprint $table) use ($ingredientUniqueIndex) {
                $table->dropUnique($ingredientUniqueIndex['name']);
            });
        }
    }

    public function down(): void
    {
        $stockByIngredient = DB::table('inventory')
            ->select('ingredient_id')
            ->selectRaw('SUM(current_stock) as current_stock')
            ->groupBy('ingredient_id')
            ->get();

        foreach ($stockByIngredient as $stock) {
            $keepId = DB::table('inventory')
                ->where('ingredient_id', $stock->ingredient_id)
                ->orderBy('id')
                ->value('id');
            DB::table('inventory')->where('id', $keepId)->update(['current_stock' => $stock->current_stock]);
            DB::table('inventory')
                ->where('ingredient_id', $stock->ingredient_id)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        Schema::table('inventory', function (Blueprint $table) {
            $table->dropUnique(['ingredient_id', 'branch_id']);
            $table->unique('ingredient_id');
        });

        foreach (['notifications', 'cashier_shifts', 'inventory_transactions', 'inventory', 'orders', 'users'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }

        Schema::dropIfExists('branches');
    }
};
