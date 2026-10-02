<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->foreignId('open_user_id')->nullable()->unique()->constrained('users')->nullOnDelete()->after('user_id');
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->json('denomination_count')->nullable();
            $table->decimal('cash_voids', 10, 2)->default(0);
            $table->decimal('debt_cash_collections', 10, 2)->default(0);
            $table->decimal('online_sales', 10, 2)->default(0);
            $table->decimal('debt_online_collections', 10, 2)->default(0);
            $table->decimal('grab_sales', 10, 2)->default(0);
            $table->decimal('grab_settlements', 10, 2)->default(0);
            $table->decimal('pay_later_charged', 10, 2)->default(0);
            $table->unsignedInteger('void_count')->default(0);
            $table->decimal('void_amount', 10, 2)->default(0);
            $table->decimal('dine_in_sales', 10, 2)->default(0);
            $table->decimal('take_out_sales', 10, 2)->default(0);
        });

        DB::table('cashier_shifts')
            ->where('status', 'open')
            ->update(['open_user_id' => DB::raw('user_id')]);

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->constrained('cashier_shifts')->nullOnDelete();
            $table->index(['shift_id', 'method', 'status']);
        });
        DB::table('payments')->whereNull('shift_id')->whereIn(
            'order_id',
            DB::table('orders')->select('id')->whereNotNull('shift_id')
        )->update(['shift_id' => DB::raw('(select shift_id from orders where orders.id = payments.order_id)')]);

        Schema::table('debt_payments', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->constrained('cashier_shifts')->nullOnDelete();
            $table->index(['shift_id', 'payment_method']);
        });
        DB::table('debt_payments')->whereNull('shift_id')->whereIn(
            'debt_id',
            DB::table('debts')->select('id')->whereIn(
                'order_id',
                DB::table('orders')->select('id')->whereNotNull('shift_id')
            )
        )->update(['shift_id' => DB::raw('(select orders.shift_id from orders join debts on debts.order_id = orders.id where debts.id = debt_payments.debt_id)')]);

        Schema::table('refunds', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->constrained('cashier_shifts')->nullOnDelete();
            $table->index(['shift_id', 'method', 'status']);
        });

        Schema::table('void_logs', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->constrained('cashier_shifts')->nullOnDelete();
            $table->decimal('amount', 10, 2)->default(0);
            $table->index('shift_id');
        });
        DB::table('void_logs')->whereNull('shift_id')->whereIn(
            'order_id',
            DB::table('orders')->select('id')->whereNotNull('shift_id')
        )->update(['shift_id' => DB::raw('(select shift_id from orders where orders.id = void_logs.order_id)')]);

        Schema::create('shift_cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('cashier_shifts')->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->string('type', 20)->default('adjustment');
            $table->decimal('amount', 10, 2);
            $table->text('reason');
            $table->timestamps();
            $table->index(['shift_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_cash_movements');
        Schema::table('void_logs', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropIndex(['shift_id']);
            $table->dropColumn(['shift_id', 'amount']);
        });
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropIndex(['shift_id', 'method', 'status']);
            $table->dropColumn('shift_id');
        });
        Schema::table('debt_payments', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropIndex(['shift_id', 'payment_method']);
            $table->dropColumn('shift_id');
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropIndex(['shift_id', 'method', 'status']);
            $table->dropColumn('shift_id');
        });
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->dropUnique(['open_user_id']);
            $table->dropForeign(['open_user_id']);
            $table->dropForeign(['closed_by']);
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn([
                'open_user_id',
                'closed_by',
                'reviewed_by',
                'reviewed_at',
                'review_note',
                'denomination_count',
                'cash_voids',
                'debt_cash_collections',
                'online_sales',
                'debt_online_collections',
                'grab_sales',
                'grab_settlements',
                'pay_later_charged',
                'void_count',
                'void_amount',
                'dine_in_sales',
                'take_out_sales',
            ]);
        });
    }
};
