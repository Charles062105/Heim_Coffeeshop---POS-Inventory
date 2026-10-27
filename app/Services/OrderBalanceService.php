<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderBalanceService
{
    public function summarizeOpenBalances(Builder $orders): Collection
    {
        $paidPayments = DB::table('payments')
            ->select('order_id')
            ->selectRaw('SUM(amount_paid) as paid_amount')
            ->where('status', 'paid')
            ->groupBy('order_id');

        return $orders
            ->leftJoinSub($paidPayments, 'paid_payments', fn ($join) => $join->on('orders.id', '=', 'paid_payments.order_id'))
            ->whereIn('orders.status', ['pending', 'partially_paid'])
            ->select('orders.status')
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw(
                'SUM(CASE WHEN orders.total > COALESCE(paid_payments.paid_amount, 0) '.
                'THEN orders.total - COALESCE(paid_payments.paid_amount, 0) ELSE 0 END) as balance_due'
            )
            ->groupBy('orders.status')
            ->get()
            ->keyBy('status');
    }
}
