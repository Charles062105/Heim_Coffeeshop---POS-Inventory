<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user() ?? auth()->user();
        $userRole = $user?->role ?? 'guest';
        $userName = $user?->name ?? 'User';
        $today = now()->toDateString();
        $saleStatuses = ['completed', 'partially_paid', 'pay_later'];

        // ── Top KPIs ─────────────────────────────────────────────────────────────
        $todaySales = (float) Order::whereIn('status', $saleStatuses)
            ->whereDate('created_at', $today)
            ->sum('total');

        $todayOrders = Order::whereIn('status', $saleStatuses)
            ->whereDate('created_at', $today)
            ->count();

        $totalOrders = Order::whereIn('status', [...$saleStatuses, 'refunded'])->count();
        $totalRevenue = (float) Order::whereIn('status', $saleStatuses)->sum('total');

        // Cashier role-specific metrics
        $cashierTodaySales = 0;
        $cashierTodayOrders = 0;
        $cashierAllTimeSales = 0;
        $cashierAllTimeOrders = 0;

        if ($userRole === 'cashier') {
            $cashierTodaySales = (float) Order::whereIn('status', $saleStatuses)
                ->where('cashier_name', $userName)
                ->whereDate('created_at', $today)
                ->sum('total');

            $cashierTodayOrders = Order::whereIn('status', $saleStatuses)
                ->where('cashier_name', $userName)
                ->whereDate('created_at', $today)
                ->count();

            $cashierAllTimeSales = (float) Order::whereIn('status', $saleStatuses)
                ->where('cashier_name', $userName)
                ->sum('total');

            $cashierAllTimeOrders = Order::whereIn('status', $saleStatuses)
                ->where('cashier_name', $userName)
                ->count();
        }

        // ── 7-Day Sales Overview Trend ──────────────────────────────────────────
        $salesTrend = collect(range(6, 0))->map(function ($dayOffset) use ($saleStatuses) {
            $date = now()->subDays($dayOffset);

            return [
                'label' => $date->format('D'),
                'date' => $date->format('M j'),
                'total' => (float) Order::whereIn('status', $saleStatuses)
                    ->whereDate('created_at', $date->toDateString())
                    ->sum('total'),
                'count' => Order::whereIn('status', $saleStatuses)
                    ->whereDate('created_at', $date->toDateString())
                    ->count(),
            ];
        })->values();

        $weekTotal = (float) $salesTrend->sum('total');
        $weekOrders = (int) $salesTrend->sum('count');
        $weekAverage = $weekTotal / 7;
        $peakDay = $salesTrend->sortByDesc('total')->first();

        // ── Top Products ────────────────────────────────────────────────────────
        $topProducts = OrderItem::where('order_items.status', 'active')
            ->whereHas('order', fn ($q) => $q->whereIn('status', $saleStatuses))
            ->with('product')
            ->select('product_id', DB::raw('SUM(quantity) as sold'))
            ->groupBy('product_id')
            ->orderByDesc('sold')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->product?->name ?? 'Unknown Product',
                    'sold' => (int) $item->sold,
                ];
            });

        // ── Payment Breakdown ───────────────────────────────────────────────────
        $todayPayments = Payment::where('status', 'paid')
            ->whereHas('order', fn ($q) => $q->whereIn('status', $saleStatuses)->whereDate('created_at', $today))
            ->select(
                DB::raw("CASE WHEN LOWER(method) = 'cash' THEN 'cash' ELSE 'online' END as method"),
                DB::raw('SUM(amount_paid) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw("CASE WHEN LOWER(method) = 'cash' THEN 'cash' ELSE 'online' END"))
            ->orderByDesc('total')
            ->get();

        $allTimePayments = Payment::where('status', 'paid')
            ->whereHas('order', fn ($q) => $q->whereIn('status', $saleStatuses))
            ->select(
                DB::raw("CASE WHEN LOWER(method) = 'cash' THEN 'cash' ELSE 'online' END as method"),
                DB::raw('SUM(amount_paid) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw("CASE WHEN LOWER(method) = 'cash' THEN 'cash' ELSE 'online' END"))
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
            $allIngredients = Ingredient::with('inventory')->active()->get();
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
                ->whereDate('created_at', $today)
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
            $recentOrders = (clone $ordersQuery)->where('cashier_name', $userName)->latest()->limit(6)->get();
            if ($recentOrders->isEmpty()) {
                $recentOrders = $ordersQuery->latest()->limit(6)->get();
            }
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
                ->map(function ($log) {
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

                    $timeStr = $log->created_at ? (
                        $log->created_at->isToday()
                            ? 'Today, '.$log->created_at->format('g:i A')
                            : ($log->created_at->isYesterday()
                                ? 'Yesterday, '.$log->created_at->format('g:i A')
                                : $log->created_at->format('M j, g:i A'))
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
            $unreadNotifications = Notification::where('is_resolved', false)
                ->whereNull('read_at')
                ->count();
        }

        return view('dashboard.index', compact(
            'user',
            'todaySales',
            'todayOrders',
            'totalOrders',
            'totalRevenue',
            'cashierTodaySales',
            'cashierTodayOrders',
            'cashierAllTimeSales',
            'cashierAllTimeOrders',
            'salesTrend',
            'weekTotal',
            'weekOrders',
            'weekAverage',
            'peakDay',
            'topProducts',
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
