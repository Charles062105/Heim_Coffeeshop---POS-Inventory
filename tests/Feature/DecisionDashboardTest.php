<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductAction;
use App\Models\ProductActionAudit;
use App\Models\ProductSize;
use App\Models\Recipe;
use App\Models\User;
use App\Services\ProductAnalyticsService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DecisionDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Branch $mainBranch;
    private Branch $uptownBranch;
    private User $owner;
    private User $mainManager;
    private User $uptownManager;
    private User $cashier;
    private Category $coffeeCategory;
    private Category $pastryCategory;
    private Product $latte;
    private ProductSize $latteSize;
    private Product $croissant;
    private ProductSize $croissantSize;
    private Ingredient $espressoBeans;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mainBranch = Branch::where('code', 'MAIN')->first() ?? Branch::create([
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'is_active' => true,
        ]);

        $this->uptownBranch = Branch::create([
            'name' => 'Uptown Branch',
            'code' => 'UPTOWN',
            'is_active' => true,
        ]);

        $this->owner = User::factory()->create([
            'role' => 'owner',
            'status' => 'active',
            'branch_id' => $this->mainBranch->id,
        ]);

        $this->mainManager = User::factory()->create([
            'role' => 'manager',
            'status' => 'active',
            'branch_id' => $this->mainBranch->id,
        ]);

        $this->uptownManager = User::factory()->create([
            'role' => 'manager',
            'status' => 'active',
            'branch_id' => $this->uptownBranch->id,
        ]);

        $this->cashier = User::factory()->create([
            'role' => 'cashier',
            'status' => 'active',
            'branch_id' => $this->mainBranch->id,
        ]);

        $this->coffeeCategory = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $this->pastryCategory = Category::create(['name' => 'Pastries', 'status' => 'active']);

        $this->latte = Product::create([
            'category_id' => $this->coffeeCategory->id,
            'name' => 'Spanish Latte',
            'status' => 'active',
        ]);
        $this->latteSize = ProductSize::create([
            'product_id' => $this->latte->id,
            'size_name' => 'Regular',
            'price' => 150.00,
            'status' => 'active',
        ]);

        $this->croissant = Product::create([
            'category_id' => $this->pastryCategory->id,
            'name' => 'Butter Croissant',
            'status' => 'active',
        ]);
        $this->croissantSize = ProductSize::create([
            'product_id' => $this->croissant->id,
            'size_name' => 'Standard',
            'price' => 120.00,
            'status' => 'active',
        ]);

        $this->espressoBeans = Ingredient::create([
            'name' => 'Espresso Beans',
            'unit' => 'g',
            'minimum_stock' => 100,
            'status' => 'active',
        ]);
        $this->espressoBeans->inventory()->create([
            'branch_id' => $this->mainBranch->id,
            'current_stock' => 1000,
            'cost_per_unit' => 0.50,
        ]);

        $recipe = Recipe::create(['product_size_id' => $this->latteSize->id]);
        $recipe->recipeIngredients()->create([
            'ingredient_id' => $this->espressoBeans->id,
            'quantity' => 20,
        ]);
    }

    public function test_branch_specific_insight_isolation(): void
    {
        // Branch A order for Spanish Latte
        $this->createOrder($this->mainBranch, $this->latte, $this->latteSize, 10, now());

        // Branch B order for Butter Croissant
        $this->createOrder($this->uptownBranch, $this->croissant, $this->croissantSize, 8, now());

        // Uptown manager accessing Decision Dashboard sees only Uptown Branch
        $response = $this->actingAs($this->uptownManager)->get(route('reports.decision-dashboard.index'));
        $response->assertOk();
        $response->assertSee('Uptown Branch');
        $performance = $response->viewData('productPerformance');

        // Croissant has 8 units, Latte has 0 units in Uptown Branch
        $uptownCroissant = $performance->firstWhere('id', $this->croissant->id);
        $uptownLatte = $performance->firstWhere('id', $this->latte->id);
        $this->assertSame(8, $uptownCroissant->units_sold);
        $this->assertSame(0, $uptownLatte->units_sold);

        // Uptown manager attempting to query Main Branch gets 403 Forbidden
        $this->actingAs($this->uptownManager)
            ->get(route('reports.decision-dashboard.index', ['branch_id' => $this->mainBranch->id]))
            ->assertForbidden();

        // Owner can view all or switch to Main Branch
        $ownerResponse = $this->actingAs($this->owner)
            ->get(route('reports.decision-dashboard.index', ['branch_id' => $this->mainBranch->id]));
        $ownerResponse->assertOk();
        $mainPerformance = $ownerResponse->viewData('productPerformance');
        $mainLatte = $mainPerformance->firstWhere('id', $this->latte->id);
        $this->assertSame(10, $mainLatte->units_sold);
    }

    public function test_correct_evidence_values(): void
    {
        // 12 units of Latte sold today (12 * 150 = 1,800.00)
        $this->createOrder($this->mainBranch, $this->latte, $this->latteSize, 12, now());

        $response = $this->actingAs($this->owner)->get(route('reports.decision-dashboard.index', [
            'type' => 'daily',
            'date' => now()->toDateString(),
        ]));
        $response->assertOk();

        $performance = $response->viewData('productPerformance');
        $latteData = $performance->firstWhere('id', $this->latte->id);

        $this->assertNotNull($latteData);
        $this->assertSame(12, $latteData->units_sold);
        $this->assertEquals(1800.00, $latteData->net_revenue);

        // Check AI Insights collection
        $insights = $response->viewData('insights');
        $this->assertTrue($insights->isNotEmpty());
        $topInsight = $insights->firstWhere('product_id', $this->latte->id);
        $this->assertNotNull($topInsight);
        $this->assertStringContainsString('12 units', $topInsight['actual_metric']);
        $this->assertStringContainsString('1,800.00', $topInsight['actual_metric']);
    }

    public function test_correct_period_comparisons(): void
    {
        $businessDate = '2026-10-11';
        $prevDate = '2026-10-10';

        $todayTime = \App\Support\BusinessDateRange::startUtc($businessDate)->addHours(4);
        $yesterdayTime = \App\Support\BusinessDateRange::startUtc($prevDate)->addHours(4);

        // Yesterday: 8 units sold (8 * 150 = 1,200)
        $this->createOrder($this->mainBranch, $this->latte, $this->latteSize, 8, $yesterdayTime);

        // Today: 12 units sold (12 * 150 = 1,800) -> Growth: +4 units (+50.0%)
        $this->createOrder($this->mainBranch, $this->latte, $this->latteSize, 12, $todayTime);

        $response = $this->actingAs($this->owner)->get(route('reports.decision-dashboard.index', [
            'type' => 'daily',
            'date' => $businessDate,
        ]));
        $response->assertOk();

        $performance = $response->viewData('productPerformance');
        $latteData = $performance->firstWhere('id', $this->latte->id);

        $this->assertSame(12, $latteData->units_sold);
        $this->assertSame(8, $latteData->prev_units_sold);
        $this->assertSame(4, $latteData->unit_change);
        $this->assertEquals(50.0, $latteData->unit_change_percent);
        $this->assertEquals(600.00, $latteData->revenue_change);
        $this->assertEquals(50.0, $latteData->revenue_change_percent);
        $this->assertSame('increasing', $latteData->trend);
    }

    public function test_handling_zero_previous_period_sales(): void
    {
        $today = now();

        // Current period: 6 units sold
        $this->createOrder($this->mainBranch, $this->latte, $this->latteSize, 6, $today);
        // Previous period: 0 units sold

        $response = $this->actingAs($this->owner)->get(route('reports.decision-dashboard.index', [
            'type' => 'daily',
            'date' => $today->toDateString(),
        ]));
        $response->assertOk();

        $performance = $response->viewData('productPerformance');
        $latteData = $performance->firstWhere('id', $this->latte->id);

        $this->assertSame(6, $latteData->units_sold);
        $this->assertSame(0, $latteData->prev_units_sold);
        // Does not divide by zero; percent is null representing new/initial period volume
        $this->assertNull($latteData->unit_change_percent);
        $this->assertSame(6, $latteData->unit_change);
        $this->assertSame('increasing', $latteData->trend);
    }

    public function test_handling_insufficient_data(): void
    {
        // Over a monthly range (30 days), only 1 unit is sold across the entire catalog
        $this->createOrder($this->mainBranch, $this->latte, $this->latteSize, 1, now());

        $response = $this->actingAs($this->owner)->get(route('reports.decision-dashboard.index', [
            'type' => 'monthly',
            'date' => now()->toDateString(),
        ]));
        $response->assertOk();

        $insights = $response->viewData('insights');
        $insufficientInsight = $insights->firstWhere('finding_category', 'Insufficient data');

        $this->assertNotNull($insufficientInsight);
        $this->assertStringContainsString('Low transaction volume', $insufficientInsight['data_limitations']);
    }

    public function test_unauthorized_users_being_denied_owner_only_actions(): void
    {
        // Cashiers denied decision dashboard completely
        $this->actingAs($this->cashier)
            ->get(route('reports.decision-dashboard.index'))
            ->assertForbidden();

        // Create an existing product action
        $action = ProductAction::create([
            'action_code' => 'ACT-TEST-0001',
            'branch_id' => $this->mainBranch->id,
            'product_id' => $this->latte->id,
            'finding_category' => 'Declining sales',
            'evidence_summary' => 'Volume down 30%',
            'recommended_action' => 'Revise recipe',
            'owner_decision' => 'Needs Review',
            'status' => 'needs_review',
            'created_by_user_id' => $this->mainManager->id,
            'created_by_name' => $this->mainManager->name,
        ]);

        // Manager cannot record executive owner decisions
        $this->actingAs($this->mainManager)
            ->patch(route('reports.decision-dashboard.actions.decision', $action), [
                'owner_decision' => 'Approved',
            ])
            ->assertForbidden();

        // Owner CAN record executive decision
        $this->actingAs($this->owner)
            ->patch(route('reports.decision-dashboard.actions.decision', $action), [
                'owner_decision' => 'Approved',
                'assigned_to_user_id' => $this->mainManager->id,
                'due_date' => now()->addDays(7)->toDateString(),
            ])
            ->assertRedirect();

        $action->refresh();
        $this->assertSame('Approved', $action->owner_decision);
        $this->assertSame('approved', $action->status);
        $this->assertSame($this->mainManager->id, $action->assigned_to_user_id);
    }

    public function test_creating_updating_and_completing_product_actions(): void
    {
        // 1. Manager logs an action
        $response = $this->actingAs($this->mainManager)->post(route('reports.decision-dashboard.actions.store'), [
            'product_id' => $this->latte->id,
            'branch_id' => $this->mainBranch->id,
            'finding_category' => 'Low ingredient stock',
            'evidence_summary' => 'Espresso beans at 80g (threshold 100g).',
            'recommended_action' => 'Place purchase order for 5kg espresso beans.',
        ]);
        $response->assertRedirect();

        $action = ProductAction::where('product_id', $this->latte->id)->latest('id')->firstOrFail();
        $this->assertSame('Needs Review', $action->owner_decision);
        $this->assertSame('needs_review', $action->status);

        // 2. Owner approves and assigns to Manager
        $this->actingAs($this->owner)->patch(route('reports.decision-dashboard.actions.decision', $action), [
            'owner_decision' => 'Approved',
            'assigned_to_user_id' => $this->mainManager->id,
            'due_date' => now()->addDays(3)->toDateString(),
            'decision_notes' => 'Approved. Order high-grade beans.',
        ])->assertRedirect();

        $action->refresh();
        $this->assertSame('Approved', $action->owner_decision);
        $this->assertSame('approved', $action->status);

        // 3. Manager marks in progress
        $this->actingAs($this->mainManager)->patch(route('reports.decision-dashboard.actions.status', $action), [
            'status' => 'in_progress',
            'completion_notes' => 'Supplier contacted; shipment arriving tomorrow.',
        ])->assertRedirect();

        $action->refresh();
        $this->assertSame('in_progress', $action->status);

        // 4. Manager completes action with completion notes
        $this->actingAs($this->mainManager)->patch(route('reports.decision-dashboard.actions.status', $action), [
            'status' => 'completed',
            'completion_notes' => 'Delivered 5kg beans into stock and verified barista grind calibration.',
            'follow_up_start' => now()->toDateString(),
            'follow_up_end' => now()->addDays(14)->toDateString(),
        ])->assertRedirect();

        $action->refresh();
        $this->assertSame('completed', $action->status);
        $this->assertSame('Completed', $action->owner_decision);
        $this->assertNotNull($action->completed_at);
        $this->assertStringContainsString('Delivered 5kg beans', $action->completion_notes);
    }

    public function test_audit_history_preservation(): void
    {
        $action = ProductAction::create([
            'action_code' => 'ACT-AUDIT-001',
            'branch_id' => $this->mainBranch->id,
            'product_id' => $this->latte->id,
            'finding_category' => 'Declining sales',
            'evidence_summary' => 'Volume down 25%',
            'recommended_action' => 'Staff beverage tasting',
            'owner_decision' => 'Needs Review',
            'status' => 'needs_review',
            'created_by_user_id' => $this->owner->id,
            'created_by_name' => $this->owner->name,
        ]);
        ProductActionAudit::create([
            'product_action_id' => $action->id,
            'user_id' => $this->owner->id,
            'user_name' => $this->owner->name,
            'user_role' => $this->owner->role,
            'event' => 'created',
            'new_decision' => 'Needs Review',
            'new_status' => 'needs_review',
        ]);

        // Owner approves
        $this->actingAs($this->owner)->patch(route('reports.decision-dashboard.actions.decision', $action), [
            'owner_decision' => 'Approved',
            'assigned_to_user_id' => $this->mainManager->id,
        ]);

        // Manager completes
        $this->actingAs($this->mainManager)->patch(route('reports.decision-dashboard.actions.status', $action), [
            'status' => 'completed',
            'completion_notes' => 'Tasting completed with baristas.',
        ]);

        $audits = $action->audits;
        $this->assertGreaterThanOrEqual(3, $audits->count());
        $this->assertTrue($audits->contains('event', 'decision_recorded'));
        $this->assertTrue($audits->contains('event', 'completed'));

        // Check global AuditLog table
        $this->assertTrue(AuditLog::where('action', 'product_action_decision_recorded')->exists());
        $this->assertTrue(AuditLog::where('action', 'product_action_status_updated')->exists());
    }

    public function test_correct_follow_up_metrics(): void
    {
        $timezone = config('app.business_timezone', 'Asia/Manila');
        $baselineStart = '2026-09-01';
        $baselineEnd = '2026-09-10';
        $followUpStart = '2026-09-15';
        $followUpEnd = '2026-09-24';

        // 10 units sold during baseline (10 * 150 = 1,500)
        $this->createOrder($this->mainBranch, $this->latte, $this->latteSize, 10, CarbonImmutable::parse('2026-09-05', $timezone));

        // 15 units sold during follow-up (15 * 150 = 2,250)
        $this->createOrder($this->mainBranch, $this->latte, $this->latteSize, 15, CarbonImmutable::parse('2026-09-20', $timezone));

        $action = ProductAction::create([
            'action_code' => 'ACT-FU-001',
            'branch_id' => $this->mainBranch->id,
            'product_id' => $this->latte->id,
            'finding_category' => 'Increasing sales',
            'evidence_summary' => 'Baseline test',
            'recommended_action' => 'Promote Latte',
            'period_start' => $baselineStart,
            'period_end' => $baselineEnd,
            'follow_up_start' => $followUpStart,
            'follow_up_end' => $followUpEnd,
            'status' => 'completed',
            'completed_at' => CarbonImmutable::parse('2026-09-14', $timezone),
            'owner_decision' => 'Completed',
        ]);

        $analytics = app(ProductAnalyticsService::class);
        $evaluation = $analytics->evaluateActionFollowUp($action);

        $this->assertTrue($evaluation['has_data']);
        $this->assertSame(10, $evaluation['before']['units']);
        $this->assertSame(15, $evaluation['after']['units']);
        $this->assertSame(5, $evaluation['changes']['unit_delta']);
        $this->assertEquals(50.0, $evaluation['changes']['unit_delta_percent']);
        $this->assertEquals(750.00, $evaluation['changes']['revenue_delta']);
        $this->assertStringContainsString('not causal proof', $evaluation['disclaimer']);
    }

    public function test_ai_suggestions_and_actions_cannot_directly_change_prices_or_inventory(): void
    {
        $initialPrice = (float) $this->latteSize->price;
        $initialStock = (float) $this->espressoBeans->inventory()->where('branch_id', $this->mainBranch->id)->sum('current_stock');

        // Manager logs action and Owner approves and marks completed
        $response = $this->actingAs($this->owner)->post(route('reports.decision-dashboard.actions.store'), [
            'product_id' => $this->latte->id,
            'branch_id' => $this->mainBranch->id,
            'finding_category' => 'High sales',
            'evidence_summary' => 'Top volume product',
            'recommended_action' => 'Consider a price adjustment from ₱150 to ₱160.',
            'owner_decision' => 'Approved',
        ]);
        $response->assertRedirect();

        $action = ProductAction::where('product_id', $this->latte->id)->latest('id')->first();
        $this->actingAs($this->owner)->patch(route('reports.decision-dashboard.actions.status', $action), [
            'status' => 'completed',
            'completion_notes' => 'Evaluated and concluded current price remains competitive.',
        ])->assertRedirect();

        // Product size price MUST remain strictly ₱150.00
        $this->assertEquals($initialPrice, (float) $this->latteSize->fresh()->price);

        // Ingredient stock MUST remain strictly unchanged (no automated stock adjustments)
        $currentStock = (float) $this->espressoBeans->inventory()->where('branch_id', $this->mainBranch->id)->sum('current_stock');
        $this->assertEquals($initialStock, $currentStock);
    }

    public function test_action_tracking_does_not_change_sales_totals(): void
    {
        // One sale of ₱300
        $this->createOrder($this->mainBranch, $this->latte, $this->latteSize, 2, now());

        $initialSales = (float) Order::where('status', 'completed')->sum('total');
        $this->assertEquals(300.00, $initialSales);

        // Create 3 actions
        for ($i = 1; $i <= 3; $i++) {
            ProductAction::create([
                'action_code' => "ACT-SALES-{$i}",
                'branch_id' => $this->mainBranch->id,
                'product_id' => $this->latte->id,
                'finding_category' => 'Low sales',
                'evidence_summary' => 'Test sales audit',
                'recommended_action' => 'Monitor',
                'owner_decision' => 'Approved',
                'status' => 'approved',
            ]);
        }

        // Sales totals in sales report and order ledger remain exactly ₱300.00
        $salesReportResponse = $this->actingAs($this->owner)->get(route('reports.sales', ['type' => 'all']));
        $this->assertEquals(300.00, (float) $salesReportResponse->viewData('totalSales'));
        $this->assertSame(1, (int) $salesReportResponse->viewData('totalOrders'));
    }

    public function test_filters_match_displayed_report_data(): void
    {
        $today = now();

        // 1. POS order for Latte (₱150)
        $posOrder = $this->createOrder($this->mainBranch, $this->latte, $this->latteSize, 1, $today, 'dine_in');

        // 2. Grab order for Croissant (₱240: 2 croissants)
        $grabOrder = $this->createOrder($this->mainBranch, $this->croissant, $this->croissantSize, 2, $today, 'grab');

        // Query with POS channel filter only
        $posResponse = $this->actingAs($this->owner)->get(route('reports.decision-dashboard.index', [
            'type' => 'daily',
            'date' => $today->toDateString(),
            'channel' => 'pos',
        ]));
        $posResponse->assertOk();
        $posPerformance = $posResponse->viewData('productPerformance');

        $this->assertSame(1, $posPerformance->firstWhere('id', $this->latte->id)->units_sold);
        $this->assertSame(0, $posPerformance->firstWhere('id', $this->croissant->id)->units_sold);

        // Query with Grab channel filter only
        $grabResponse = $this->actingAs($this->owner)->get(route('reports.decision-dashboard.index', [
            'type' => 'daily',
            'date' => $today->toDateString(),
            'channel' => 'grab',
        ]));
        $grabResponse->assertOk();
        $grabPerformance = $grabResponse->viewData('productPerformance');

        $this->assertSame(0, $grabPerformance->firstWhere('id', $this->latte->id)->units_sold);
        $this->assertSame(2, $grabPerformance->firstWhere('id', $this->croissant->id)->units_sold);

        // Query with Category filter (Coffee only)
        $coffeeResponse = $this->actingAs($this->owner)->get(route('reports.decision-dashboard.index', [
            'type' => 'daily',
            'date' => $today->toDateString(),
            'category_id' => $this->coffeeCategory->id,
        ]));
        $coffeeResponse->assertOk();
        $coffeePerformance = $coffeeResponse->viewData('productPerformance');

        $this->assertTrue($coffeePerformance->contains('id', $this->latte->id));
        $this->assertFalse($coffeePerformance->contains('id', $this->croissant->id));
    }

    private function createOrder(
        Branch $branch,
        Product $product,
        ProductSize $size,
        int $quantity,
        $dateTime,
        string $orderType = 'dine_in'
    ): Order {
        $subtotal = round($size->price * $quantity, 2);
        $order = Order::create([
            'order_number' => 'ORD-TEST-'.uniqid(),
            'branch_id' => $branch->id,
            'cashier_name' => 'Cashier Test',
            'order_type' => $orderType,
            'subtotal' => $subtotal,
            'discount' => 0,
            'total' => $subtotal,
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);
        $order->forceFill([
            'created_at' => $dateTime,
            'updated_at' => $dateTime,
        ])->saveQuietly();

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_size_id' => $size->id,
            'quantity' => $quantity,
            'unit_price' => $size->price,
            'subtotal' => $subtotal,
            'payable_total' => $subtotal,
            'status' => 'active',
        ]);
        $item->forceFill([
            'created_at' => $dateTime,
            'updated_at' => $dateTime,
        ])->saveQuietly();

        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => 'cash',
            'amount_paid' => $subtotal,
            'amount_received' => $subtotal,
            'status' => 'paid',
        ]);
        $payment->forceFill([
            'created_at' => $dateTime,
            'updated_at' => $dateTime,
        ])->saveQuietly();

        return $order;
    }
}
