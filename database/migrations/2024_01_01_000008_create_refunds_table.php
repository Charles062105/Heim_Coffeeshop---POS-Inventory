<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('refunds')) {
            Schema::create('refunds', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->restrictOnDelete();
                $table->decimal('amount', 10, 2);
                $table->text('reason');
                $table->string('authorized_by');    // authorizer name
                $table->string('authorized_role');  // authorizer role
                $table->foreignId('authorized_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('stock_restored')->default(false);
                $table->timestamp('refunded_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('order_adjustments')) {
            Schema::create('order_adjustments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->restrictOnDelete();
                $table->enum('action', ['cancel', 'edit', 'other']);
                $table->text('reason');
                $table->string('authorized_by');
                $table->string('authorized_role');
                $table->foreignId('authorized_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->json('before_state')->nullable(); // snapshot of order before edit
                $table->json('after_state')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('purchase_orders')) {
            Schema::create('purchase_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
                $table->string('reference_no')->nullable();
                $table->enum('status', ['draft', 'received', 'cancelled'])->default('draft');
                $table->decimal('total', 10, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('purchase_order_items')) {
            Schema::create('purchase_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
                $table->decimal('quantity', 10, 3);
                $table->decimal('unit_cost', 10, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('order_adjustments');
        Schema::dropIfExists('refunds');
    }
};
