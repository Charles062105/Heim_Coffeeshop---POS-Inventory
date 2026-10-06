<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\User;
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
        $response->assertSee('style="zoom: 90%"', false);

        // Verify view data
        $response->assertViewHas('todaySales', 195.0);
        $response->assertViewHas('todayOrders', 2);
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
        $response->assertSee('Inventory Alerts');
        $response->assertSee('Daily Consumption');
        $response->assertSee('Recent Activity');
        $response->assertSee('Payment Breakdown');
        $response->assertSee('Total Sales');
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

        // Create order by someone else
        Order::create([
            'order_number' => 'ORD-OTHER-2',
            'cashier_name' => 'Juan',
            'subtotal' => 250,
            'discount' => 0,
            'total' => 250,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($cashier)->get(route('dashboard'));
        $response->assertOk();

        $response->assertViewHas('cashierTodaySales', 96.0);
        $response->assertViewHas('cashierTodayOrders', 1);

        $response->assertSee('My Sales Today');
        $response->assertSee('My Shift Orders');
        $response->assertSee('Open Register');
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
}
