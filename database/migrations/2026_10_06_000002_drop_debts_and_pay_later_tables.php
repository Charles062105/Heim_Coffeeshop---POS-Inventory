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
        if (! Schema::hasTable('debts')) {
            Schema::create('debts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->string('customer_name');
                $table->string('customer_phone', 30)->nullable();
                $table->decimal('original_amount', 10, 2);
                $table->decimal('amount_paid', 10, 2)->default(0);
                $table->decimal('balance', 10, 2);
                $table->date('due_date')->nullable();
                $table->string('status', 20)->default('pending');
                $table->text('notes')->nullable();
                $table->string('created_by');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('debt_payments')) {
            Schema::create('debt_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('debt_id')->constrained('debts')->cascadeOnDelete();
                $table->decimal('amount', 10, 2);
                $table->date('payment_date');
                $table->string('payment_method', 30)->default('cash');
                $table->string('reference_number', 100)->nullable();
                $table->text('notes')->nullable();
                $table->string('recorded_by');
                $table->timestamps();
            });
        }

        if (Schema::hasTable('cashier_shifts')) {
            $shiftColumns = ['debt_cash_collections', 'debt_online_collections', 'pay_later_charged'];
            $columnsToRestore = array_values(array_filter(
                $shiftColumns,
                fn ($column) => ! Schema::hasColumn('cashier_shifts', $column)
            ));
            if ($columnsToRestore !== []) {
                Schema::table('cashier_shifts', function (Blueprint $table) use ($columnsToRestore) {
                    foreach ($columnsToRestore as $column) {
                        $table->decimal($column, 10, 2)->default(0);
                    }
                });
            }
        }

        if (Schema::hasTable('debt_payments') && ! Schema::hasColumn('debt_payments', 'shift_id')
            && Schema::hasTable('cashier_shifts')) {
            Schema::table('debt_payments', function (Blueprint $table) {
                $table->foreignId('shift_id')->nullable()->constrained('cashier_shifts')->nullOnDelete();
                $table->index(['shift_id', 'payment_method']);
            });
        }

        if (Schema::hasTable('refunds') && ! Schema::hasColumn('refunds', 'debt_payment_id')) {
            Schema::table('refunds', function (Blueprint $table) {
                $table->foreignId('debt_payment_id')->nullable()->constrained()->nullOnDelete();
            });
        }
    }
};
