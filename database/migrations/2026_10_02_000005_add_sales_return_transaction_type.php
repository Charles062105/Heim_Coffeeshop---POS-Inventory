<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE inventory_transactions MODIFY COLUMN type ENUM('stock_in', 'sales_consumption', 'sales_return', 'waste', 'adjustment_add', 'adjustment_deduct') NOT NULL");
        } else {
            Schema::table('inventory_transactions', function (Blueprint $table) {
                $table->enum('type', ['stock_in', 'sales_consumption', 'sales_return', 'waste', 'adjustment_add', 'adjustment_deduct'])->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::table('inventory_transactions')->where('type', 'sales_return')->exists()) {
            throw new RuntimeException('Cannot remove sales_return while return movements exist.');
        }

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE inventory_transactions MODIFY COLUMN type ENUM('stock_in', 'sales_consumption', 'waste', 'adjustment_add', 'adjustment_deduct') NOT NULL");
        } else {
            Schema::table('inventory_transactions', function (Blueprint $table) {
                $table->enum('type', ['stock_in', 'sales_consumption', 'waste', 'adjustment_add', 'adjustment_deduct'])->change();
            });
        }
    }
};
