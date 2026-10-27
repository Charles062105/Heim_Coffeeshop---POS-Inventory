<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderAdjustment;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Refund;
use App\Models\VoidLog;
use App\Services\OrderBalanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user() ?? auth()->user();
        $userRole = $user?->role ?? 'guest';
        $userName = $user?->name ?? 'User';
        $cashierShift = $userRole === 'cashier' ? CashierShift::activeForUser($user?->id ?? 0) : null;
        $businessNow = now(config('app.business_timezone', 'Asia/Manila'));
        $todayStartUtc = $businessNow->copy()->startOfDay()->utc();
        $tomorrowStartUtc = $businessNow->copy()->addDay()->startOfDay()->utc();
        $saleStatuses = ['completed', 'partially_paid'];
        $paymentOrderStatuses = [...$saleStatuses, 'refunded'];
        $paymentStatuses = ['paid', 'refunded'];
        $collectedPayments = fn () => Payment::whereIn('status', $paymentStatuses)
            ->whereHas('order', fn ($query) => $query->whereIn('status', $paymentOrderStatuses));
        $cashierOrderScope = function ($query) use ($userName, $cashierShift) {
            $query->where(function ($inner) use ($userName, $cashierShift) {
                $inner->where('cashier_name', $userName);

                if ($cashierShift) {
                    $inner->orWhere('shift_id', $cashierShift->id);
                }
            });
        };
        $dashboardPayments = function () use ($collectedPayments, $userRole, $cashierOrderScope) {
            $query = $collectedPayments();
            if ($userRole === 'cashier') {
                $query->whereHas('order', fn ($orders) => $orders->where($cashierOrderScope));
            }

            return $query;
        };
        $dashboardOrders = function () use ($paymentOrderStatuses, $userRole, $cashierOrderScope) {
            $query = Order::whereIn('status', $paymentOrderStatuses);
            if ($userRole === 'cashier') {
                $query->where($cashierOrderScope);
            }

            return $query;
        };

        // ── Top KPIs ─────────────────────────────────────────────────────────────
        // Collected Revenue: Actual cash payments received (completed + partial + refunded order statuses)
        $todaySales = (float) $dashboardPayments()
            ->where('created_at', '>=', $todayStartUtc)
            ->where('created_at', '<', $tomorrowStartUtc)
            ->sum('amount_paid');

        // Total Paid Orders: Count of orders with recorded payments today
        $todayOrders = $dashboardOrders()
            ->whereHas('payments', fn ($query) => $query->whereIn('status', $paymentStatuses)
                ->where('created_at', '>=', $todayStartUtc)
                ->where('created_at', '<', $tomorrowStartUtc))
            ->count();

        // Total Orders: All orders in completed/partially paid status
        $totalOrders = $dashboardOrders()->count();

        // Total Collected Revenue: Sum of all paid payments from completed/partially paid/refunded orders
        $totalRevenue = (float) $dashboardPayments()->sum('amount_paid');

        $openOrderBalances = app(OrderBalanceService::class)->summarizeOpenBalances(
            Order::query()->when($userRole === 'cashier', fn ($query) => $query->where($cashierOrderScope))
        );
        $pendingOrders = (int) $openOrderBalances->sum('order_count');
        $pendingOrderCount = (int) ($openOrderBalances->get('pending')->order_count ?? 0);
        $pendingOrderBalance = (float) ($openOrderBalances->get('pending')->balance_due ?? 0);
        $partiallyPaidOrderCount = (int) ($openOrderBalances->get('partially_paid')->order_count ?? 0);
        $partiallyPaidOrderBalance = (float) ($openOrderBalances->get('partially_paid')->balance_due ?? 0);
        $orderStatusLabels = [
            'held' => 'Held',
            'pending' => 'Pending',
            'partially_paid' => 'Partially Paid',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            'voided' => 'Voided',
        ];
        $orderStatusCounts = Order::query()
            ->where('created_at', '>=', $todayStartUtc)
            ->where('created_at', '<', $tomorrowStartUtc)
            ->when($userRole === 'cashier', fn ($query) => $query->where($cashierOrderScope))
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');
        $orderStatusBreakdown = collect($orderStatusLabels)->map(
            fn ($label, $status) => [
                'status' => $status,
                'label' => $label,
                'count' => (int) $orderStatusCounts->get($status, 0),
            ]
        )->values();

        $hourlyOrders = Order::query()
            ->where('created_at', '>=', $todayStartUtc)
            ->where('created_at', '<', $tomorrowStartUtc)
            ->where('status', '!=', 'held')
            ->when($userRole === 'cashier', fn ($query) => $query->where($cashierOrderScope))
            ->get(['created_at']);
        $hourlyActivity = collect(range(0, 11))->map(function ($bucket) use ($hourlyOrders, $businessNow) {
            $startHour = $bucket * 2;
            $orders = $hourlyOrders->filter(function ($order) use ($startHour) {
                $localHour = $order->created_at->copy()->timezone(config('app.business_timezone', 'Asia/Manila'))->hour;

                return $localHour >= $startHour && $localHour < $startHour + 2;
            })->count();
            $startLabel = Carbon::createFromTime($startHour, 0, 0, $businessNow->timezone)->format('g A');
            $endLabel = Carbon::createFromTime(($startHour + 2) % 24, 0, 0, $businessNow->timezone)->format('g A');

            return [
                'label' => $startLabel.'–'.$endLabel,
                'orders' => $orders,
            ];
        });

        $refundSummary = Refund::query()
            ->where('status', 'completed')
            ->whereRaw('COALESCE(refunded_at, created_at) >= ?', [$todayStartUtc])
            ->whereRaw('COALESCE(refunded_at, created_at) < ?', [$tomorrowStartUtc])
            ->whereHas('order', fn ($query) => $userRole === 'cashier' ? $query->where($cashierOrderScope) : $query)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount), 0) as total')
            ->first();
        $refundReasons = Refund::query()
            ->where('status', 'completed')
            ->whereRaw('COALESCE(refunded_at, created_at) >= ?', [$todayStartUtc])
            ->whereRaw('COALESCE(refunded_at, created_at) < ?', [$tomorrowStartUtc])
            ->whereHas('order', fn ($query) => $userRole === 'cashier' ? $query->where($cashierOrderScope) : $query)
            ->select('reason', DB::raw('COUNT(*) as count'))
            ->groupBy('reason')
            ->orderByDesc('count')
            ->limit(3)
            ->get();

        $cancellationSummary = Order::query()
            ->where('status', 'cancelled')
            ->where('created_at', '>=', $todayStartUtc)
            ->where('created_at', '<', $tomorrowStartUtc)
            ->when($userRole === 'cashier', fn ($query) => $query->where($cashierOrderScope))
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->first();
        $cancellationReasons = OrderAdjustment::query()
            ->where('action', 'cancel')
            ->where('created_at', '>=', $todayStartUtc)
            ->where('created_at', '<', $tomorrowStartUtc)
            ->whereHas('order', fn ($query) => $userRole === 'cashier' ? $query->where($cashierOrderScope) : $query)
            ->select('reason', DB::raw('COUNT(*) as count'))
            ->groupBy('reason')
            ->orderByDesc('count')
            ->limit(3)
            ->get();

        $voidSummary = VoidLog::query()
            ->where('void_type', 'order')
            ->where('voided_at', '>=', $todayStartUtc)
            ->where('voided_at', '<', $tomorrowStartUtc)
            ->whereHas('order', fn ($query) => $userRole === 'cashier' ? $query->where($cashierOrderScope) : $query)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount), 0) as total')
            ->first();
        $voidReasons = VoidLog::query()
            ->where('void_type', 'order')
            ->where('voided_at', '>=', $todayStartUtc)
            ->where('voided_at', '<', $tomorrowStartUtc)
            ->whereHas('order', fn ($query) => $userRole === 'cashier' ? $query->where($cashierOrderScope) : $query)
            ->select('reason', DB::raw('COUNT(*) as count'))
            ->groupBy('reason')
            ->orderByDesc('count')
            ->limit(3)
            ->get();

        // Cashier role-specific metrics
        $cashierTodaySales = 0;
        $cashierTodayOrders = 0;
        $cashierAllTimeSales = 0;
        $cashierAllTimeOrders = 0;

        if ($userRole === 'cashier') {
            $cashierTodaySales = (float) $dashboardPayments()
                ->where('created_at', '>=', $todayStartUtc)
                ->where('created_at', '<', $tomorrowStartUtc)
                ->sum('amount_paid');

            $cashierTodayOrders = $dashboardOrders()
                ->whereHas('payments', fn ($query) => $query->whereIn('status', $paymentStatuses)
                    ->where('created_at', '>=', $todayStartUtc)
                    ->where('created_at', '<', $tomorrowStartUtc))
                ->count();

            $cashierAllTimeSales = (float) $dashboardPayments()->sum('amount_paid');

            $cashierAllTimeOrders = $dashboardOrders()
                ->whereHas('payments', fn ($query) => $query->whereIn('status', $paymentStatuses))
                ->count();
        }

        // ── 7-Day Sales Overview Trend ──────────────────────────────────────────
        $salesTrend = collect(range(6, 0))->map(function ($dayOffset) use ($paymentStatuses, $businessNow, $dashboardPayments, $dashboardOrders) {
            $date = $businessNow->copy()->subDays($dayOffset);
            $dateStartUtc = $date->copy()->startOfDay()->utc();
            $nextDateStartUtc = $date->copy()->addDay()->startOfDay()->utc();

            return [
                'label' => $date->format('D'),
                'date' => $date->format('M j'),
                'day' => $date->toDateString(),
                'total' => (float) $dashboardPayments()
                    ->where('created_at', '>=', $dateStartUtc)
                    ->where('created_at', '<', $nextDateStartUtc)
                    ->sum('amount_paid'),
                'count' => $dashboardOrders()
                    ->whereHas('payments', fn ($query) => $query->whereIn('status', $paymentStatuses)
                        ->where('created_at', '>=', $dateStartUtc)
                        ->where('created_at', '<', $nextDateStartUtc))
                    ->count(),
            ];
        })->values();

        $weekTotal = (float) $salesTrend->sum('total');
        $weekOrders = (int) $salesTrend->sum('count');
        $weekAverage = $weekTotal / 7;
        $peakDay = $salesTrend->sortByDesc('total')->first();
        $weekStartUtc = $businessNow->copy()->subDays(6)->startOfDay()->utc();
        $previousWeekStartUtc = $businessNow->copy()->subDays(13)->startOfDay()->utc();
        $previousWeekTotal = (float) $dashboardPayments()
            ->where('created_at', '>=', $previousWeekStartUtc)
            ->where('created_at', '<', $weekStartUtc)
            ->sum('amount_paid');
        $previousWeekOrders = $dashboardOrders()
            ->whereHas('payments', fn ($query) => $query->whereIn('status', $paymentStatuses)
                ->where('created_at', '>=', $previousWeekStartUtc)
                ->where('created_at', '<', $weekStartUtc))
            ->count();
        $weekOrderTotals = Order::query()
            ->whereIn('status', $saleStatuses)
            ->where('created_at', '>=', $weekStartUtc)
            ->where('created_at', '<', $tomorrowStartUtc)
            ->when($userRole === 'cashier', fn ($query) => $query->where($cashierOrderScope))
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->first();
        $weekChangePercent = $previousWeekTotal > 0
            ? (($weekTotal - $previousWeekTotal) / $previousWeekTotal) * 100
            : null;
        $weekOrdersChangePercent = $previousWeekOrders > 0
            ? (float) (($weekOrders - $previousWeekOrders) / $previousWeekOrders) * 100
            : null;
        $averageOrderValue = $weekOrderTotals->count > 0
            ? (float) $weekOrderTotals->total / (int) $weekOrderTotals->count
            : 0;
        $averageTenderPerOrder = $weekOrders > 0
            ? $weekTotal / $weekOrders
            : 0;

        $peakDayProducts = collect();
        $peakDayCategories = collect();
        if ($peakDay && $peakDay['total'] > 0) {
            $peakDateStartUtc = Carbon::parse($peakDay['day'], $businessNow->timezone)->startOfDay()->utc();
            $peakDateEndUtc = Carbon::parse($peakDay['day'], $businessNow->timezone)->addDay()->startOfDay()->utc();
            $peakDayOrders = Order::whereIn('status', $saleStatuses)
                ->whereHas('payments', fn ($query) => $query->whereIn('status', $paymentStatuses)
                    ->where('created_at', '>=', $peakDateStartUtc)
                    ->where('created_at', '<', $peakDateEndUtc));
            if ($userRole === 'cashier') {
                $peakDayOrders->where($cashierOrderScope);
            }
            $peakDayOrderIds = $peakDayOrders->select('id');

            $peakDayProducts = OrderItem::where('order_items.status', 'active')
                ->whereIn('order_items.order_id', $peakDayOrderIds)
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->select('products.name', DB::raw('SUM(order_items.quantity) as sold'), DB::raw('SUM(order_items.subtotal) as revenue'))
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('revenue')
                ->orderBy('products.name')
                ->limit(5)
                ->get();

            $peakDayCategories = OrderItem::where('order_items.status', 'active')
                ->whereIn('order_items.order_id', $peakDayOrderIds)
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->selectRaw("COALESCE(categories.name, 'Uncategorized') as name")
                ->selectRaw('SUM(order_items.quantity) as sold, SUM(order_items.subtotal) as revenue')
                ->groupBy('categories.id', 'categories.name')
                ->orderByDesc('revenue')
                ->orderBy('name')
                ->limit(5)
                ->get();
        }

        // ── Top Products ────────────────────────────────────────────────────────
        $productSales = OrderItem::where('order_items.status', 'active')
            ->whereHas('order', function ($query) use ($saleStatuses, $userRole, $cashierOrderScope) {
                $query->whereIn('status', $saleStatuses);
                if ($userRole === 'cashier') {
                    $query->where($cashierOrderScope);
                }
            })
            ->select('product_id', DB::raw('SUM(quantity) as sold'))
            ->groupBy('product_id');

        $topProducts = Product::query()
            ->joinSub(clone $productSales, 'product_sales', 'product_sales.product_id', '=', 'products.id')
            ->select('products.name', 'product_sales.sold')
            ->orderByDesc('product_sales.sold')
            ->orderBy('products.name')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'sold' => (int) $item->sold,
                ];
            });

        $leastProducts = Product::query()
            ->where('products.status', 'active')
            ->leftJoinSub(clone $productSales, 'product_sales', 'product_sales.product_id', '=', 'products.id')
            ->select('products.name', DB::raw('COALESCE(product_sales.sold, 0) as sold'))
            ->orderBy('sold')
            ->orderBy('products.name')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'sold' => (int) $item->sold,
                ];
            });

        // ── Payment Breakdown ───────────────────────────────────────────────────
        $todayPayments = $dashboardPayments()
            ->where('created_at', '>=', $todayStartUtc)
            ->where('created_at', '<', $tomorrowStartUtc)
            ->select(
                DB::raw('LOWER(method) as method'),
                DB::raw('SUM(amount_paid) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw('LOWER(method)'))
            ->orderByDesc('total')
            ->get();

        $allTimePayments = $dashboardPayments()
            ->select(
                DB::raw('LOWER(method) as method'),
                DB::raw('SUM(amount_paid) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw('LOWER(method)'))
            ->orderByDesc('total')
            ->get();

        $isTodayPaymentsEmpty = $todayPayments->isEmpty();
        $paymentSummary = $isTodayPaymentsEmpty ? $allTimePayments : $todayPayments;
        $paymentTotal = (float) $paymentSummary->sum('total');

        // ── Stock Health & Inventory Alerts ─────────────────────────────────────
        $lowStockCount = 0;
        $outOfStockCount = 0;
        $lowStockItems = collect();
        $inventoryAlerts = collect();

        if (in_array($userRole, ['owner', 'manager'])) {
            $branchId = $user?->branch_id
                ?? Branch::where('is_active', true)->orderBy('id')->value('id');
            $allIngredients = Ingredient::with([
                'inventory' => fn ($query) => $query->where('branch_id', $branchId),
            ])->active()->get();
            $lowStockItems = $allIngredients->filter(fn ($i) => in_array($i->getStockStatus(), ['low_stock', 'out_of_stock']));
            $lowStockCount = $allIngredients->filter(fn ($i) => $i->getStockStatus() === 'low_stock')->count();
            $outOfStockCount = $allIngredients->filter(fn ($i) => $i->getStockStatus() === 'out_of_stock')->count();

            // Out of stock first, then low stock
            $inventoryAlerts = $lowStockItems->sortBy(fn ($i) => $i->getStockStatus() === 'out_of_stock' ? 0 : 1)->values();
        }

        // ── Daily Consumption ───────────────────────────────────────────────────
        $todayConsumption = collect();
        if (in_array($userRole, ['owner', 'manager'])) {
            $todayConsumption = InventoryTransaction::whereIn('type', ['sales_consumption', 'sales_return'])
                ->where('created_at', '>=', $todayStartUtc)
                ->where('created_at', '<', $tomorrowStartUtc)
                ->with('ingredient')
                ->select('ingredient_id', DB::raw("SUM(CASE WHEN type = 'sales_return' THEN -quantity ELSE quantity END) as total_consumed"))
                ->groupBy('ingredient_id')
                ->orderByDesc('total_consumed')
                ->limit(6)
                ->get()
                ->map(function ($tx) {
                    return (object) [
                        'name' => $tx->ingredient?->name ?? 'Ingredient',
                        'consumed' => (float) $tx->total_consumed,
                        'unit' => $tx->ingredient?->unit ?? 'unit',
                        'current_stock' => $tx->ingredient ? $tx->ingredient->getCurrentStock() : 0,
                    ];
                });
        }

        // ── Recent Orders ───────────────────────────────────────────────────────
        $ordersQuery = Order::with('payment');
        if ($userRole === 'cashier') {
            $recentOrders = (clone $ordersQuery)->where($cashierOrderScope)->latest()->limit(6)->get();
        } else {
            $recentOrders = $ordersQuery->latest()->limit(6)->get();
        }

        $recentTransactions = InventoryTransaction::with('ingredient')
            ->latest()
            ->limit(5)
            ->get();

        // ── Recent Activity (Individual Chronological Audit Logs) ────────────────
        $recentLogs = collect();
        if (in_array($userRole, ['owner', 'manager'])) {
            $recentLogs = AuditLog::latest()
                ->limit(6)
                ->get()
                ->map(function ($log) use ($businessNow) {
                    $actionName = match ($log->action) {
                        'login' => 'User Login',
                        'logout' => 'Logged out',
                        'order_created' => 'Completed Order',
                        'refund_processed' => 'Processed Refund',
                        'stock_in' => 'Stock-In Replenishment',
                        'stock_adjustment' => 'Inventory Adjustment',
                        'waste_logged' => 'Recorded Waste',
                        'report_inventory_viewed' => 'Generated Inventory Report',
                        'report_sales_viewed' => 'Generated Sales Report',
                        default => ucwords(str_replace('_', ' ', $log->action)),
                    };

                    $businessLogTime = $log->created_at?->copy()->timezone(config('app.business_timezone', 'Asia/Manila'));
                    $timeStr = $businessLogTime ? (
                        $businessLogTime->isSameDay($businessNow)
                            ? 'Today, '.$businessLogTime->format('g:i A')
                            : ($businessLogTime->isSameDay($businessNow->copy()->subDay())
                                ? 'Yesterday, '.$businessLogTime->format('g:i A')
                                : $businessLogTime->format('M j, g:i A'))
                    ) : 'Recently';

                    return (object) [
                        'actor_name' => $log->actor_name ?? 'Staff',
                        'actor_role' => $log->actor_role ?? '',
                        'action' => $actionName,
                        'details' => $log->details,
                        'time_str' => $timeStr,
                        'created_at' => $log->created_at,
                    ];
                });
        }

        $unreadNotifications = 0;
        if (in_array($userRole, ['owner', 'manager'])) {
            $unreadNotifications = Notification::forRole($userRole)
                ->where('is_resolved', false)
                ->unreadForUser($user)
                ->count();
        }

        return view('dashboard.index', compact(
            'user',
            'todaySales',
            'todayOrders',
            'totalOrders',
            'totalRevenue',
            'pendingOrders',
            'pendingOrderCount',
            'pendingOrderBalance',
            'partiallyPaidOrderCount',
            'partiallyPaidOrderBalance',
            'orderStatusBreakdown',
            'hourlyActivity',
            'refundSummary',
            'refundReasons',
            'cancellationSummary',
            'cancellationReasons',
            'voidSummary',
            'voidReasons',
            'cashierTodaySales',
            'cashierTodayOrders',
            'cashierAllTimeSales',
            'cashierAllTimeOrders',
            'salesTrend',
            'weekTotal',
            'weekOrders',
            'weekAverage',
            'previousWeekTotal',
            'previousWeekOrders',
            'weekChangePercent',
            'weekOrdersChangePercent',
            'averageOrderValue',
            'averageTenderPerOrder',
            'peakDay',
            'peakDayProducts',
            'peakDayCategories',
            'topProducts',
            'leastProducts',
            'paymentSummary',
            'paymentTotal',
            'isTodayPaymentsEmpty',
            'lowStockCount',
            'outOfStockCount',
            'lowStockItems',
            'inventoryAlerts',
            'todayConsumption',
            'recentOrders',
            'recentTransactions',
            'recentLogs',
            'unreadNotifications'
        ));
    }
}
