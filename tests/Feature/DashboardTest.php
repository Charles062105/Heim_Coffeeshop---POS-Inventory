<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderAdjustment;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Refund;
use App\Models\User;
use App\Models\VoidLog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_dashboard_renders_all_executive_panels(): void
    {
        $manager = User::factory()->create(['name' => 'Maria Santos', 'role' => 'manager']);

        // Create categories & products
        $cat = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $prod = Product::create(['category_id' => $cat->id, 'name' => 'Spanish Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $prod->id, 'size_name' => 'Regular', 'price' => 150, 'status' => 'active']);

        // Create an order completed today
        $order = Order::create([
            'order_number' => 'ORD-20260917-TEST',
            'cashier_name' => 'Maria Santos',
            'subtotal' => 150,
            'discount' => 0,
            'total' => 150,
            'status' => 'completed',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $prod->id,
            'product_size_id' => $size->id,
            'quantity' => 1,
            'unit_price' => 150,
            'subtotal' => 150,
        ]);
        Payment::create([
            'order_id' => $order->id,
            'method' => 'cash',
            'amount_received' => 200,
            'amount_paid' => 150,
            'change_amount' => 50,
            'status' => 'paid',
        ]);

        Order::create([
            'order_number' => 'ORD-20260917-PARTIAL',
            'cashier_name' => 'Maria Santos',
            'subtotal' => 45,
            'discount' => 0,
            'total' => 45,
            'status' => 'partially_paid',
        ])->payments()->create([
            'method' => 'cash',
            'amount_received' => 20,
            'amount_paid' => 20,
            'change_amount' => 0,
            'status' => 'paid',
        ]);

        Order::create([
            'order_number' => 'ORD-20260917-UNPAID',
            'cashier_name' => 'Maria Santos',
            'subtotal' => 30,
            'discount' => 0,
            'total' => 30,
            'status' => 'partially_paid',
        ]);
        Order::create([
            'order_number' => 'ORD-20260917-PENDING',
            'cashier_name' => 'Maria Santos',
            'subtotal' => 40,
            'discount' => 0,
            'total' => 40,
            'status' => 'pending',
        ]);

        // Create low stock and out of stock ingredients
        $milk = Ingredient::create(['name' => 'Fresh Milk', 'unit' => 'L', 'minimum_stock' => 5, 'status' => 'active']);
        Inventory::create(['ingredient_id' => $milk->id, 'current_stock' => 2.5]); // low stock

        $beans = Ingredient::create(['name' => 'Coffee Beans', 'unit' => 'kg', 'minimum_stock' => 1, 'status' => 'active']);
        Inventory::create(['ingredient_id' => $beans->id, 'current_stock' => 0]); // out of stock

        // Create sales consumption transaction
        InventoryTransaction::create([
            'ingredient_id' => $milk->id,
            'type' => 'sales_consumption',
            'quantity' => 0.257,
            'previous_stock' => 2.757,
            'new_stock' => 2.5,
            'performed_by' => 'POS System',
            'performed_role' => 'cashier',
            'notes' => 'Sales consumption for order',
        ]);

        // Create audit logs
        AuditLog::create([
            'actor_name' => 'Maria Santos',
            'actor_role' => 'manager',
            'actor_user_id' => $manager->id,
            'action' => 'report_inventory_viewed',
            'module' => 'reports',
        ]);

        $response = $this->actingAs($manager)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('.dashboard-shell {', false)
            ->assertSee('zoom: 100% !important', false);
        $response->assertSee('dashboard-shell', false)
            ->assertSee('.dashboard-shell', false)
            ->assertSee('width: 100vw', false)
            ->assertSee('dashboard-kpi-grid', false)
            ->assertSee('dashboard-sales-summary-grid', false)
            ->assertSee('dashboard-payment-breakdown', false)
            ->assertSee('dashboard-side-panel', false)
            ->assertSee('dashboard-product-ranking', false)
            ->assertSee('class="dashboard-payment-breakdown overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm" open', false)
            ->assertSee('grid-template-columns: repeat(5, minmax(0, 1fr))', false)
            ->assertSee('Open Order Balances')
            ->assertSee('Pending (1)')
            ->assertSee('Partially paid (2)')
            ->assertSee('grid-template-columns: repeat(6, minmax(0, 1fr))', false)
            ->assertSee('Last 7 Days')
            ->assertSee('Tender / Paid Order');

        // Verify view data
        $response->assertViewHas('todaySales', 170.0);
        $response->assertViewHas('todayOrders', 2);
        $response->assertViewHas('pendingOrders', 3);
        $response->assertViewHas('pendingOrderCount', 1);
        $response->assertViewHas('pendingOrderBalance', 40.0);
        $response->assertViewHas('partiallyPaidOrderCount', 2);
        $response->assertViewHas('partiallyPaidOrderBalance', 55.0);
        $response->assertViewHas('averageTenderPerOrder', 85.0);
        $response->assertViewHas('totalRevenue', 170.0);
        $response->assertViewHas('lowStockCount', 1);
        $response->assertViewHas('outOfStockCount', 1);

        $salesTrend = $response->viewData('salesTrend');
        $this->assertCount(7, $salesTrend);

        $inventoryAlerts = $response->viewData('inventoryAlerts');
        $this->assertCount(2, $inventoryAlerts);
        // Out of stock item should be first
        $this->assertEquals('Coffee Beans', $inventoryAlerts->first()->name);

        $todayConsumption = $response->viewData('todayConsumption');
        $this->assertCount(1, $todayConsumption);
        $this->assertEquals('Fresh Milk', $todayConsumption->first()->name);
        $this->assertEquals(0.257, $todayConsumption->first()->consumed);
        $response->assertSee('0.257 L');

        $recentLogs = $response->viewData('recentLogs');
        $this->assertCount(1, $recentLogs);
        $this->assertEquals('Generated Inventory Report', $recentLogs->first()->action);

        // Verify key UI text elements exist
        $response->assertSee('Quick Actions');
        $response->assertSee('Sales Overview');
        $response->assertSee('New sales activity');
        $response->assertSee('New paid orders');
        $response->assertSee('Sales</button>', false);
        $response->assertSee('Orders</button>', false);
        $response->assertSee('Inventory Alerts');
        $response->assertSee('Daily Consumption');
        $response->assertSee('Recent Activity');
        $response->assertSee('Payment Breakdown');
        $response->assertSee('Total Payments Received');
    }

    public function test_cashier_dashboard_displays_shift_specific_metrics(): void
    {
        $cashier = User::factory()->create(['name' => 'Charles', 'role' => 'cashier']);

        // Create order by Charles
        $order = Order::create([
            'order_number' => 'ORD-CASHIER-1',
            'cashier_name' => 'Charles',
            'subtotal' => 96,
            'discount' => 0,
            'total' => 96,
            'status' => 'completed',
        ]);
        $order->payments()->create([
            'method' => 'cash',
            'amount_received' => 96,
            'amount_paid' => 96,
            'change_amount' => 0,
            'status' => 'paid',
        ]);

        $partialOrder = Order::create([
            'order_number' => 'ORD-CASHIER-PARTIAL',
            'cashier_name' => 'Charles',
            'subtotal' => 50,
            'discount' => 0,
            'total' => 50,
            'status' => 'partially_paid',
        ]);
        $partialOrder->payments()->create([
            'method' => 'cash',
            'amount_received' => 20,
            'amount_paid' => 20,
            'change_amount' => 0,
            'status' => 'paid',
        ]);

        // Create order by someone else
        $otherOrder = Order::create([
            'order_number' => 'ORD-OTHER-2',
            'cashier_name' => 'Juan',
            'subtotal' => 250,
            'discount' => 0,
            'total' => 250,
            'status' => 'completed',
        ]);
        $otherOrder->payments()->create([
            'method' => 'online',
            'amount_received' => 250,
            'amount_paid' => 250,
            'change_amount' => 0,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($cashier)->get(route('dashboard'));
        $response->assertOk();

        $response->assertViewHas('cashierTodaySales', 116.0);
        $response->assertViewHas('cashierTodayOrders', 2);
        $response->assertViewHas('cashierAllTimeSales', 116.0);
        $response->assertViewHas('todaySales', 116.0);
        $response->assertViewHas('totalRevenue', 116.0);
        $response->assertViewHas('todayOrders', 2);
        $response->assertViewHas('totalOrders', 2);

        $this->assertSame(116.0, (float) $response->viewData('paymentSummary')->first()->total);
        $this->assertSame(116.0, (float) $response->viewData('weekTotal'));
        $this->assertSame(2, $response->viewData('weekOrders'));
        $this->assertCount(2, $response->viewData('recentOrders'));
        $this->assertNotContains(
            'ORD-OTHER-2',
            $response->viewData('recentOrders')->pluck('order_number')->all()
        );

        $response->assertSee('My Payments Today');
        $response->assertSee('My Shift Orders');
        $response->assertSee('Open Register');
        $response->assertDontSee('Inventory Alerts');
        $response->assertDontSee('Daily Consumption');
        $response->assertDontSee('Recent Activity');
        $response->assertDontSee(route('audit-logs.index'));
        $response->assertDontSee(route('reports.sales'));
        $response->assertDontSee(route('inventory.index'));
    }

    public function test_dashboard_daily_sales_use_the_business_timezone(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        Carbon::setTestNow(Carbon::parse('2026-10-06 17:00:00', 'UTC'));

        try {
            foreach ([
                ['before-business-day', '2026-10-06 15:59:59', 100],
                ['at-business-day-start', '2026-10-06 16:00:00', 90],
                ['during-business-day', '2026-10-07 15:59:59', 30],
                ['at-next-business-day', '2026-10-07 16:00:00', 200],
            ] as [$number, $createdAt, $total]) {
                $order = Order::create([
                    'order_number' => 'ORD-TZ-'.$number,
                    'cashier_name' => 'Cashier',
                    'subtotal' => $total,
                    'discount' => 0,
                    'total' => $total,
                    'status' => 'completed',
                ]);
                $order->forceFill([
                    'created_at' => Carbon::parse($createdAt, 'UTC'),
                    'updated_at' => Carbon::parse($createdAt, 'UTC'),
                ])->save();
                $payment = $order->payments()->create([
                    'method' => 'cash',
                    'amount_received' => $total,
                    'amount_paid' => $total,
                    'change_amount' => 0,
                    'status' => 'paid',
                ]);
                $payment->forceFill([
                    'created_at' => Carbon::parse($createdAt, 'UTC'),
                    'updated_at' => Carbon::parse($createdAt, 'UTC'),
                ])->save();
            }

            $response = $this->actingAs($manager)->get(route('dashboard'));

            $response->assertOk();
            $response->assertViewHas('todaySales', 120.0);
            $response->assertViewHas('todayOrders', 2);

            $todayTrend = $response->viewData('salesTrend')->firstWhere('date', 'Oct 7');
            $this->assertSame(120.0, $todayTrend['total']);
            $this->assertSame(2, $todayTrend['count']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_sales_follow_the_date_a_payment_is_collected(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        Carbon::setTestNow(Carbon::parse('2026-10-08 10:00:00', 'UTC'));

        try {
            $order = Order::create([
                'order_number' => 'ORD-PRIOR-DAY-PAYMENT',
                'cashier_name' => 'Cashier',
                'subtotal' => 100,
                'discount' => 0,
                'total' => 100,
                'status' => 'partially_paid',
            ]);
            $order->forceFill([
                'created_at' => Carbon::parse('2026-10-07 15:59:59', 'UTC'),
                'updated_at' => Carbon::parse('2026-10-07 15:59:59', 'UTC'),
            ])->save();

            $payment = $order->payments()->create([
                'method' => 'cash',
                'amount_received' => 30,
                'amount_paid' => 30,
                'change_amount' => 0,
                'status' => 'paid',
            ]);
            $payment->forceFill([
                'created_at' => Carbon::parse('2026-10-07 16:00:00', 'UTC'),
                'updated_at' => Carbon::parse('2026-10-07 16:00:00', 'UTC'),
            ])->save();

            $response = $this->actingAs($manager)->get(route('dashboard'));

            $response->assertOk();
            $response->assertViewHas('todaySales', 30.0);
            $response->assertViewHas('todayOrders', 1);
            $todayTrend = $response->viewData('salesTrend')->firstWhere('date', 'Oct 8');
            $this->assertSame(30.0, $todayTrend['total']);
            $this->assertSame(1, $todayTrend['count']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_preserves_collected_tender_after_a_later_refund(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        Carbon::setTestNow(Carbon::parse('2026-10-08 10:00:00', 'UTC'));

        try {
            $order = Order::create([
                'order_number' => 'ORD-DASHBOARD-REFUNDED-TENDER',
                'cashier_name' => $manager->name,
                'subtotal' => 100,
                'discount' => 0,
                'total' => 100,
                'status' => 'refunded',
            ]);
            $payment = $order->payments()->create([
                'method' => 'cash',
                'amount_received' => 100,
                'amount_paid' => 100,
                'change_amount' => 0,
                'status' => 'refunded',
            ]);
            $payment->forceFill([
                'created_at' => Carbon::parse('2026-10-07 16:00:00', 'UTC'),
                'updated_at' => Carbon::parse('2026-10-07 16:00:00', 'UTC'),
            ])->save();
            Refund::create([
                'order_id' => $order->id,
                'amount' => 100,
                'method' => 'cash',
                'status' => 'completed',
                'reason' => 'Dashboard report regression',
                'authorized_by' => $manager->name,
                'authorized_role' => 'manager',
                'stock_restored' => false,
                'refunded_at' => now(),
            ]);

            $response = $this->actingAs($manager)->get(route('dashboard'));

            $response->assertOk();
            $response->assertViewHas('todaySales', 100.0);
            $response->assertViewHas('todayOrders', 1);
            $response->assertViewHas('totalRevenue', 100.0);
            $todayTrend = $response->viewData('salesTrend')->firstWhere('date', 'Oct 8');
            $this->assertSame(100.0, $todayTrend['total']);
            $this->assertSame(1, $todayTrend['count']);
            $this->assertSame(100.0, (float) $response->viewData('paymentSummary')->first()->total);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_daily_consumption_nets_sales_returns(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $ingredient = Ingredient::create([
            'name' => 'Daily Net Coffee Beans',
            'unit' => 'g',
            'minimum_stock' => 1,
            'status' => 'active',
        ]);
        Inventory::create(['ingredient_id' => $ingredient->id, 'current_stock' => 96]);

        foreach ([
            ['sales_consumption', 10],
            ['sales_return', 4],
        ] as [$type, $quantity]) {
            InventoryTransaction::create([
                'ingredient_id' => $ingredient->id,
                'type' => $type,
                'quantity' => $quantity,
                'previous_stock' => 100,
                'new_stock' => 96,
                'performed_by' => $manager->name,
                'performed_role' => 'manager',
            ]);
        }

        $response = $this->actingAs($manager)->get(route('dashboard'));

        $response->assertOk();
        $consumption = $response->viewData('todayConsumption')->firstWhere('name', 'Daily Net Coffee Beans');
        $this->assertNotNull($consumption);
        $this->assertSame(6.0, $consumption->consumed);
    }

    public function test_dashboard_shows_best_and_least_selling_products(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $category = Category::create(['name' => 'Dashboard Ranking', 'status' => 'active']);
        $bestSeller = Product::create(['category_id' => $category->id, 'name' => 'Popular Latte', 'status' => 'active']);
        $slowSeller = Product::create(['category_id' => $category->id, 'name' => 'Slow Mocha', 'status' => 'active']);
        $unsold = Product::create(['category_id' => $category->id, 'name' => 'Unsold Tea', 'status' => 'active']);
        $bestSize = ProductSize::create(['product_id' => $bestSeller->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);
        $slowSize = ProductSize::create(['product_id' => $slowSeller->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);
        $order = Order::create([
            'order_number' => 'ORD-DASHBOARD-RANKING',
            'cashier_name' => $owner->name,
            'subtotal' => 800,
            'discount' => 0,
            'total' => 800,
            'status' => 'completed',
        ]);

        foreach ([[$bestSeller, $bestSize, 7], [$slowSeller, $slowSize, 1]] as [$product, $size, $quantity]) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_size_id' => $size->id,
                'quantity' => $quantity,
                'unit_price' => 100,
                'subtotal' => $quantity * 100,
            ]);
        }

        $response = $this->actingAs($owner)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Best-Selling Products');
        $response->assertSee('Least-Selling Products');
        $this->assertSame('Popular Latte', $response->viewData('topProducts')->first()['name']);
        $leastProducts = $response->viewData('leastProducts');
        $this->assertSame('Unsold Tea', $leastProducts->first()['name']);
        $this->assertSame(0, $leastProducts->first()['sold']);
        $this->assertSame('Slow Mocha', $leastProducts->get(1)['name']);
        $this->assertSame(1, $leastProducts->get(1)['sold']);
    }

    public function test_dashboard_shows_operational_and_weekly_performance_metrics(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        Carbon::setTestNow(Carbon::parse('2026-10-08 10:00:00', 'UTC'));

        try {
            $category = Category::create(['name' => 'Peak Day Category', 'status' => 'active']);
            $product = Product::create(['category_id' => $category->id, 'name' => 'Peak Day Latte', 'status' => 'active']);
            $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);

            $createOrder = function (string $number, string $status, float $total, string $createdAt, bool $paid = false) use ($manager) {
                $order = Order::create([
                    'order_number' => $number,
                    'cashier_name' => $manager->name,
                    'subtotal' => $total,
                    'discount' => 0,
                    'total' => $total,
                    'status' => $status,
                ]);
                $order->forceFill([
                    'created_at' => Carbon::parse($createdAt, 'UTC'),
                    'updated_at' => Carbon::parse($createdAt, 'UTC'),
                ])->save();

                if ($paid) {
                    $payment = $order->payments()->create([
                        'method' => 'cash',
                        'amount_received' => $total,
                        'amount_paid' => $total,
                        'change_amount' => 0,
                        'status' => 'paid',
                    ]);
                    $payment->forceFill([
                        'created_at' => Carbon::parse($createdAt, 'UTC'),
                        'updated_at' => Carbon::parse($createdAt, 'UTC'),
                    ])->save();
                }

                return $order;
            };

            $peakDayOrder = $createOrder('ORD-PEAK-DAY', 'completed', 100, '2026-10-08 02:00:00', true);
            OrderItem::create([
                'order_id' => $peakDayOrder->id,
                'product_id' => $product->id,
                'product_size_id' => $size->id,
                'quantity' => 1,
                'unit_price' => 100,
                'subtotal' => 100,
            ]);
            $priorWeekOrder = $createOrder('ORD-PRIOR-WEEK', 'completed', 50, '2026-09-30 03:00:00', true);
            $pendingOrder = $createOrder('ORD-TODAY-PENDING', 'pending', 25, '2026-10-08 03:30:00');
            $cancelledOrder = $createOrder('ORD-TODAY-CANCELLED', 'cancelled', 20, '2026-10-08 04:00:00');
            OrderAdjustment::create([
                'order_id' => $cancelledOrder->id,
                'action' => 'cancel',
                'reason' => 'Customer changed their mind',
                'authorized_by' => $manager->name,
                'authorized_role' => $manager->role,
            ]);
            $voidedOrder = $createOrder('ORD-TODAY-VOIDED', 'voided', 30, '2026-10-08 05:00:00');
            VoidLog::create([
                'order_id' => $voidedOrder->id,
                'void_type' => 'order',
                'amount' => 30,
                'reason' => 'Incorrect order entry',
                'cashier_name' => $manager->name,
                'authorized_by' => $manager->name,
                'authorized_role' => $manager->role,
                'voided_at' => Carbon::parse('2026-10-08 05:00:00', 'UTC'),
            ]);
            Refund::create([
                'order_id' => $peakDayOrder->id,
                'amount' => 5,
                'method' => 'cash',
                'status' => 'completed',
                'reason' => 'Customer reported a duplicate charge',
                'authorized_by' => $manager->name,
                'authorized_role' => $manager->role,
                'refunded_at' => Carbon::parse('2026-10-08 06:00:00', 'UTC'),
            ]);

            $response = $this->actingAs($manager)->get(route('dashboard'));

            $response->assertOk();
            $this->assertSame(100.0, $response->viewData('averageOrderValue'));
            $this->assertSame(100.0, $response->viewData('averageTenderPerOrder'));
            $this->assertSame(100.0, $response->viewData('weekChangePercent'));
            $this->assertSame(0.0, $response->viewData('weekOrdersChangePercent'));
            $this->assertSame(1, $response->viewData('pendingOrders'));

            $statuses = $response->viewData('orderStatusBreakdown')->keyBy('status');
            $this->assertSame(1, $statuses['completed']['count']);
            $this->assertSame(1, $statuses['pending']['count']);
            $this->assertSame(1, $statuses['cancelled']['count']);
            $this->assertSame(1, $statuses['voided']['count']);

            $this->assertSame(2, $response->viewData('hourlyActivity')->firstWhere('label', '10 AM–12 PM')['orders']);
            $this->assertSame(1, (int) $response->viewData('refundSummary')->count);
            $this->assertSame(5.0, (float) $response->viewData('refundSummary')->total);
            $this->assertSame(1, (int) $response->viewData('cancellationSummary')->count);
            $this->assertSame(20.0, (float) $response->viewData('cancellationSummary')->total);
            $this->assertSame(1, (int) $response->viewData('voidSummary')->count);
            $this->assertSame(30.0, (float) $response->viewData('voidSummary')->total);
            $this->assertSame('Peak Day Latte', $response->viewData('peakDayProducts')->first()->name);
            $this->assertSame('Peak Day Category', $response->viewData('peakDayCategories')->first()->name);
            $this->assertSame('Customer changed their mind', $response->viewData('cancellationReasons')->first()->reason);
            $this->assertSame('Incorrect order entry', $response->viewData('voidReasons')->first()->reason);
            $this->assertSame('Customer reported a duplicate charge', $response->viewData('refundReasons')->first()->reason);
        } finally {
            Carbon::setTestNow();
        }
    }
}
