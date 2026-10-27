<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $dataToPreserve = [];

        foreach (['debts', 'debt_payments'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                $dataToPreserve[] = $table;
            }
        }

        if (Schema::hasTable('refunds')
            && Schema::hasColumn('refunds', 'debt_payment_id')
            && DB::table('refunds')->whereNotNull('debt_payment_id')->exists()) {
            $dataToPreserve[] = 'refunds.debt_payment_id';
        }

        foreach (['debt_cash_collections', 'debt_online_collections', 'pay_later_charged'] as $column) {
            if (Schema::hasTable('cashier_shifts')
                && Schema::hasColumn('cashier_shifts', $column)
                && DB::table('cashier_shifts')->where($column, '<>', 0)->exists()) {
                $dataToPreserve[] = "cashier_shifts.{$column}";
            }
        }

        if ($dataToPreserve !== []) {
            throw new RuntimeException(
                'Debt retirement stopped because historical records exist in: '.
                implode(', ', $dataToPreserve).
                '. Back up and archive or otherwise disposition this data before retrying the debt retirement migration.'
            );
        }
    }

    public function down(): void {}
};
