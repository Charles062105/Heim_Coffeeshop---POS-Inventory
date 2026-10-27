<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'payment_status')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('payment_status', 20)->default('unpaid');
            });
        }

        if (! Schema::hasTable('order_payment_portions')) {
            Schema::create('order_payment_portions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->string('payer_name', 150);
                $table->decimal('due_amount', 10, 2);
                $table->decimal('amount_paid', 10, 2)->default(0);
                $table->string('status', 20)->default('pending');
                $table->timestamps();
                $table->unique(['order_id', 'payer_name']);
            });
        }

        if (! Schema::hasColumn('payments', 'portion_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreignId('portion_id')
                    ->nullable()
                    ->constrained('order_payment_portions')
                    ->nullOnDelete();
            });
        }

        foreach (DB::table('orders')->select('id', 'total', 'status')->orderBy('id')->cursor() as $order) {
            $paid = (float) DB::table('payments')
                ->where('order_id', $order->id)
                ->where('status', 'paid')
                ->sum('amount_paid');
            $status = match ($order->status) {
                'refunded' => 'refunded',
                'voided' => 'voided',
                default => $paid <= 0 ? 'unpaid' : ($paid >= (float) $order->total ? 'paid' : 'partially_paid'),
            };
            DB::table('orders')->where('id', $order->id)->update(['payment_status' => $status]);
        }

        if (Schema::hasColumn('order_items', 'assigned_to') && Schema::hasColumn('order_items', 'payable_total')) {
            $assignments = DB::table('order_items')
                ->where('status', 'active')
                ->whereNotNull('assigned_to')
                ->select('order_id', 'assigned_to')
                ->selectRaw('SUM(payable_total) AS due_amount')
                ->groupBy('order_id', 'assigned_to')
                ->orderBy('order_id')
                ->cursor();

            foreach ($assignments as $assignment) {
                $paid = (float) DB::table('payments')
                    ->where('order_id', $assignment->order_id)
                    ->where('customer_name', $assignment->assigned_to)
                    ->where('status', 'paid')
                    ->sum('amount_paid');
                $due = (float) $assignment->due_amount;
                DB::table('order_payment_portions')->updateOrInsert(
                    ['order_id' => $assignment->order_id, 'payer_name' => $assignment->assigned_to],
                    [
                        'due_amount' => $due,
                        'amount_paid' => min($paid, $due),
                        'status' => $paid <= 0 ? 'pending' : ($paid >= $due ? 'paid' : 'partially_paid'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        DB::table('payments')
            ->whereNull('portion_id')
            ->whereNotNull('customer_name')
            ->orderBy('id')
            ->cursor()
            ->each(function ($payment) {
                $portionId = DB::table('order_payment_portions')
                    ->where('order_id', $payment->order_id)
                    ->where('payer_name', $payment->customer_name)
                    ->value('id');
                if ($portionId) {
                    DB::table('payments')->where('id', $payment->id)->update(['portion_id' => $portionId]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('payments', 'portion_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('portion_id');
            });
        }

        Schema::dropIfExists('order_payment_portions');

        if (Schema::hasColumn('orders', 'payment_status')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('payment_status');
            });
        }
    }
};
