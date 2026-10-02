<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->decimal('reorder_level', 10, 3)->default(0);
            $table->decimal('cost', 10, 2)->default(0);
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->date('expiration_date')->nullable();
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->date('transaction_date')->nullable();
            $table->date('expiration_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropColumn(['unit_cost', 'transaction_date', 'expiration_date']);
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['reorder_level', 'cost', 'supplier_id', 'expiration_date']);
        });
    }
};
