<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Recipe;
use App\Models\User;
use App\Models\VoidLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosSplitPartialVoidIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;
    private User $manager;
    private User $owner;
    private ProductSize $latteSize;
    private ProductSize $matchaSize;
    private Ingredient $coffeeBeans;
    private Ingredient $matchaPowder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => 'owner', 'status' => 'active']);
        $this->manager = User::factory()->create(['role' => 'manager', 'status' => 'active']);
        $this->cashier = User::factory()->create(['role' => 'cashier', 'status' => 'active']);

        $this->actingAs($this->cashier)
            ->postJson(route('shifts.start'), ['beginning_cash' => 500])
            ->assertOk();

        $category = Category::create(['name' => 'Beverages', 'status' => 'active']);

        $latte = Product::create(['category_id' => $category->id, 'name' => 'Caffe Latte', 'status' => 'active']);
        $this->latteSize = ProductSize::create([
            'product_id' => $latte->id,
            'size_name' => 'Regular',
            'price' => 200.00,
            'cost' => 50.00,
            'status' => 'active',
        ]);

        $matcha = Product::create(['category_id' => $category->id, 'name' => 'Matcha Latte', 'status' => 'active']);
        $this->matchaSize = ProductSize::create([
            'product_id' => $matcha->id,
            'size_name' => 'Large',
            'price' => 300.00,
            'cost' => 70.00,
            'status' => 'active',
        ]);

        $this->coffeeBeans = Ingredient::create([
            'name' => 'Espresso Beans',
            'unit' => 'g',
            'minimum_stock' => 100,
            'status' => 'active',
        ]);
        $this->coffeeBeans->inventory()->create([
            'current_stock' => 1000,
            'cost_per_unit' => 0.50,
        ]);

        $this->matchaPowder = Ingredient::create([
            'name' => 'Matcha Powder',
            'unit' => 'g',
            'minimum_stock' => 50,
            'status' => 'active',
        ]);
        $this->matchaPowder->inventory()->create([
            'current_stock' => 1000,
            'cost_per_unit' => 0.80,
        ]);

        $latteRecipe = Recipe::create(['product_size_id' => $this->latteSize->id]);
        $latteRecipe->recipeIngredients()->create([
            'ingredient_id' => $this->coffeeBeans->id,
            'quantity' => 20, // 20g per cup
        ]);

        $matchaRecipe = Recipe::create(['product_size_id' => $this->matchaSize->id]);
        $matchaRecipe->recipeIngredients()->create([
            'ingredient_id' => $this->matchaPowder->id,
            'quantity' => 15, // 15g per cup
        ]);
    }

    public function test_split_payment_records_multiple_payers_portions_and_deducts_inventory_once(): void
    {
        $response = $this->actingAs($this->cashier)->postJson(route('pos.store'), [
            'cashier_name' => 'Jane Cashier',
            'customer_name' => 'Table 10',
            'payment_method' => 'cash',
            'amount_received' => 500,
            'split_payment' => 1,
            'split_portions' => [
                ['payer_name' => 'Alice', 'amount' => 200],
                ['payer_name' => 'Bob', 'amount' => 300],
            ],
            'payments' => [
                [
                    'method' => 'cash',
                    'amount_paid' => 200,
                    'amount_received' => 250,
                    'person_name' => 'Alice',
                ],
                [
                    'method' => 'online',
                    'amount_paid' => 300,
                    'amount_received' => 300,
                    'person_name' => 'Bob',
                    'reference_number' => 'REF-BOB-9988',
                ],
            ],
            'items' => [
                ['product_size_id' => $this->latteSize->id, 'quantity' => 1, 'assigned_to' => 'Alice'],
                ['product_size_id' => $this->matchaSize->id, 'quantity' => 1, 'assigned_to' => 'Bob'],
            ],
        ])->assertCreated();

        $orderId = $response->json('order.id');
        $order = Order::with(['payments', 'paymentPortions', 'orderItems'])->findOrFail($orderId);

        $this->assertSame('completed', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertEquals(500.00, (float) $order->total);
        $this->assertEquals(500.00, $order->paidAmount());
        $this->assertEquals(0.00, $order->remainingBalance());

        // Check payment portions
        $this->assertCount(2, $order->paymentPortions);
        $alicePortion = $order->paymentPortions->firstWhere('payer_name', 'Alice');
        $this->assertNotNull($alicePortion);
        $this->assertEquals(200.00, (float) $alicePortion->due_amount);
        $this->assertEquals(200.00, (float) $alicePortion->amount_paid);
        $this->assertSame('paid', $alicePortion->status);

        $bobPortion = $order->paymentPortions->firstWhere('payer_name', 'Bob');
        $this->assertNotNull($bobPortion);
        $this->assertEquals(300.00, (float) $bobPortion->due_amount);
        $this->assertEquals(300.00, (float) $bobPortion->amount_paid);
        $this->assertSame('paid', $bobPortion->status);

        // Check individual payments
        $this->assertCount(2, $order->payments);
        $alicePayment = $order->payments->firstWhere('customer_name', 'Alice');
        $this->assertSame('cash', $alicePayment->method);
        $this->assertEquals(200.00, (float) $alicePayment->amount_paid);
        $this->assertEquals(250.00, (float) $alicePayment->amount_received);
        $this->assertEquals(50.00, (float) $alicePayment->change_amount);
        $this->assertSame('paid', $alicePayment->status);

        $bobPayment = $order->payments->firstWhere('customer_name', 'Bob');
        $this->assertSame('online', $bobPayment->method);
        $this->assertEquals(300.00, (float) $bobPayment->amount_paid);
        $this->assertSame('REF-BOB-9988', $bobPayment->reference_number);
        $this->assertSame('paid', $bobPayment->status);

        // Inventory must be deducted exactly once
        $this->assertEquals(980.00, (float) $this->coffeeBeans->inventory()->first()->current_stock);
        $this->assertEquals(985.00, (float) $this->matchaPowder->inventory()->first()->current_stock);
        $this->assertSame(2, InventoryTransaction::where('type', 'sales_consumption')->count());
    }

    public function test_partial_payment_tracks_balance_and_completes_when_settled_without_duplicate_inventory_deduction(): void
    {
        // Initial inventory stock
        $this->assertEquals(1000.00, (float) $this->coffeeBeans->inventory()->first()->current_stock);

        // Step 1: Cashier creates order with partial payment: total ₱400 (2 Lattes @ ₱200), paid ₱150
        $response = $this->actingAs($this->cashier)->postJson(route('pos.store'), [
            'cashier_name' => 'Jane Cashier',
            'payment_method' => 'cash',
            'amount_paid' => 150.00,
            'amount_received' => 200.00,
            'items' => [
                ['product_size_id' => $this->latteSize->id, 'quantity' => 2],
            ],
        ])->assertCreated();

        $orderId = $response->json('order.id');
        $order = Order::findOrFail($orderId);

        $this->assertSame('partially_paid', $order->status);
        $this->assertSame('partially_paid', $order->payment_status);
        $this->assertEquals(400.00, (float) $order->total);
        $this->assertEquals(150.00, $order->paidAmount());
        $this->assertEquals(250.00, $order->remainingBalance());

        // Inventory deducted exactly once (2 cups * 20g = 40g deducted)
        $this->assertEquals(960.00, (float) $this->coffeeBeans->inventory()->first()->current_stock);
        $this->assertSame(1, InventoryTransaction::where('type', 'sales_consumption')->count());

        // Step 2: Later, cashier reopens order and records payment for remaining balance (₱250)
        $this->actingAs($this->cashier)->postJson(route('orders.payments.store', $order), [
            'payment_method' => 'cash',
            'amount_paid' => 250.00,
            'amount_received' => 300.00,
        ])->assertOk();

        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertEquals(400.00, $order->paidAmount());
        $this->assertEquals(0.00, $order->remainingBalance());

        // Inventory is NOT deducted again! Stock remains exactly 960g
        $this->assertEquals(960.00, (float) $this->coffeeBeans->inventory()->first()->current_stock);
        $this->assertSame(1, InventoryTransaction::where('type', 'sales_consumption')->count());
    }

    public function test_reopened_partial_order_accepts_split_payments_batch_for_remaining_balance(): void
    {
        // Order with total ₱500 (1 Latte ₱200 + 1 Matcha ₱300), partial payment ₱100 recorded at checkout
        $response = $this->actingAs($this->cashier)->postJson(route('pos.store'), [
            'cashier_name' => 'Jane Cashier',
            'payment_method' => 'cash',
            'amount_paid' => 100.00,
            'amount_received' => 100.00,
            'items' => [
                ['product_size_id' => $this->latteSize->id, 'quantity' => 1],
                ['product_size_id' => $this->matchaSize->id, 'quantity' => 1],
            ],
        ])->assertCreated();

        $order = Order::findOrFail($response->json('order.id'));
        $this->assertEquals(400.00, $order->remainingBalance());

        // Settle remaining ₱400 using batch payments: ₱150 cash from Person A, ₱250 online from Person B
        $this->actingAs($this->cashier)->postJson(route('orders.payments.store', $order), [
            'payments' => [
                [
                    'method' => 'cash',
                    'amount_paid' => 150.00,
                    'amount_received' => 200.00,
                    'person_name' => 'Person A',
                ],
                [
                    'method' => 'online',
                    'amount_paid' => 250.00,
                    'amount_received' => 250.00,
                    'person_name' => 'Person B',
                    'reference_number' => 'REF-BATCH-7788',
                ],
            ],
        ])->assertOk();

        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertEquals(500.00, $order->paidAmount());
        $this->assertEquals(0.00, $order->remainingBalance());
        $this->assertCount(3, $order->payments);

        // Inventory deducted only on creation
        $this->assertEquals(980.00, (float) $this->coffeeBeans->inventory()->first()->current_stock);
        $this->assertEquals(985.00, (float) $this->matchaPowder->inventory()->first()->current_stock);
        $this->assertSame(2, InventoryTransaction::where('type', 'sales_consumption')->count());
    }

    public function test_unpaid_held_order_can_be_voided_without_restoring_stock(): void
    {
        $initialStock = (float) $this->coffeeBeans->inventory()->first()->current_stock;

        // Cashier saves order (hold)
        $heldResponse = $this->actingAs($this->cashier)->postJson(route('pos.hold'), [
            'items' => [
                ['product_size_id' => $this->latteSize->id, 'quantity' => 2],
            ],
        ])->assertOk();

        $heldOrderId = $heldResponse->json('order.id');
        $heldOrder = Order::findOrFail($heldOrderId);
        $this->assertSame('held', $heldOrder->status);
        $this->assertSame('unpaid', $heldOrder->payment_status);

        // Held orders do NOT deduct inventory
        $this->assertEquals($initialStock, (float) $this->coffeeBeans->inventory()->first()->current_stock);
        $this->assertFalse(InventoryTransaction::where('type', 'sales_consumption')->exists());

        // Cashier voids the held ticket via discard-held
        $voidResponse = $this->actingAs($this->cashier)->deleteJson(route('pos.discard-held', $heldOrder), [
            'reason' => 'Customer left before placing order',
        ])->assertOk()->assertJsonPath('success', true);

        $heldOrder->refresh();
        $this->assertSame('voided', $heldOrder->status);
        $this->assertSame('voided', $heldOrder->payment_status);

        // Verify VoidLog was created with stock_restored = false
        $voidLog = VoidLog::where('order_id', $heldOrder->id)->first();
        $this->assertNotNull($voidLog);
        $this->assertFalse($voidLog->stock_restored);
        $this->assertSame('Customer left before placing order', $voidLog->reason);

        // Inventory is still untouched
        $this->assertEquals($initialStock, (float) $this->coffeeBeans->inventory()->first()->current_stock);
        $this->assertFalse(InventoryTransaction::where('type', 'void_restock')->exists());
    }

    public function test_unpaid_saved_split_order_can_be_voided_and_restores_deducted_stock(): void
    {
        $initialStock = (float) $this->coffeeBeans->inventory()->first()->current_stock;

        // Cashier saves an unpaid split order via pos.store with save_split_order = 1
        $response = $this->actingAs($this->cashier)->postJson(route('pos.store'), [
            'cashier_name' => 'Jane Cashier',
            'customer_name' => 'Reservation Table 1',
            'payment_method' => 'cash',
            'amount_received' => 0,
            'split_payment' => 1,
            'save_split_order' => 1,
            'amount_paid' => 0,
            'items' => [
                ['product_size_id' => $this->latteSize->id, 'quantity' => 1, 'assigned_to' => 'Alice'],
            ],
            'split_portions' => [
                ['payer_name' => 'Alice', 'amount' => 200],
            ],
        ])->assertCreated();

        $splitOrder = Order::findOrFail($response->json('order.id'));
        $this->assertSame('pending', $splitOrder->status);
        $this->assertSame('unpaid', $splitOrder->payment_status);

        // Pos store deducted inventory on creation
        $this->assertEquals($initialStock - 20, (float) $this->coffeeBeans->inventory()->first()->current_stock);
        $this->assertTrue(InventoryTransaction::where('type', 'sales_consumption')->exists());

        // Void the saved unpaid split order
        $this->actingAs($this->cashier)->deleteJson(route('pos.discard-held', $splitOrder), [
            'reason' => 'Guest cancelled table reservation',
        ])->assertOk()->assertJsonPath('success', true);

        $splitOrder->refresh();
        $this->assertSame('voided', $splitOrder->status);
        $this->assertSame('voided', $splitOrder->payment_status);

        // VoidLog has stock_restored = true
        $voidLog = VoidLog::where('order_id', $splitOrder->id)->first();
        $this->assertNotNull($voidLog);
        $this->assertTrue($voidLog->stock_restored);

        // Stock was restored back to initial level
        $this->assertEquals($initialStock, (float) $this->coffeeBeans->inventory()->first()->current_stock);
    }

    public function test_voiding_order_with_recorded_payments_via_discard_held_is_rejected(): void
    {
        // Create an order with partial payment
        $response = $this->actingAs($this->cashier)->postJson(route('pos.store'), [
            'cashier_name' => 'Jane Cashier',
            'payment_method' => 'cash',
            'amount_paid' => 100.00,
            'amount_received' => 100.00,
            'items' => [
                ['product_size_id' => $this->latteSize->id, 'quantity' => 1],
            ],
        ])->assertCreated();

        $order = Order::findOrFail($response->json('order.id'));
        $this->assertSame('partially_paid', $order->status);

        // Attempting to void via discardHeld must fail with 422
        $voidResponse = $this->actingAs($this->cashier)->deleteJson(route('pos.discard-held', $order), [
            'reason' => 'Customer changed their mind',
        ]);

        $voidResponse->assertStatus(422);
        $voidResponse->assertJsonValidationErrors('order');
        $this->assertStringContainsString('cannot be voided', $voidResponse->json('errors.order.0'));

        $order->refresh();
        $this->assertSame('partially_paid', $order->status);
        $this->assertDatabaseMissing('void_logs', ['order_id' => $order->id]);
    }

    public function test_voided_and_held_orders_are_excluded_from_sales_reports(): void
    {
        // 1. One completed order: ₱200
        $this->actingAs($this->cashier)->postJson(route('pos.store'), [
            'cashier_name' => 'Jane Cashier',
            'payment_method' => 'cash',
            'amount_paid' => 200.00,
            'amount_received' => 200.00,
            'items' => [
                ['product_size_id' => $this->latteSize->id, 'quantity' => 1],
            ],
        ])->assertCreated();

        // 2. One held order: ₱300
        $this->actingAs($this->cashier)->postJson(route('pos.hold'), [
            'items' => [
                ['product_size_id' => $this->matchaSize->id, 'quantity' => 1],
            ],
        ])->assertOk();

        // 3. One saved split order that was voided: ₱200
        $splitResponse = $this->actingAs($this->cashier)->postJson(route('pos.store'), [
            'cashier_name' => 'Jane Cashier',
            'customer_name' => 'Reservation Table 2',
            'payment_method' => 'cash',
            'amount_received' => 0,
            'split_payment' => 1,
            'save_split_order' => 1,
            'amount_paid' => 0,
            'items' => [
                ['product_size_id' => $this->latteSize->id, 'quantity' => 1, 'assigned_to' => 'Alice'],
            ],
            'split_portions' => [
                ['payer_name' => 'Alice', 'amount' => 200],
            ],
        ])->assertCreated();
        $voidOrder = Order::findOrFail($splitResponse->json('order.id'));
        $this->deleteJson(route('pos.discard-held', $voidOrder), [
            'reason' => 'Voiding test split ticket',
        ])->assertOk();

        // Check sales report
        $reportResponse = $this->actingAs($this->owner)->get(route('reports.sales', ['type' => 'all']));
        $reportResponse->assertOk();

        // Only the 1 completed order (₱200) should be included in sales
        $totalSales = $reportResponse->viewData('totalSales');
        $totalOrders = $reportResponse->viewData('totalOrders');

        $this->assertEquals(200.00, (float) $totalSales);
        $this->assertSame(1, (int) $totalOrders);
    }

    public function test_combined_section_7_end_to_end_workflow(): void
    {
        $initialStock = (float) $this->coffeeBeans->inventory()->first()->current_stock;

        // Step 1: Save ₱1,000 order (5 Lattes @ ₱200) in POS
        $holdResponse = $this->actingAs($this->cashier)->postJson(route('pos.hold'), [
            'items' => [
                ['product_size_id' => $this->latteSize->id, 'quantity' => 5],
            ],
        ])->assertOk();
        $heldOrderId = $holdResponse->json('order.id');

        // Step 2: Resume ticket from Saved Orders
        $resumeResponse = $this->postJson(route('pos.resume-held', ['order' => $heldOrderId]))
            ->assertOk();
        $this->assertSame($heldOrderId, $resumeResponse->json('data.held_order_id'));

        // Step 3: Checkout with partial payment of ₱300
        $partialCheckoutResponse = $this->postJson(route('pos.store'), [
            'held_order_id' => $heldOrderId,
            'cashier_name' => 'Jane Cashier',
            'payment_method' => 'cash',
            'amount_paid' => 300.00,
            'amount_received' => 300.00,
            'items' => [
                ['product_size_id' => $this->latteSize->id, 'quantity' => 5],
            ],
        ])->assertCreated();

        $order = Order::findOrFail($heldOrderId);
        $this->assertSame('partially_paid', $order->status);
        $this->assertSame('partially_paid', $order->payment_status);
        $this->assertEquals(1000.00, (float) $order->total);
        $this->assertEquals(300.00, $order->paidAmount());
        $this->assertEquals(700.00, $order->remainingBalance());

        // Inventory deducted exactly once (5 * 20g = 100g)
        $this->assertEquals($initialStock - 100, (float) $this->coffeeBeans->inventory()->first()->current_stock);
        $this->assertSame(1, InventoryTransaction::where('type', 'sales_consumption')->count());

        // Step 4: Reopen order in Order Management and pay remaining ₱700 split across two payers
        $settleResponse = $this->postJson(route('orders.payments.store', $order), [
            'payments' => [
                [
                    'method' => 'cash',
                    'amount_paid' => 200.00,
                    'amount_received' => 200.00,
                    'person_name' => 'Payer A',
                ],
                [
                    'method' => 'online',
                    'amount_paid' => 500.00,
                    'amount_received' => 500.00,
                    'person_name' => 'Payer B',
                    'reference_number' => 'REF-FINAL-700',
                ],
            ],
        ])->assertOk();

        // Step 5: Verify Order Completed and fully paid
        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertEquals(1000.00, $order->paidAmount());
        $this->assertEquals(0.00, $order->remainingBalance());

        // Verify inventory was NOT deducted again
        $this->assertEquals($initialStock - 100, (float) $this->coffeeBeans->inventory()->first()->current_stock);
        $this->assertSame(1, InventoryTransaction::where('type', 'sales_consumption')->count());

        // Step 6: Create a separate unpaid saved ticket and void it
        $unpaidHeldResponse = $this->postJson(route('pos.hold'), [
            'items' => [
                ['product_size_id' => $this->matchaSize->id, 'quantity' => 2],
            ],
        ])->assertOk();
        $unpaidOrderId = $unpaidHeldResponse->json('order.id');
        $unpaidOrder = Order::findOrFail($unpaidOrderId);

        $this->deleteJson(route('pos.discard-held', $unpaidOrder), [
            'reason' => 'Customer canceled table before payment',
        ])->assertOk()->assertJsonPath('success', true);

        $unpaidOrder->refresh();
        $this->assertSame('voided', $unpaidOrder->status);
        $this->assertDatabaseHas('void_logs', [
            'order_id' => $unpaidOrderId,
            'stock_restored' => false,
        ]);

        // Step 7: Sales report confirms exactly 1 order completed with ₱1,000 total
        $salesResponse = $this->actingAs($this->owner)->get(route('reports.sales', ['type' => 'all']));
        $salesResponse->assertOk();
        $this->assertEquals(1000.00, (float) $salesResponse->viewData('totalSales'));
        $this->assertSame(1, (int) $salesResponse->viewData('totalOrders'));
    }
}
