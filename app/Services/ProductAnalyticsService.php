<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductAction;
use App\Models\Refund;
use App\Models\VoidLog;
use App\Support\BusinessDateRange;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductAnalyticsService
{
    public function resolveDateWindows(string $type, string $date, ?string $from = null, ?string $to = null): array
    {
        $timezone = config('app.business_timezone', 'Asia/Manila');
        $anchor = CarbonImmutable::parse($date, $timezone);

        [$startDate, $endDate, $prevStartDate, $prevEndDate, $label] = match ($type) {
            'daily' => [
                $anchor->toDateString(),
                $anchor->toDateString(),
                $anchor->subDay()->toDateString(),
                $anchor->subDay()->toDateString(),
                $anchor->format('M j, Y').' vs. '.$anchor->subDay()->format('M j, Y'),
            ],
            'weekly' => [
                $anchor->startOfWeek()->toDateString(),
                $anchor->endOfWeek()->toDateString(),
                $anchor->subWeek()->startOfWeek()->toDateString(),
                $anchor->subWeek()->endOfWeek()->toDateString(),
                $anchor->startOfWeek()->format('M j').'–'.$anchor->endOfWeek()->format('M j, Y').' vs. '.$anchor->subWeek()->startOfWeek()->format('M j').'–'.$anchor->subWeek()->endOfWeek()->format('M j, Y'),
            ],
            'monthly' => [
                $anchor->startOfMonth()->toDateString(),
                $anchor->endOfMonth()->toDateString(),
                $anchor->subMonth()->startOfMonth()->toDateString(),
                $anchor->subMonth()->endOfMonth()->toDateString(),
                $anchor->format('F Y').' vs. '.$anchor->subMonth()->format('F Y'),
            ],
            'yearly' => [
                $anchor->startOfYear()->toDateString(),
                $anchor->endOfYear()->toDateString(),
                $anchor->subYear()->startOfYear()->toDateString(),
                $anchor->subYear()->endOfYear()->toDateString(),
                $anchor->format('Y').' vs. '.$anchor->subYear()->format('Y'),
            ],
            'custom' => (function () use ($from, $to, $anchor, $timezone) {
                $cFrom = CarbonImmutable::parse($from ?? $anchor->toDateString(), $timezone);
                $cTo = CarbonImmutable::parse($to ?? $anchor->toDateString(), $timezone);
                if ($cFrom->greaterThan($cTo)) {
                    $cTo = $cFrom;
                }
                $days = $cFrom->diffInDays($cTo) + 1;
                $pTo = $cFrom->subDay();
                $pFrom = $pTo->subDays($days - 1);

                return [
                    $cFrom->toDateString(),
                    $cTo->toDateString(),
                    $pFrom->toDateString(),
                    $pTo->toDateString(),
                    $cFrom->format('M j, Y').'–'.$cTo->format('M j, Y').' vs. '.$pFrom->format('M j, Y').'–'.$pTo->format('M j, Y'),
                ];
            })(),
            default => [
                '2000-01-01',
                now($timezone)->toDateString(),
                '1990-01-01',
                '1999-12-31',
                'All-Time Data',
            ],
        };

        $startCarbon = CarbonImmutable::parse($startDate, $timezone);
        $endCarbon = CarbonImmutable::parse($endDate, $timezone);
        $daysInPeriod = max(1, $startCarbon->diffInDays($endCarbon) + 1);

        return [
            'type' => $type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'prev_start_date' => $prevStartDate,
            'prev_end_date' => $prevEndDate,
            'label' => $label,
            'days_in_period' => $daysInPeriod,
            'start_utc' => BusinessDateRange::startUtc($startDate),
            'end_utc' => BusinessDateRange::endExclusiveUtc($endDate),
            'prev_start_utc' => BusinessDateRange::startUtc($prevStartDate),
            'prev_end_utc' => BusinessDateRange::endExclusiveUtc($prevEndDate),
        ];
    }

    public function getProductPerformance(array $filters, array $dateWindows, ?int $scopedBranchId = null): Collection
    {
        $saleStatuses = ['completed', 'partially_paid', 'refunded'];
        $branchId = $scopedBranchId ?? ($filters['branch_id'] ?? null);
        $channel = $filters['channel'] ?? 'all';
        $categoryId = $filters['category_id'] ?? null;
        $productId = $filters['product_id'] ?? null;
        $rankingBy = $filters['ranking_by'] ?? 'units';

        $applyOrderFilters = function ($query, CarbonImmutable $startUtc, CarbonImmutable $endUtc) use ($branchId, $channel, $saleStatuses) {
            $query->whereIn('orders.status', $saleStatuses)
                ->where('orders.created_at', '>=', $startUtc)
                ->where('orders.created_at', '<', $endUtc);

            if ($branchId) {
                $query->where('orders.branch_id', $branchId);
            }

            if ($channel === 'pos') {
                $query->where('orders.order_type', '!=', 'grab');
            } elseif ($channel === 'grab') {
                $query->where('orders.order_type', 'grab');
            }
        };

        // Query current period product sales
        $currentSalesQuery = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.status', 'active');
        $applyOrderFilters($currentSalesQuery, $dateWindows['start_utc'], $dateWindows['end_utc']);

        $currentSales = $currentSalesQuery
            ->select('order_items.product_id')
            ->selectRaw('SUM(order_items.quantity) as units_sold')
            ->selectRaw('SUM(order_items.subtotal) as net_revenue')
            ->selectRaw('COUNT(DISTINCT orders.id) as order_count')
            ->groupBy('order_items.product_id')
            ->get()
            ->keyBy('product_id');

        // Query previous period product sales
        $prevSalesQuery = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.status', 'active');
        $applyOrderFilters($prevSalesQuery, $dateWindows['prev_start_utc'], $dateWindows['prev_end_utc']);

        $prevSales = $prevSalesQuery
            ->select('order_items.product_id')
            ->selectRaw('SUM(order_items.quantity) as prev_units_sold')
            ->selectRaw('SUM(order_items.subtotal) as prev_net_revenue')
            ->groupBy('order_items.product_id')
            ->get()
            ->keyBy('product_id');

        // Load products with categories, sizes and recipes to assess ingredient health
        $productsQuery = Product::with(['category', 'sizes.recipe.recipeIngredients.ingredient.inventory'])
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($productId, fn ($q) => $q->where('id', $productId))
            ->where('status', 'active');

        $products = $productsQuery->get();

        $performance = $products->map(function (Product $product) use ($currentSales, $prevSales, $branchId) {
            $cur = $currentSales->get($product->id);
            $prev = $prevSales->get($product->id);

            $units = (int) ($cur->units_sold ?? 0);
            $revenue = (float) ($cur->net_revenue ?? 0.0);
            $orderCount = (int) ($cur->order_count ?? 0);

            $prevUnits = (int) ($prev->prev_units_sold ?? 0);
            $prevRevenue = (float) ($prev->prev_net_revenue ?? 0.0);

            $unitChange = $units - $prevUnits;
            $unitChangePercent = $prevUnits > 0
                ? round((($units - $prevUnits) / $prevUnits) * 100, 1)
                : ($units > 0 ? null : 0.0); // null means new or was 0 previous

            $revenueChange = round($revenue - $prevRevenue, 2);
            $revenueChangePercent = $prevRevenue > 0
                ? round((($revenue - $prevRevenue) / $prevRevenue) * 100, 1)
                : ($revenue > 0 ? null : 0.0);

            // Determine trend
            if ($units === 0) {
                $trend = 'zero_sales';
            } elseif ($prevUnits === 0) {
                $trend = 'increasing'; // newly selling
            } elseif ($unitChange > 0) {
                $trend = 'increasing';
            } elseif ($unitChange < 0) {
                $trend = 'decreasing';
            } else {
                $trend = 'stable';
            }

            // Inspect ingredient stock health for this product
            $lowStockIngredients = collect();
            $maxStockRatio = 0.0;
            foreach ($product->sizes as $size) {
                if (! $size->recipe) {
                    continue;
                }
                $recipe = $size->recipe;
                foreach ($recipe->recipeIngredients as $ri) {
                    $ing = $ri->ingredient;
                    if (! $ing) {
                        continue;
                    }
                    $inventoryQuery = DB::table('inventory')->where('ingredient_id', $ing->id);
                    if ($branchId) {
                        $inventoryQuery->where('branch_id', $branchId);
                    }
                    $currentStock = (float) ($inventoryQuery->sum('current_stock') ?? 0);
                    $minStock = (float) ($ing->minimum_stock ?? 0);

                    if ($minStock > 0 && $currentStock <= $minStock) {
                        $lowStockIngredients->push([
                            'id' => $ing->id,
                            'name' => $ing->name,
                            'unit' => $ing->unit,
                            'current_stock' => $currentStock,
                            'minimum_stock' => $minStock,
                        ]);
                    }
                    if ($minStock > 0) {
                        $ratio = $currentStock / $minStock;
                        if ($ratio > $maxStockRatio) {
                            $maxStockRatio = $ratio;
                        }
                    }
                }
            }
            $lowStockIngredients = $lowStockIngredients->unique('id')->values();

            return (object) [
                'id' => $product->id,
                'name' => $product->name,
                'category_id' => $product->category_id,
                'category_name' => $product->category?->name ?? 'Uncategorized',
                'units_sold' => $units,
                'net_revenue' => $revenue,
                'order_count' => $orderCount,
                'prev_units_sold' => $prevUnits,
                'prev_net_revenue' => $prevRevenue,
                'unit_change' => $unitChange,
                'unit_change_percent' => $unitChangePercent,
                'revenue_change' => $revenueChange,
                'revenue_change_percent' => $revenueChangePercent,
                'trend' => $trend,
                'has_low_stock' => $lowStockIngredients->isNotEmpty(),
                'low_stock_ingredients' => $lowStockIngredients,
                'max_stock_ratio' => $maxStockRatio,
            ];
        });

        // Sort collection based on ranking criteria
        return $rankingBy === 'revenue'
            ? $performance->sortByDesc('net_revenue')->values()
            : $performance->sortByDesc('units_sold')->values();
    }

    public function getBranchPerformanceSummary(array $filters, array $dateWindows, ?int $scopedBranchId = null): array
    {
        $saleStatuses = ['completed', 'partially_paid', 'refunded'];
        $selectedBranchId = $scopedBranchId ?? ($filters['branch_id'] ?? null);

        $branchesQuery = Branch::where('is_active', true);
        if ($selectedBranchId) {
            $branchesQuery->where('id', $selectedBranchId);
        }
        $branches = $branchesQuery->get();

        $branchRows = [];
        $totalConsolidatedRevenue = 0.0;
        $totalConsolidatedOrders = 0;
        $totalConsolidatedUnits = 0;
        $totalPrevRevenue = 0.0;

        foreach ($branches as $branch) {
            $ordersQuery = Order::where('branch_id', $branch->id)
                ->whereIn('status', $saleStatuses)
                ->where('created_at', '>=', $dateWindows['start_utc'])
                ->where('created_at', '<', $dateWindows['end_utc']);

            if (($filters['channel'] ?? 'all') === 'pos') {
                $ordersQuery->where('order_type', '!=', 'grab');
            } elseif (($filters['channel'] ?? 'all') === 'grab') {
                $ordersQuery->where('order_type', 'grab');
            }

            $branchRevenue = (float) $ordersQuery->sum('total');
            $branchOrders = (int) $ordersQuery->count();

            $branchUnits = (int) DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.branch_id', $branch->id)
                ->where('order_items.status', 'active')
                ->whereIn('orders.status', $saleStatuses)
                ->where('orders.created_at', '>=', $dateWindows['start_utc'])
                ->where('orders.created_at', '<', $dateWindows['end_utc'])
                ->sum('order_items.quantity');

            // Previous period branch revenue
            $prevOrdersQuery = Order::where('branch_id', $branch->id)
                ->whereIn('status', $saleStatuses)
                ->where('created_at', '>=', $dateWindows['prev_start_utc'])
                ->where('created_at', '<', $dateWindows['prev_end_utc']);
            $prevRevenue = (float) $prevOrdersQuery->sum('total');

            $growthPercent = $prevRevenue > 0
                ? round((($branchRevenue - $prevRevenue) / $prevRevenue) * 100, 1)
                : ($branchRevenue > 0 ? null : 0.0);

            // Open balances for this branch
            $openBalance = (float) DB::table('orders')
                ->where('branch_id', $branch->id)
                ->whereIn('status', ['pending', 'partially_paid'])
                ->where('created_at', '>=', $dateWindows['start_utc'])
                ->where('created_at', '<', $dateWindows['end_utc'])
                ->selectRaw('SUM(total - COALESCE((SELECT SUM(amount_paid) FROM payments WHERE payments.order_id = orders.id AND payments.status = "paid"), 0)) as balance')
                ->value('balance') ?? 0.0;

            $branchRows[] = [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'total_sales' => $branchRevenue,
                'total_orders' => $branchOrders,
                'units_sold' => $branchUnits,
                'prev_sales' => $prevRevenue,
                'growth_percent' => $growthPercent,
                'open_balance' => max(0, $openBalance),
            ];

            $totalConsolidatedRevenue += $branchRevenue;
            $totalConsolidatedOrders += $branchOrders;
            $totalConsolidatedUnits += $branchUnits;
            $totalPrevRevenue += $prevRevenue;
        }

        $overallGrowthPercent = $totalPrevRevenue > 0
            ? round((($totalConsolidatedRevenue - $totalPrevRevenue) / $totalPrevRevenue) * 100, 1)
            : ($totalConsolidatedRevenue > 0 ? null : 0.0);

        return [
            'branches' => collect($branchRows),
            'total_sales' => $totalConsolidatedRevenue,
            'total_orders' => $totalConsolidatedOrders,
            'total_units' => $totalConsolidatedUnits,
            'prev_total_sales' => $totalPrevRevenue,
            'growth_percent' => $overallGrowthPercent,
        ];
    }

    public function getFinancialReconciliationWarnings(array $filters, array $dateWindows, ?int $scopedBranchId = null): array
    {
        $branchId = $scopedBranchId ?? ($filters['branch_id'] ?? null);

        // 1. Outstanding open balances in period
        $openOrdersQuery = Order::whereIn('status', ['pending', 'partially_paid'])
            ->where('created_at', '>=', $dateWindows['start_utc'])
            ->where('created_at', '<', $dateWindows['end_utc'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $openOrderCount = $openOrdersQuery->count();
        $openBalanceTotal = 0.0;
        foreach ($openOrdersQuery->get() as $openOrder) {
            $openBalanceTotal += $openOrder->remainingBalance();
        }

        // 2. Cashier shift variances (actual_cash != expected_cash)
        $shiftVarianceQuery = CashierShift::whereIn('status', ['closed', 'reviewed'])
            ->where('shift_date', '>=', $dateWindows['start_date'])
            ->where('shift_date', '<=', $dateWindows['end_date'])
            ->whereRaw('ABS(difference) > 0.01')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $unbalancedShiftCount = $shiftVarianceQuery->count();
        $totalVarianceAmount = (float) $shiftVarianceQuery->sum('difference');

        // 3. Voids in period
        $voidQuery = VoidLog::where('voided_at', '>=', $dateWindows['start_utc'])
            ->where('voided_at', '<', $dateWindows['end_utc'])
            ->when($branchId, fn ($q) => $q->whereHas('order', fn ($oq) => $oq->where('branch_id', $branchId)));
        $voidCount = $voidQuery->count();
        $voidTotalAmount = (float) $voidQuery->sum('amount');

        // 4. Refunds in period
        $refundQuery = Refund::where('status', 'completed')
            ->where('refunded_at', '>=', $dateWindows['start_utc'])
            ->where('refunded_at', '<', $dateWindows['end_utc'])
            ->when($branchId, fn ($q) => $q->whereHas('order', fn ($oq) => $oq->where('branch_id', $branchId)));
        $refundCount = $refundQuery->count();
        $refundTotalAmount = (float) $refundQuery->sum('amount');

        $warnings = [];
        if ($openBalanceTotal > 0) {
            $warnings[] = [
                'type' => 'open_balance',
                'severity' => 'warning',
                'title' => 'Outstanding Uncollected Balances',
                'message' => "There are {$openOrderCount} pending/partially paid order(s) totaling ₱".number_format($openBalanceTotal, 2).' in outstanding receivables.',
                'metric' => '₱'.number_format($openBalanceTotal, 2),
            ];
        }
        if ($unbalancedShiftCount > 0) {
            $warnings[] = [
                'type' => 'shift_variance',
                'severity' => $totalVarianceAmount < 0 ? 'danger' : 'warning',
                'title' => 'Cash Drawer Variances Detected',
                'message' => "{$unbalancedShiftCount} shift(s) closed with cash discrepancies totaling ₱".number_format($totalVarianceAmount, 2).'.',
                'metric' => '₱'.number_format($totalVarianceAmount, 2),
            ];
        }
        if ($voidCount > 0) {
            $warnings[] = [
                'type' => 'voids',
                'severity' => 'info',
                'title' => 'Voided Transactions',
                'message' => "{$voidCount} void record(s) totaling ₱".number_format($voidTotalAmount, 2).' occurred in this period.',
                'metric' => "{$voidCount} voids (₱".number_format($voidTotalAmount, 2).')',
            ];
        }

        return [
            'open_order_count' => $openOrderCount,
            'open_balance_total' => $openBalanceTotal,
            'unbalanced_shift_count' => $unbalancedShiftCount,
            'total_variance_amount' => $totalVarianceAmount,
            'void_count' => $voidCount,
            'void_total_amount' => $voidTotalAmount,
            'refund_count' => $refundCount,
            'refund_total_amount' => $refundTotalAmount,
            'warnings' => $warnings,
        ];
    }

    public function generateInsights(
        Collection $productPerformance,
        array $branchSummary,
        array $dateWindows,
        ?int $scopedBranchId = null
    ): Collection {
        $insights = collect();
        $periodLabel = $dateWindows['label'];
        $branchName = $scopedBranchId ? (Branch::find($scopedBranchId)?->name ?? 'Selected Branch') : 'All Branches';
        $totalVolume = $productPerformance->sum('units_sold');

        // Check if there is insufficient overall data
        if ($totalVolume < 5 && $dateWindows['days_in_period'] >= 7) {
            $insights->push([
                'id' => 'insufficient_data_overall',
                'branch_id' => $scopedBranchId,
                'branch_name' => $branchName,
                'product_id' => null,
                'product_name' => 'General Catalog',
                'finding_category' => 'Insufficient data',
                'reporting_period' => $periodLabel,
                'actual_metric' => "{$totalVolume} total unit(s) sold",
                'comparison_metric' => 'Minimum reliable threshold is 10 transactions',
                'calculation_method' => 'Sample size evaluation over '.$dateWindows['days_in_period'].' days',
                'data_limitations' => 'Low transaction volume reduces statistical reliability for automated trend modeling.',
                'observed_fact' => "Only {$totalVolume} total item(s) were recorded across {$branchName} during this period.",
                'explanation' => 'Transaction density is currently too low to formulate high-confidence product demand patterns.',
                'suggested_action' => 'Monitor POS logging and verify that cashiers are recording all customer orders through the system before drawing operational conclusions.',
                'urgency' => 'low',
            ]);
        }

        // 1. High Demand with Low Ingredient Stock (Highest Priority)
        foreach ($productPerformance as $prod) {
            if ($prod->units_sold >= 3 && $prod->has_low_stock) {
                $ingNames = $prod->low_stock_ingredients->pluck('name')->join(', ');
                $insights->push([
                    'id' => "low_stock_{$prod->id}",
                    'branch_id' => $scopedBranchId,
                    'branch_name' => $branchName,
                    'product_id' => $prod->id,
                    'product_name' => $prod->name,
                    'finding_category' => 'Low ingredient stock',
                    'reporting_period' => $periodLabel,
                    'actual_metric' => "Sold {$prod->units_sold} units; Ingredients below threshold: {$ingNames}",
                    'comparison_metric' => 'Ingredient minimum threshold breached',
                    'calculation_method' => 'Current stock <= Minimum stock threshold during active customer demand',
                    'data_limitations' => 'Does not account for pending unreceived supplier purchase orders.',
                    'observed_fact' => "{$prod->name} sold {$prod->units_sold} units, but required ingredient(s) ({$ingNames}) have reached or fallen below minimum reorder thresholds.",
                    'explanation' => "Active sales velocity paired with depleted raw materials risks impending stock-outs during operational service.",
                    'suggested_action' => "Initiate a Stock-In delivery review for {$ingNames} to sustain current sales volume.",
                    'urgency' => 'high',
                ]);
            }
        }

        // 2. High Sales (Top Performer)
        $topPerformer = $productPerformance->firstWhere('units_sold', '>', 0);
        if ($topPerformer && $topPerformer->units_sold >= 10) {
            $insights->push([
                'id' => "top_sales_{$topPerformer->id}",
                'branch_id' => $scopedBranchId,
                'branch_name' => $branchName,
                'product_id' => $topPerformer->id,
                'product_name' => $topPerformer->name,
                'finding_category' => 'High sales',
                'reporting_period' => $periodLabel,
                'actual_metric' => "{$topPerformer->units_sold} units sold (₱".number_format($topPerformer->net_revenue, 2).')',
                'comparison_metric' => "Previous period: {$topPerformer->prev_units_sold} units (₱".number_format($topPerformer->prev_net_revenue, 2).')',
                'calculation_method' => 'Ranked #1 by customer volume across active menu catalog',
                'data_limitations' => 'Customer preference may reflect seasonal climate or channel mix.',
                'observed_fact' => "{$topPerformer->name} is the top revenue generator in this window, contributing ₱".number_format($topPerformer->net_revenue, 2)." across {$topPerformer->units_sold} units.",
                'explanation' => "Consistently high customer uptake makes this item a core volume driver for {$branchName}.",
                'suggested_action' => 'Ensure barista recipe compliance and adequate ingredient buffer stock to avoid service bottlenecks.',
                'urgency' => 'medium',
            ]);
        }

        // 3. Increasing Sales (Strong Momentum)
        foreach ($productPerformance as $prod) {
            if ($prod->units_sold >= 5 && $prod->prev_units_sold > 0 && ($prod->unit_change_percent ?? 0) >= 20.0) {
                $insights->push([
                    'id' => "increasing_sales_{$prod->id}",
                    'branch_id' => $scopedBranchId,
                    'branch_name' => $branchName,
                    'product_id' => $prod->id,
                    'product_name' => $prod->name,
                    'finding_category' => 'Increasing sales',
                    'reporting_period' => $periodLabel,
                    'actual_metric' => "{$prod->units_sold} units (+{$prod->unit_change_percent}%)",
                    'comparison_metric' => "Previous equivalent period: {$prod->prev_units_sold} units",
                    'calculation_method' => "(({$prod->units_sold} - {$prod->prev_units_sold}) / {$prod->prev_units_sold}) * 100 = +{$prod->unit_change_percent}%",
                    'data_limitations' => 'External factors such as promotions or weather are not controlled for.',
                    'observed_fact' => "{$prod->name} experienced a {$prod->unit_change_percent}% growth in sales volume compared to the prior comparable period.",
                    'explanation' => "Rising demand indicates positive customer reception or shifting category preference.",
                    'suggested_action' => 'Evaluate barista prep staffing and confirm recipe component buffers can accommodate continued trajectory.',
                    'urgency' => 'medium',
                ]);
                break; // Limit to highest growth
            }
        }

        // 4. Declining Sales (Noticeable drop)
        foreach ($productPerformance as $prod) {
            if ($prod->prev_units_sold >= 5 && $prod->units_sold < $prod->prev_units_sold && ($prod->unit_change_percent ?? 0) <= -20.0) {
                $absChange = abs($prod->unit_change_percent);
                $insights->push([
                    'id' => "declining_sales_{$prod->id}",
                    'branch_id' => $scopedBranchId,
                    'branch_name' => $branchName,
                    'product_id' => $prod->id,
                    'product_name' => $prod->name,
                    'finding_category' => 'Declining sales',
                    'reporting_period' => $periodLabel,
                    'actual_metric' => "{$prod->units_sold} units (-{$absChange}%)",
                    'comparison_metric' => "Previous equivalent period: {$prod->prev_units_sold} units",
                    'calculation_method' => "(({$prod->units_sold} - {$prod->prev_units_sold}) / {$prod->prev_units_sold}) * 100 = -{$absChange}%",
                    'data_limitations' => 'Prior period might have included a temporary surge or one-time bulk order.',
                    'observed_fact' => "{$prod->name} sales dropped by {$absChange}% from {$prod->prev_units_sold} units down to {$prod->units_sold} units.",
                    'explanation' => "Downward momentum observed across equivalent timeframes suggests either customer shift or availability friction.",
                    'suggested_action' => 'Check with branch baristas regarding taste feedback, display visibility, or menu positioning.',
                    'urgency' => 'high',
                ]);
                break;
            }
        }

        // 5. Zero Sales (Active products not moving)
        $zeroSales = $productPerformance->filter(fn ($p) => $p->units_sold === 0)->values();
        if ($zeroSales->isNotEmpty() && $dateWindows['days_in_period'] >= 3) {
            $sampleZero = $zeroSales->first();
            $count = $zeroSales->count();
            $insights->push([
                'id' => "zero_sales_{$sampleZero->id}",
                'branch_id' => $scopedBranchId,
                'branch_name' => $branchName,
                'product_id' => $sampleZero->id,
                'product_name' => $sampleZero->name,
                'finding_category' => 'Zero sales',
                'reporting_period' => $periodLabel,
                'actual_metric' => "0 units sold in current period ({$count} item(s) in catalog with zero sales)",
                'comparison_metric' => "Prior period sold: {$sampleZero->prev_units_sold} units",
                'calculation_method' => 'Zero recorded transaction lines across period',
                'data_limitations' => 'Item may have been temporarily 86ed (out of stock) without being deactivated.',
                'observed_fact' => "{$sampleZero->name} recorded 0 sales across this {$dateWindows['days_in_period']}-day window.",
                'explanation' => 'Unordered menu items consume display space and tie up potential ingredient shelf life without generating cash flow.',
                'suggested_action' => 'Verify ingredient freshness, assess whether recipe should be featured or rotated off active POS display.',
                'urgency' => 'medium',
            ]);
        }

        // 6. Branch Performance Difference (When viewing All Branches)
        if (! $scopedBranchId && ($branchSummary['branches'] ?? collect())->count() > 1) {
            $branches = $branchSummary['branches'];
            $maxBranch = $branches->sortByDesc('total_sales')->first();
            $minBranch = $branches->sortBy('total_sales')->first();

            if ($maxBranch && $minBranch && $minBranch['total_sales'] < ($maxBranch['total_sales'] * 0.5) && $maxBranch['total_sales'] > 500) {
                $insights->push([
                    'id' => 'branch_difference_sales',
                    'branch_id' => null,
                    'branch_name' => 'All Branches',
                    'product_id' => null,
                    'product_name' => 'Branch Store Comparison',
                    'finding_category' => 'Branch performance difference',
                    'reporting_period' => $periodLabel,
                    'actual_metric' => "{$maxBranch['name']}: ₱".number_format($maxBranch['total_sales'], 2)." vs. {$minBranch['name']}: ₱".number_format($minBranch['total_sales'], 2),
                    'comparison_metric' => 'Cross-branch revenue spread exceeds 50%',
                    'calculation_method' => 'Branch sales disparity = (High Branch - Low Branch) / High Branch',
                    'data_limitations' => 'Location foot traffic, operating hours, and customer demographics vary per branch.',
                    'observed_fact' => "{$maxBranch['name']} generated substantially higher sales volume than {$minBranch['name']} during this reporting window.",
                    'explanation' => 'Significant performance variance between locations indicates varying operating dynamics or staffing peaks.',
                    'suggested_action' => 'Review operating hours, footfall trends, and local branch outreach at '.$minBranch['name'].'.',
                    'urgency' => 'medium',
                ]);
            }
        }

        return $insights;
    }

    public function evaluateActionFollowUp(ProductAction $action): array
    {
        $timezone = config('app.business_timezone', 'Asia/Manila');
        $baselineStart = $action->period_start ? CarbonImmutable::parse($action->period_start, $timezone) : null;
        $baselineEnd = $action->period_end ? CarbonImmutable::parse($action->period_end, $timezone) : null;

        $followUpStart = $action->follow_up_start
            ? CarbonImmutable::parse($action->follow_up_start, $timezone)
            : ($action->completed_at ? CarbonImmutable::parse($action->completed_at, $timezone)->startOfDay() : null);

        $followUpEnd = $action->follow_up_end
            ? CarbonImmutable::parse($action->follow_up_end, $timezone)
            : ($followUpStart && $baselineStart && $baselineEnd ? $followUpStart->addDays($baselineStart->diffInDays($baselineEnd)) : now($timezone));

        if (! $baselineStart || ! $baselineEnd || ! $followUpStart) {
            return [
                'has_data' => false,
                'message' => 'Insufficient period parameters to calculate before/after metrics.',
            ];
        }

        $saleStatuses = ['completed', 'partially_paid', 'refunded'];
        $productId = $action->product_id;
        $branchId = $action->branch_id;

        $computePeriodMetrics = function (CarbonImmutable $start, CarbonImmutable $end) use ($productId, $branchId, $saleStatuses) {
            $startUtc = BusinessDateRange::startUtc($start->toDateString());
            $endUtc = BusinessDateRange::endExclusiveUtc($end->toDateString());

            // Units & Revenue
            $orderItemQuery = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('order_items.status', 'active')
                ->whereIn('orders.status', $saleStatuses)
                ->where('orders.created_at', '>=', $startUtc)
                ->where('orders.created_at', '<', $endUtc);

            if ($productId) {
                $orderItemQuery->where('order_items.product_id', $productId);
            }
            if ($branchId) {
                $orderItemQuery->where('orders.branch_id', $branchId);
            }

            $units = (int) $orderItemQuery->sum('order_items.quantity');
            $revenue = (float) $orderItemQuery->sum('order_items.subtotal');

            // Discounts on orders
            $orderQuery = Order::whereIn('status', $saleStatuses)
                ->where('created_at', '>=', $startUtc)
                ->where('created_at', '<', $endUtc)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->when($productId, fn ($q) => $q->whereHas('orderItems', fn ($oi) => $oi->where('product_id', $productId)));
            $discounts = (float) $orderQuery->sum('discount');

            // Ingredient consumption
            $ingredientIds = collect();
            if ($productId) {
                $product = Product::with('sizes.recipe.recipeIngredients')->find($productId);
                if ($product) {
                    foreach ($product->sizes as $size) {
                        if ($size->recipe) {
                            $ingredientIds = $ingredientIds->merge($size->recipe->recipeIngredients->pluck('ingredient_id'));
                        }
                    }
                }
            }
            $ingredientIds = $ingredientIds->unique()->values();

            $consumption = (float) DB::table('inventory_transactions')
                ->whereIn('type', ['sales_consumption'])
                ->where('created_at', '>=', $startUtc)
                ->where('created_at', '<', $endUtc)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->when($ingredientIds->isNotEmpty(), fn ($q) => $q->whereIn('ingredient_id', $ingredientIds))
                ->sum('quantity');

            // Waste recorded
            $waste = (float) DB::table('inventory_transactions')
                ->where('type', 'waste')
                ->where('created_at', '>=', $startUtc)
                ->where('created_at', '<', $endUtc)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->when($ingredientIds->isNotEmpty(), fn ($q) => $q->whereIn('ingredient_id', $ingredientIds))
                ->sum('quantity');

            return [
                'units' => $units,
                'revenue' => $revenue,
                'discounts' => $discounts,
                'consumption' => $consumption,
                'waste' => $waste,
            ];
        };

        $before = $computePeriodMetrics($baselineStart, $baselineEnd);
        $after = $computePeriodMetrics($followUpStart, $followUpEnd);

        $unitDelta = $after['units'] - $before['units'];
        $unitDeltaPercent = $before['units'] > 0
            ? round(($unitDelta / $before['units']) * 100, 1)
            : ($after['units'] > 0 ? null : 0.0);

        $revenueDelta = round($after['revenue'] - $before['revenue'], 2);
        $revenueDeltaPercent = $before['revenue'] > 0
            ? round(($revenueDelta / $before['revenue']) * 100, 1)
            : ($after['revenue'] > 0 ? null : 0.0);

        // Stock availability now
        $stockStatus = 'Adequate';
        if ($action->product) {
            $hasLowStock = false;
            foreach ($action->product->sizes as $s) {
                if ($s->recipe) {
                    foreach ($s->recipe->recipeIngredients as $ri) {
                        $ing = $ri->ingredient;
                        if ($ing && ($ing->getCurrentStock() <= $ing->minimum_stock)) {
                            $hasLowStock = true;
                            break 2;
                        }
                    }
                }
            }
            if ($hasLowStock) {
                $stockStatus = 'Low Ingredient Stock';
            }
        }

        return [
            'has_data' => true,
            'baseline_period' => $baselineStart->format('M j, Y').' – '.$baselineEnd->format('M j, Y'),
            'follow_up_period' => $followUpStart->format('M j, Y').' – '.$followUpEnd->format('M j, Y'),
            'before' => $before,
            'after' => $after,
            'changes' => [
                'unit_delta' => $unitDelta,
                'unit_delta_percent' => $unitDeltaPercent,
                'revenue_delta' => $revenueDelta,
                'revenue_delta_percent' => $revenueDeltaPercent,
                'discount_delta' => round($after['discounts'] - $before['discounts'], 2),
                'consumption_delta' => round($after['consumption'] - $before['consumption'], 3),
                'waste_delta' => round($after['waste'] - $before['waste'], 3),
            ],
            'stock_status' => $stockStatus,
            'disclaimer' => 'Observed metric changes across equivalent reporting windows. These figures represent observed descriptive correlation, not causal proof. Profitability cannot be calculated without complete operational expense allocations.',
        ];
    }
}
