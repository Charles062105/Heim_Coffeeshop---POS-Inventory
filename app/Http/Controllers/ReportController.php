<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\DebtPayment;
use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Refund;
use App\Models\VoidLog;
use App\Services\AuditService;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $filters = $request->validate([
            'type' => ['nullable', 'in:daily,weekly,monthly,yearly,custom,all'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'from' => ['nullable', 'required_if:type,custom', 'date_format:Y-m-d'],
            'to' => ['nullable', 'required_if:type,custom', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $type = $filters['type'] ?? 'daily';
        $date = $filters['date'] ?? now()->toDateString();
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        [$startDate, $endDate] = $this->resolveDateRange($type, $date, $from, $to);

        $query = Order::whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);

        $completedQuery = (clone $query)->whereIn('status', ['completed', 'partially_paid', 'pay_later']);

        $totalSales = $completedQuery->sum('total');
        $totalOrders = $completedQuery->count();
        $totalItems = OrderItem::where('order_items.status', 'active')
            ->whereHas('order', fn ($q) => $q->whereIn('status', ['completed', 'partially_paid', 'pay_later'])
                ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            )->sum('quantity');

        // Payment breakdown (Cash vs Online Payment)
        $paymentBreakdown = DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->where('payments.status', 'paid')
            ->whereIn('orders.status', ['completed', 'partially_paid', 'pay_later'])
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$startDate, $endDate])
            ->select(
                DB::raw("CASE WHEN LOWER(payments.method) = 'cash' THEN 'cash' ELSE 'online' END as method"),
                DB::raw('SUM(payments.amount_paid) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw("CASE WHEN LOWER(payments.method) = 'cash' THEN 'cash' ELSE 'online' END"))
            ->get();

        // Best-selling products
        $bestSellers = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('product_sizes', 'order_items.product_size_id', '=', 'product_sizes.id')
            ->where('order_items.status', 'active')
            ->whereIn('orders.status', ['completed', 'partially_paid', 'pay_later'])
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$startDate, $endDate])
            ->select(
                'products.name as product',
                'product_sizes.size_name',
                DB::raw('SUM(order_items.quantity) as qty_sold'),
                DB::raw('SUM(order_items.subtotal) as revenue')
            )
            ->groupBy('products.name', 'product_sizes.size_name')
            ->orderByDesc('qty_sold')
            ->limit(10)
            ->get();

        // Sales by cashier
        $byCashier = Order::whereIn('status', ['completed', 'partially_paid', 'pay_later'])
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->select('cashier_name', DB::raw('SUM(total) as total_sales'), DB::raw('COUNT(*) as total_orders'))
            ->groupBy('cashier_name')
            ->orderByDesc('total_sales')
            ->get();

        // Refunds in period
        $refundsCount = Order::where('status', 'refunded')
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->count();
        $refundsAmount = Refund::whereHas('order', fn ($q) => $q->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
        )->sum('amount');

        // Daily breakdown (for multi-day reports)
        $dailyBreakdown = Order::whereIn('status', ['completed', 'partially_paid', 'pay_later'])
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as orders'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'))
            ->get();

        $user = $request->user();
        AuditService::logFromUser($user, 'generated_sales_report', 'Reports', [
            'type' => $type,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        if ($request->get('export') === 'excel') {
            $headers = [
                'Order ID',
                'Order Number',
                'Date & Time',
                'Cashier',
                'Payment Method',
                'Reference Number',
                'Items Count',
                'Subtotal (PHP)',
                'Discount (PHP)',
                'Total (PHP)',
                'Status',
            ];

            $orders = (clone $completedQuery)
                ->with(['payment', 'items' => fn ($items) => $items->where('status', 'active')])
                ->orderBy('created_at')
                ->get();
            $rows = [];
            foreach ($orders as $order) {
                $payment = $order->payment;
                $rows[] = [
                    $order->id,
                    $order->order_number ?? ('#'.$order->id),
                    $order->created_at->format('Y-m-d H:i:s'),
                    $order->cashier_name ?? '—',
                    $payment ? ucfirst($payment->method) : '—',
                    $payment?->reference_number ?? '—',
                    $order->items->sum('quantity'),
                    number_format((float) $order->subtotal, 2, '.', ''),
                    number_format((float) $order->discount, 2, '.', ''),
                    number_format((float) $order->total, 2, '.', ''),
                    ucfirst($order->status),
                ];
            }

            $rows[] = [
                'TOTALS',
                'Orders Count: '.$orders->count(),
                '',
                '',
                '',
                '',
                $totalItems,
                number_format((float) $completedQuery->sum('subtotal'), 2, '.', ''),
                number_format((float) $completedQuery->sum('discount'), 2, '.', ''),
                number_format((float) $totalSales, 2, '.', ''),
                '',
            ];

            return ExportService::streamCsv("sales-report-{$startDate}-to-{$endDate}.csv", $headers, $rows);
        }

        return view('reports.sales', compact(
            'type', 'date', 'from', 'to', 'startDate', 'endDate',
            'totalSales', 'totalOrders', 'totalItems',
            'paymentBreakdown', 'bestSellers', 'byCashier',
            'refundsCount', 'refundsAmount', 'dailyBreakdown',
            'user'
        ));
    }

    public function inventory(Request $request)
    {
        $filters = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $defaultDate = now()->toDateString();
        $from = $filters['from'] ?? $filters['to'] ?? $defaultDate;
        $to = $filters['to'] ?? $from;

        // Current stock status
        $allIngredients = Ingredient::with('inventory')->orderBy('name')->get();
        $lowStock = $allIngredients->filter(fn ($i) => $i->getStockStatus() === 'low_stock');
        $outOfStock = $allIngredients->filter(fn ($i) => $i->getStockStatus() === 'out_of_stock');

        // Stock-in in period
        $stockIns = InventoryTransaction::with(['ingredient', 'supplier'])
            ->where('type', 'stock_in')
            ->effectiveDateFrom($from)
            ->effectiveDateTo($to)
            ->orderByRaw('COALESCE(transaction_date, created_at) DESC')
            ->get();

        // Waste in period
        $wasteRecords = InventoryTransaction::with('ingredient')
            ->where('type', 'waste')
            ->effectiveDateFrom($from)
            ->effectiveDateTo($to)
            ->orderByRaw('COALESCE(transaction_date, created_at) DESC')
            ->get();

        // Net sales consumption subtracts stock returned by voided/refunded items.
        $salesConsumption = InventoryTransaction::with('ingredient')
            ->whereIn('type', ['sales_consumption', 'sales_return'])
            ->effectiveDateFrom($from)
            ->effectiveDateTo($to)
            ->select('ingredient_id')
            ->selectRaw(
                "SUM(CASE WHEN COALESCE(reason, '') NOT LIKE ? AND reference_type = ? THEN CASE WHEN type = 'sales_return' THEN -quantity ELSE quantity END ELSE 0 END) as recipe_consumed",
                ['%Addon%', OrderItem::class]
            )
            ->selectRaw(
                "SUM(CASE WHEN COALESCE(reason, '') LIKE ? THEN CASE WHEN type = 'sales_return' THEN -quantity ELSE quantity END ELSE 0 END) as addon_consumed",
                ['%Addon%']
            )
            ->selectRaw(
                "SUM(CASE WHEN COALESCE(reason, '') NOT LIKE ? AND (reference_type IS NULL OR reference_type <> ?) THEN CASE WHEN type = 'sales_return' THEN -quantity ELSE quantity END ELSE 0 END) as unattributed_consumed",
                ['%Addon%', OrderItem::class]
            )
            ->selectRaw("SUM(CASE WHEN type = 'sales_return' THEN -quantity ELSE quantity END) as total_consumed")
            ->groupBy('ingredient_id')
            ->orderByDesc(DB::raw("SUM(CASE WHEN type = 'sales_return' THEN -quantity ELSE quantity END)"))
            ->get();
        $consumptionTotals = $salesConsumption
            ->groupBy(fn ($row) => $row->ingredient?->unit ?? 'unknown')
            ->map(fn ($rows) => [
                'recipe_consumed' => (float) $rows->sum('recipe_consumed'),
                'addon_consumed' => (float) $rows->sum('addon_consumed'),
                'unattributed_consumed' => (float) $rows->sum('unattributed_consumed'),
                'total_consumed' => (float) $rows->sum('total_consumed'),
            ]);

        $user = $request->user();
        AuditService::logFromUser($user, 'generated_inventory_report', 'Reports', [
            'from' => $from, 'to' => $to,
        ]);

        if ($request->get('export') === 'excel') {
            $stockInMap = InventoryTransaction::where('type', 'stock_in')
                ->effectiveDateFrom($from)
                ->effectiveDateTo($to)
                ->groupBy('ingredient_id')
                ->select('ingredient_id', DB::raw('SUM(quantity) as total_qty'))
                ->pluck('total_qty', 'ingredient_id');

            $wasteMap = InventoryTransaction::where('type', 'waste')
                ->effectiveDateFrom($from)
                ->effectiveDateTo($to)
                ->groupBy('ingredient_id')
                ->select('ingredient_id', DB::raw('SUM(quantity) as total_qty'))
                ->pluck('total_qty', 'ingredient_id');

            $consumptionMap = $salesConsumption->keyBy('ingredient_id');

            $headers = [
                'Ingredient ID',
                'Ingredient Name',
                'Unit',
                'Current Stock',
                'Reorder Threshold',
                'Stock Status',
                'Stock-In Deliveries (Period)',
                'Waste & Spoilage (Period)',
                'Net Recipe Sales Usage (Period)',
                'Net Add-On Sales Usage (Period)',
                'Unattributed Sales Usage (Period)',
                'Net Total Sales Consumption (Period)',
            ];

            $rows = [];
            foreach ($allIngredients as $ingredient) {
                $status = match ($ingredient->getStockStatus()) {
                    'good' => 'Good Standing',
                    'low_stock' => 'Low Stock Warning',
                    'out_of_stock' => 'Out of Stock',
                    default => 'Good Standing',
                };

                $cons = $consumptionMap->get($ingredient->id);
                $recipeUsage = $cons ? (float) $cons->recipe_consumed : 0.0;
                $addonUsage = $cons ? (float) $cons->addon_consumed : 0.0;
                $unattributedUsage = $cons ? (float) $cons->unattributed_consumed : 0.0;
                $totalCons = $cons ? (float) $cons->total_consumed : 0.0;
                $stockInQty = (float) ($stockInMap[$ingredient->id] ?? 0);
                $wasteQty = (float) ($wasteMap[$ingredient->id] ?? 0);

                $rows[] = [
                    $ingredient->id,
                    $ingredient->name,
                    $ingredient->unit,
                    number_format($ingredient->getCurrentStock(), 3, '.', ''),
                    number_format($ingredient->getReorderThreshold(), 3, '.', ''),
                    $status,
                    number_format($stockInQty, 3, '.', ''),
                    number_format($wasteQty, 3, '.', ''),
                    number_format($recipeUsage, 3, '.', ''),
                    number_format($addonUsage, 3, '.', ''),
                    number_format($unattributedUsage, 3, '.', ''),
                    number_format($totalCons, 3, '.', ''),
                ];
            }

            return ExportService::streamCsv("inventory-report-audit-{$from}-to-{$to}.csv", $headers, $rows);
        }

        return view('reports.inventory', compact(
            'from', 'to', 'allIngredients', 'lowStock', 'outOfStock',
            'stockIns', 'wasteRecords', 'salesConsumption', 'consumptionTotals', 'user'
        ));
    }

    public function grab(Request $request)
    {
        $query = Order::with([
            'orderItems.product',
            'orderItems.productSize',
            'payments' => fn ($payments) => $payments->where('status', 'paid'),
        ])
            ->where('order_type', 'grab')
            ->orderByDesc('created_at');

        if ($from = $request->get('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->whereDate('created_at', '<=', $to);
        }
        if ($code = $request->get('grab_order_code')) {
            $query->where('grab_order_code', 'like', "%{$code}%");
        }
        if ($rider = $request->get('rider_code')) {
            $query->where('rider_code', 'like', "%{$rider}%");
        }
        if ($cashier = $request->get('cashier_name')) {
            $query->where('cashier_name', 'like', "%{$cashier}%");
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($productId = $request->get('product_id')) {
            $query->whereHas('orderItems', fn ($q) => $q->where('product_id', $productId));
        }

        // Summary totals
        $summaryQuery = clone $query;
        $completedSummary = (clone $summaryQuery)->whereIn('status', ['completed', 'partially_paid', 'pay_later']);
        $totalGrabSales = $completedSummary->sum('total');
        $totalGrabOrders = $summaryQuery->count();
        $completedCount = $completedSummary->count();

        if ($request->get('export') === 'excel') {
            abort_if(! $request->user()?->canExportOrPrint(), 403, 'Only managers and owners can export reports.');
            $filename = 'grab-orders-report-'.now()->format('Y-m-d').'.csv';
            $headers = [
                'Order #',
                'Grab Order Code',
                'Rider Code',
                'Customer Name',
                'Date & Time',
                'Cashier',
                'Payment Method',
                'Items Summary',
                'Subtotal',
                'Discount',
                'Total',
                'Status',
            ];

            $rows = $query->get()->map(function ($ord) {
                $itemsText = $ord->orderItems->map(fn ($it) => "{$it->product?->name} ({$it->productSize?->size_name}) x{$it->quantity}".($it->comment ? " [{$it->comment}]" : ''))->join('; ');
                $paymentMethods = $ord->payments
                    ->pluck('method')
                    ->unique()
                    ->map(fn ($method) => match (strtolower($method)) {
                        'cash' => 'Cash',
                        'grabfood', 'grab' => 'GrabFood',
                        'online' => 'Online Payment',
                        'pay_later' => 'Pay Later',
                        default => ucfirst($method),
                    })
                    ->join(', ');

                return [
                    $ord->order_number,
                    $ord->grab_order_code ?? '—',
                    $ord->rider_code ?? '—',
                    $ord->customer_name ?? '—',
                    $ord->created_at ? $ord->created_at->format('Y-m-d H:i:s') : '',
                    $ord->cashier_name,
                    $paymentMethods ?: '—',
                    $itemsText,
                    number_format((float) $ord->subtotal, 2, '.', ''),
                    number_format((float) $ord->discount, 2, '.', ''),
                    number_format((float) $ord->total, 2, '.', ''),
                    ucfirst($ord->status),
                ];
            });

            return ExportService::streamCsv($filename, $headers, $rows);
        }

        $orders = $request->get('print') === 'all'
            ? (abort_if(! $request->user()?->canExportOrPrint(), 403, 'Only managers and owners can print reports.') ?: $query->get())
            : $query->paginate(20)->withQueryString();

        $products = Product::orderBy('name')->get();
        $cashiers = Order::where('order_type', 'grab')->distinct()->pluck('cashier_name');

        return view('reports.grab', compact('orders', 'totalGrabSales', 'totalGrabOrders', 'completedCount', 'products', 'cashiers'));
    }

    public function shifts(Request $request)
    {
        $query = CashierShift::with('user')->orderByDesc('start_time');

        if ($from = $request->get('from')) {
            $query->whereDate('shift_date', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->whereDate('shift_date', '<=', $to);
        }
        if ($cashier = $request->get('cashier_name')) {
            $query->where('cashier_name', 'like', "%{$cashier}%");
        }
        if ($status = $request->get('status')) {
            if ($status === 'balanced') {
                $query->whereIn('status', ['closed', 'reviewed'])->where('difference', 0);
            } elseif ($status === 'short') {
                $query->whereIn('status', ['closed', 'reviewed'])->where('difference', '<', 0);
            } elseif ($status === 'over') {
                $query->whereIn('status', ['closed', 'reviewed'])->where('difference', '>', 0);
            } else {
                $query->where('status', $status);
            }
        }

        $summaryQuery = clone $query;
        $totalShifts = $summaryQuery->count();
        $totalCashSales = (clone $summaryQuery)->whereIn('status', ['closed', 'reviewed'])->sum('cash_sales');
        $totalVariance = (clone $summaryQuery)->whereIn('status', ['closed', 'reviewed'])->sum('difference');
        $shortageByCashier = (clone $summaryQuery)
            ->whereIn('status', ['closed', 'reviewed'])
            ->where('difference', '<', 0)
            ->select('cashier_name')
            ->selectRaw('SUM(ABS(difference)) as total_shortage')
            ->groupBy('cashier_name')
            ->orderByDesc('total_shortage')
            ->get();

        if ($request->get('export') === 'excel') {
            abort_if(! $request->user()?->canExportOrPrint(), 403, 'Only managers and owners can export reports.');
            $filename = 'cashier-shifts-report-'.now()->format('Y-m-d').'.csv';
            $headers = [
                'Shift ID',
                'Cashier',
                'Shift Date',
                'Start Time',
                'End Time',
                'Beginning Cash',
                'Cash Sales',
                'Debt Collections (Cash)',
                'Cash Refunds',
                'Cash Voids',
                'Online Sales',
                'Debt Collections (Online)',
                'Grab Sales',
                'Grab Settlements',
                'Pay Later Charged',
                'Dine-in Sales',
                'Take-out Sales',
                'Void Count',
                'Void Amount',
                'Expected Cash',
                'Actual Cash',
                'Difference',
                'Notes',
                'Status',
            ];

            $rows = $query->get()->map(function ($shift) {
                $shift = $this->withLiveCashSummary($shift);

                return [
                    $shift->id,
                    $shift->cashier_name,
                    $shift->shift_date ? $shift->shift_date->format('Y-m-d') : '',
                    $shift->localStartTime()?->format('Y-m-d H:i:s') ?? '',
                    $shift->localEndTime()?->format('Y-m-d H:i:s') ?? 'In Progress',
                    number_format((float) $shift->beginning_cash, 2, '.', ''),
                    number_format((float) $shift->cash_sales, 2, '.', ''),
                    number_format((float) $shift->debt_cash_collections, 2, '.', ''),
                    number_format((float) $shift->cash_refunds, 2, '.', ''),
                    number_format((float) $shift->cash_voids, 2, '.', ''),
                    number_format((float) $shift->online_sales, 2, '.', ''),
                    number_format((float) $shift->debt_online_collections, 2, '.', ''),
                    number_format((float) $shift->grab_sales, 2, '.', ''),
                    number_format((float) $shift->grab_settlements, 2, '.', ''),
                    number_format((float) $shift->pay_later_charged, 2, '.', ''),
                    number_format((float) $shift->dine_in_sales, 2, '.', ''),
                    number_format((float) $shift->take_out_sales, 2, '.', ''),
                    $shift->void_count,
                    number_format((float) $shift->void_amount, 2, '.', ''),
                    number_format((float) $shift->expected_cash, 2, '.', ''),
                    $shift->actual_cash !== null ? number_format((float) $shift->actual_cash, 2, '.', '') : '—',
                    $shift->difference !== null ? number_format((float) $shift->difference, 2, '.', '') : '—',
                    $shift->notes ?? '',
                    ucfirst($shift->status),
                ];
            });

            return ExportService::streamCsv($filename, $headers, $rows);
        }

        $shifts = $request->get('print') === 'all'
            ? (abort_if(! $request->user()?->canExportOrPrint(), 403, 'Only managers and owners can print reports.') ?: $query->get())
            : $query->paginate(20)->withQueryString();
        if ($shifts instanceof LengthAwarePaginator) {
            $shifts->setCollection($shifts->getCollection()->map(fn ($shift) => $this->withLiveCashSummary($shift)));
        } else {
            $shifts = $shifts->map(fn ($shift) => $this->withLiveCashSummary($shift));
        }

        $cashiers = CashierShift::distinct()->pluck('cashier_name');

        return view('reports.shifts', compact('shifts', 'totalShifts', 'totalCashSales', 'totalVariance', 'shortageByCashier', 'cashiers'));
    }

    private function withLiveCashSummary(CashierShift $shift): CashierShift
    {
        if (! $shift->isOpen()) {
            return $shift;
        }

        $summary = $shift->cashSummary();
        $shift->setAttribute('cash_sales', $summary['cash_sales']);
        $shift->setAttribute('debt_cash_collections', $summary['debt_cash_collections']);
        $shift->setAttribute('cash_refunds', $summary['cash_refunds']);
        $shift->setAttribute('cash_voids', $summary['cash_voids']);
        $shift->setAttribute('online_sales', $summary['online_sales']);
        $shift->setAttribute('debt_online_collections', $summary['debt_online_collections']);
        $shift->setAttribute('grab_sales', $summary['grab_sales']);
        $shift->setAttribute('grab_settlements', $summary['grab_settlements']);
        $shift->setAttribute('pay_later_charged', $summary['pay_later_charged']);
        $shift->setAttribute('void_count', $summary['void_count']);
        $shift->setAttribute('void_amount', $summary['void_amount']);
        $shift->setAttribute('dine_in_sales', $summary['dine_in_sales']);
        $shift->setAttribute('take_out_sales', $summary['take_out_sales']);
        $shift->setAttribute('expected_cash', round(
            (float) $shift->beginning_cash
            + $summary['cash_sales']
            + $summary['debt_cash_collections']
            - $summary['cash_refunds']
            - $summary['cash_voids'],
            2
        ));

        return $shift;
    }

    public function shiftDetail(CashierShift $shift)
    {
        $summary = $shift->cashSummary();
        $expectedCash = round(
            (float) $shift->beginning_cash
            + $summary['cash_sales']
            + $summary['debt_cash_collections']
            - $summary['cash_refunds']
            - $summary['cash_voids'],
            2
        );
        $orders = $shift->orders()
            ->with(['payments', 'refunds', 'voidLogs', 'orderItems.product', 'orderItems.size'])
            ->latest()
            ->get();
        $payments = $shift->payments()->with('order')->latest()->get();
        $debtPayments = DebtPayment::where('shift_id', $shift->id)->with('debt.order')->latest()->get();
        $refunds = Refund::where('shift_id', $shift->id)->with('order')->latest()->get();
        $voidLogs = VoidLog::where('shift_id', $shift->id)->with('order', 'orderItem.product')->latest('voided_at')->get();
        $adjustments = $shift->cashMovements()->with('recordedBy')->latest()->get();

        return view('reports.shift-show', compact(
            'shift',
            'summary',
            'expectedCash',
            'orders',
            'payments',
            'debtPayments',
            'refunds',
            'voidLogs',
            'adjustments',
        ));
    }

    private function resolveDateRange(string $type, string $date, ?string $from, ?string $to): array
    {
        return match ($type) {
            'daily' => [$date, $date],
            'weekly' => [now()->parse($date)->startOfWeek()->toDateString(),  now()->parse($date)->endOfWeek()->toDateString()],
            'monthly' => [now()->parse($date)->startOfMonth()->toDateString(), now()->parse($date)->endOfMonth()->toDateString()],
            'yearly' => [now()->parse($date)->startOfYear()->toDateString(),  now()->parse($date)->endOfYear()->toDateString()],
            'custom' => [$from ?? $date, $to ?? $date],
            default => ['2000-01-01', now()->toDateString()],
        };
    }
}
