<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
            $table->enum('type', [
                'stock_in',
                'sales_consumption',
                'sales_return',
                'waste',
                'adjustment_add',
                'adjustment_deduct',
            ]);
            $table->decimal('quantity', 10, 3);       // always positive
            $table->decimal('previous_stock', 10, 3);
            $table->decimal('new_stock', 10, 3);
            $table->nullableMorphs('reference');       // reference_id + reference_type (order, stock_in, waste, adjustment)
            $table->text('reason')->nullable();
            $table->string('performed_by');            // user name
            $table->string('performed_role');          // user role
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference_number')->nullable(); // delivery ref / PO number
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
