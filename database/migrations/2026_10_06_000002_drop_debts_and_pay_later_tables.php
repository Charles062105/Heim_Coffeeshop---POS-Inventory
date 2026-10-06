<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('refunds') && Schema::hasColumn('refunds', 'debt_payment_id')) {
            Schema::table('refunds', function (Blueprint $table) {
                $table->dropForeign(['debt_payment_id']);
                $table->dropColumn('debt_payment_id');
            });
        }

        Schema::dropIfExists('debt_payments');
        Schema::dropIfExists('debts');

        if (Schema::hasTable('cashier_shifts')) {
            Schema::table('cashier_shifts', function (Blueprint $table) {
                $columnsToDrop = [];
                if (Schema::hasColumn('cashier_shifts', 'debt_cash_collections')) {
                    $columnsToDrop[] = 'debt_cash_collections';
                }
                if (Schema::hasColumn('cashier_shifts', 'debt_online_collections')) {
                    $columnsToDrop[] = 'debt_online_collections';
                }
                if (Schema::hasColumn('cashier_shifts', 'pay_later_charged')) {
                    $columnsToDrop[] = 'pay_later_charged';
                }
                if (! empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }
    }

    public function down(): void
    {
    }
};
