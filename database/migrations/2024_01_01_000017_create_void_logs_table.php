<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('void_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->string('void_type', 20)->default('order'); // 'order' or 'item'
            $table->text('reason');
            $table->string('cashier_name');
            $table->foreignId('authorized_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('authorized_by');
            $table->string('authorized_role');
            $table->boolean('stock_restored')->default(false);
            $table->timestamp('voided_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('void_logs');
    }
};
