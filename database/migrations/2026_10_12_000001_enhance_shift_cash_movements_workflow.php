<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add cash_in and cash_out to cashier_shifts if not present
        Schema::table('cashier_shifts', function (Blueprint $table) {
            if (! Schema::hasColumn('cashier_shifts', 'cash_in')) {
                $table->decimal('cash_in', 10, 2)->default(0.00)->after('cash_sales');
            }
            if (! Schema::hasColumn('cashier_shifts', 'cash_out')) {
                $table->decimal('cash_out', 10, 2)->default(0.00)->after('cash_in');
            }
        });

        // 2. Enhance shift_cash_movements with branch, attribution, authorization, and reversal columns
        Schema::table('shift_cash_movements', function (Blueprint $table) {
            if (! Schema::hasColumn('shift_cash_movements', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('shift_id')->constrained('branches')->nullOnDelete();
            }
            if (! Schema::hasColumn('shift_cash_movements', 'cashier_id')) {
                $table->foreignId('cashier_id')->nullable()->after('branch_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('shift_cash_movements', 'authorized_by')) {
                $table->foreignId('authorized_by')->nullable()->after('recorded_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('shift_cash_movements', 'status')) {
                $table->string('status', 20)->default('approved')->after('reason');
            }
            if (! Schema::hasColumn('shift_cash_movements', 'request_id')) {
                $table->string('request_id', 64)->nullable()->unique()->after('status');
            }
            if (! Schema::hasColumn('shift_cash_movements', 'reversed_at')) {
                $table->timestamp('reversed_at')->nullable()->after('request_id');
            }
            if (! Schema::hasColumn('shift_cash_movements', 'reversed_by')) {
                $table->foreignId('reversed_by')->nullable()->after('reversed_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('shift_cash_movements', 'reversal_reason')) {
                $table->text('reversal_reason')->nullable()->after('reversed_by');
            }
            if (! Schema::hasColumn('shift_cash_movements', 'reversal_id')) {
                $table->foreignId('reversal_id')->nullable()->after('reversal_reason')->constrained('shift_cash_movements')->nullOnDelete();
            }
        });

        // Add indices for fast querying and reporting
        Schema::table('shift_cash_movements', function (Blueprint $table) {
            $table->index(['shift_id', 'status']);
            $table->index(['branch_id', 'created_at']);
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('shift_cash_movements', function (Blueprint $table) {
            $table->dropIndex(['shift_id', 'status']);
            $table->dropIndex(['branch_id', 'created_at']);
            $table->dropIndex(['type', 'status']);

            $table->dropForeign(['reversal_id']);
            $table->dropForeign(['reversed_by']);
            $table->dropForeign(['authorized_by']);
            $table->dropForeign(['cashier_id']);
            $table->dropForeign(['branch_id']);

            $table->dropColumn([
                'branch_id',
                'cashier_id',
                'authorized_by',
                'status',
                'request_id',
                'reversed_at',
                'reversed_by',
                'reversal_reason',
                'reversal_id',
            ]);
        });

        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->dropColumn(['cash_in', 'cash_out']);
        });
    }
};
