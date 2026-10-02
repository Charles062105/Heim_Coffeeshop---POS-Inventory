<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->string('method', 20)->default('cash');
            $table->string('status', 20)->default('completed');
            $table->foreignId('payment_id')->nullable()->after('order_id')->constrained()->nullOnDelete();
            $table->foreignId('debt_payment_id')->nullable()->after('payment_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
            $table->dropForeign(['debt_payment_id']);
            $table->dropColumn(['payment_id', 'debt_payment_id', 'method', 'status']);
        });
    }
};
