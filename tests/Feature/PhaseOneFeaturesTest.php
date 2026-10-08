<?php

namespace Tests\Feature;

use App\Models\AddonIngredient;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductSize;
use App\Models\Recipe;
use App\Models\TaxSetting;
use App\Models\User;
use App\Models\VoidLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $manager;

    private User $cashier;

    private ProductSize $size;

    private Ingredient $coffeeBeans;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => 'owner', 'status' => 'active']);
        $this->manager = User::factory()->create(['role' => 'manager', 'status' => 'active']);
        $this->cashier = User::factory()->create(['role' => 'cashier', 'status' => 'active']);
        $this->actingAs($this->manager)->postJson(route('shifts.start'), ['beginning_cash' => 500])->assertOk();
        $this->actingAs($this->cashier)->postJson(route('shifts.start'), ['beginning_cash' => 500])->assertOk();

        $category = Category::create(['name' => 'Espresso', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Caramel Macchiato', 'status' => 'active']);
        $this->size = ProductSize::create([
            'product_id' => $product->id,
            'size_name' => 'Regular',
            'price' => 150.00,
            'cost' => 50.00,
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

        $recipe = Recipe::create(['product_size_id' => $this->size->id]);
        $recipe->recipeIngredients()->create([
            'ingredient_id' => $this->coffeeBeans->id,
            'quantity' => 20, // 20g per cup
        ]);
    }

    public function test_pos_order_item_saves_comment_and_special_instructions()
    {
        $response = $this->actingAs($this->cashier)->post(route('pos.store'), [
            'cashier_name' => 'Barista Joe',
            'payment_method' => 'cash',
            'amount_received' => 200,
            'items' => [
                [
                    'product_size_id' => $this->size->id,
                    'quantity' => 1,
                    'comment' => 'Extra hot, no caramel drizzle',
                ],
            ],
        ]);

        $order = Order::latest()->first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('pos.success', $order));

        $item = $order->orderItems()->first();
        $this->assertNotNull($item);
        $this->assertEquals('Extra hot, no caramel drizzle', $item->comment);
        $this->assertEquals('active', $item->status);

        // Verify stock deducted
        $this->coffeeBeans->refresh();
        $this->assertEquals(980, $this->coffeeBeans->getCurrentStock());
    }

    public function test_tax_computation_uses_stable_two_decimal_rounding(): void
    {
        $taxSetting = TaxSetting::create([
            'name' => 'VAT',
            'rate' => 12.00,
            'is_inclusive' => true,
            'is_active' => true,
        ]);

        $computed = $taxSetting->computeOrder(999.99);

        $this->assertSame(999.99, round((float) $computed['total'], 2));
        $this->assertSame(892.85, round((float) $computed['vatable_sales'], 2));
        $this->assertSame(107.14, round((float) $computed['tax_amount'], 2));
        $this->assertEqualsWithDelta(999.99, (float) $computed['vatable_sales'] + (float) $computed['tax_amount'], 0.01);
    }

    public function test_can_hold_order_without_payment_or_stock_deduction()
    {
        $response = $this->actingAs($this->cashier)->postJson(route('pos.hold'), [
            'cashier_name' => 'Barista Joe',
            'items' => [
                [
                    'product_size_id' => $this->size->id,
                    'quantity' => 2,
                    'comment' => 'Less ice',
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $heldOrder = Order::where('status', 'held')->first();
        $this->assertNotNull($heldOrder);
        $this->assertNotNull($heldOrder->held_at);
        $this->assertFalse($heldOrder->is_pinned);
        $this->assertEquals(300.00, (float) $heldOrder->total);

        // Verify stock was NOT deducted for held order
        $this->coffeeBeans->refresh();
        $this->assertEquals(1000, $this->coffeeBeans->getCurrentStock());
    }

    public function test_can_hold_and_resume_an_assigned_grab_order_without_losing_grab_pricing_or_deducting_stock(): void
    {
        $this->size->update(['grab_price' => 180]);

        $response = $this->actingAs($this->cashier)->postJson(route('pos.hold'), [
            'order_type' => 'grab',
            'grab_order_code' => 'GF-HOLD-100',
            'rider_code' => 'RDR-HOLD-100',
            'customer_name' => 'Saved Grab Customer',
            'cashier_name' => $this->cashier->name,
            'items' => [[
                'product_size_id' => $this->size->id,
                'quantity' => 1,
                'assigned_to' => 'Alex',
                'comment' => 'Less ice',
            ]],
        ]);

        $response->assertOk()
            ->assertJsonPath('order.order_type', 'grab')
            ->assertJsonPath('order.total', '180.00');

        $heldOrder = Order::where('status', 'held')->firstOrFail();
        $this->assertSame('grab', $heldOrder->order_type);
        $this->assertSame('GF-HOLD-100', $heldOrder->grab_order_code);
        $this->assertSame('Alex', $heldOrder->orderItems->first()->assigned_to);
        $this->assertSame(0, $heldOrder->payments()->count());
        $this->assertEquals(1000, $this->coffeeBeans->fresh()->getCurrentStock());

        $this->actingAs($this->cashier)->postJson(route('pos.resume-held', $heldOrder))
            ->assertOk()
            ->assertJsonPath('data.order_type', 'grab')
            ->assertJsonPath('data.grab_order_code', 'GF-HOLD-100')
            ->assertJsonPath('data.rider_code', 'RDR-HOLD-100')
            ->assertJsonPath('data.customer_name', 'Saved Grab Customer')
            ->assertJsonPath('data.cart.0.assigned_to', 'Alex')
            ->assertJsonPath('data.cart.0.regular_price', 150)
            ->assertJsonPath('data.cart.0.grab_price', 180)
            ->assertJsonPath('data.cart.0.price', 180);

        $this->assertDatabaseHas('orders', ['id' => $heldOrder->id, 'status' => 'held']);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_can_pin_and_unpin_held_orders()
    {
        $order = Order::create([
            'order_number' => 'ORD-HELD-001',
            'cashier_name' => 'Barista Joe',
            'subtotal' => 150,
            'total' => 150,
            'tax_name' => 'VAT',
            'tax_rate' => 12,
            'tax_amount' => 16.07,
            'vatable_sales' => 133.93,
            'status' => 'held',
            'held_at' => now(),
            'is_pinned' => false,
        ]);

        // Pin
        $response = $this->actingAs($this->cashier)->patchJson(route('pos.toggle-pin', $order));
        $response->assertOk();
        $response->assertJson(['success' => true, 'is_pinned' => true]);
        $this->assertTrue($order->fresh()->is_pinned);

        // Unpin
        $response = $this->actingAs($this->cashier)->patchJson(route('pos.toggle-pin', $order));
        $response->assertOk();
        $response->assertJson(['success' => true, 'is_pinned' => false]);
        $this->assertFalse($order->fresh()->is_pinned);
    }

    public function test_can_resume_held_order_and_rehydrate_cart()
    {
        $order = Order::create([
            'order_number' => 'ORD-HELD-002',
            'cashier_name' => 'Barista Joe',
            'subtotal' => 150,
            'total' => 150,
            'tax_name' => 'VAT',
            'tax_rate' => 12,
            'tax_amount' => 16.07,
            'vatable_sales' => 133.93,
            'status' => 'held',
            'held_at' => now(),
            'is_pinned' => false,
        ]);

        $order->orderItems()->create([
            'product_id' => $this->size->product_id,
            'product_size_id' => $this->size->id,
            'quantity' => 1,
            'unit_price' => 150,
            'subtotal' => 150,
            'comment' => 'No sugar',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->cashier)->postJson(route('pos.resume-held', $order));
        $response->assertOk();
        $response->assertJson(['success' => true]);

        // Original held order is removed
        $this->assertSame('held', Order::findOrFail($order->id)->status);

        // Payload contains re-hydrated cart
        $data = $response->json('data');
        $this->assertCount(1, $data['cart']);
        $this->assertEquals('No sugar', $data['cart'][0]['comment']);
    }

    public function test_discarding_held_order_requires_a_reason()
    {
        $order = Order::create([
            'order_number' => 'ORD-HELD-003',
            'cashier_name' => 'Barista Joe',
            'subtotal' => 150,
            'total' => 150,
            'tax_name' => 'VAT',
            'tax_rate' => 12,
            'tax_amount' => 16.07,
            'vatable_sales' => 133.93,
            'status' => 'held',
            'held_at' => now(),
        ]);

        $response = $this->actingAs($this->cashier)->deleteJson(route('pos.discard-held', $order), []);
        $response->assertUnprocessable()->assertJsonValidationErrors('reason');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'held']);

        $this->deleteJson(route('pos.discard-held', $order), ['reason' => '    '])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'held']);

        $response = $this->deleteJson(route('pos.discard-held', $order), ['reason' => 'Customer cancelled']);
        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertNull(Order::find($order->id));
    }

    public function test_manager_can_void_full_order_and_restore_stock()
    {
        // 1. Create completed order
        $this->actingAs($this->cashier)->post(route('pos.store'), [
            'cashier_name' => 'Barista Joe',
            'payment_method' => 'cash',
            'amount_received' => 400,
            'items' => [
                [
                    'product_size_id' => $this->size->id,
                    'quantity' => 2, // Deducts 40g
                    'comment' => 'Takeaway',
                ],
            ],
        ]);

        $order = Order::latest()->first();
        $this->coffeeBeans->refresh();
        $this->assertEquals(960, $this->coffeeBeans->getCurrentStock());

        // 2. Void order by manager
        $response = $this->actingAs($this->manager)->post(route('orders.void', $order), [
            'reason' => 'Customer changed mind after punch-in',
        ]);

        $response->assertRedirect(route('orders.show', $order));
        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals('voided', $order->status);

        // Stock restored
        $this->coffeeBeans->refresh();
        $this->assertEquals(1000, $this->coffeeBeans->getCurrentStock());

        // VoidLog created
        $voidLog = VoidLog::where('order_id', $order->id)->first();
        $this->assertNotNull($voidLog);
        $this->assertEquals('order', $voidLog->void_type);
        $this->assertEquals($this->manager->name, $voidLog->authorized_by);
        $this->assertEquals('manager', $voidLog->authorized_role);
        $this->assertEquals($this->manager->name, $voidLog->requested_by);
        $this->assertTrue($voidLog->stock_restored);
    }

    public function test_full_order_void_rejects_saved_and_cancelled_tickets(): void
    {
        $heldOrderData = $this->actingAs($this->manager)->postJson(route('pos.hold'), [
            'items' => [['product_size_id' => $this->size->id, 'quantity' => 1]],
        ])->assertOk()->json('order');
        $heldOrder = Order::findOrFail($heldOrderData['id']);

        $this->postJson(route('orders.void', $heldOrder), [
            'reason' => 'Attempt to void held order',
        ])->assertStatus(422)
            ->assertJsonPath('error', 'Saved tickets must be voided from Saved Orders.');
        $this->assertSame('held', $heldOrder->fresh()->status);
        $this->assertDatabaseMissing('void_logs', ['order_id' => $heldOrder->id]);

        $cancelledOrder = Order::create([
            'order_number' => 'ORD-CANCELLED-VOID-REJECT',
            'cashier_name' => $this->cashier->name,
            'subtotal' => 150,
            'total' => 150,
            'status' => 'cancelled',
        ]);
        $this->postJson(route('orders.void', $cancelledOrder), [
            'reason' => 'Attempt to re-void cancelled order',
        ])->assertStatus(422)
            ->assertJsonPath('error', 'Order cannot be voided because it is already cancelled.');
        $this->assertSame('cancelled', $cancelledOrder->fresh()->status);
        $this->assertDatabaseMissing('void_logs', ['order_id' => $cancelledOrder->id]);
    }

    public function test_cashier_can_void_order_with_manager_authorization()
    {
        // Create completed order
        $this->actingAs($this->cashier)->post(route('pos.store'), [
            'cashier_name' => 'Barista Joe',
            'payment_method' => 'cash',
            'amount_received' => 200,
            'items' => [
                [
                    'product_size_id' => $this->size->id,
                    'quantity' => 1, // Deducts 20g
                ],
            ],
        ]);

        $order = Order::latest()->first();
        $this->coffeeBeans->refresh();
        $this->assertEquals(980, $this->coffeeBeans->getCurrentStock());

        // Cashier submits void with manager credentials
        $response = $this->actingAs($this->cashier)->post(route('orders.void', $order), [
            'reason' => 'Cashier entered wrong drink',
            'authorizer_email' => $this->manager->email,
            'authorizer_password' => 'password',
        ]);

        $response->assertRedirect(route('orders.show', $order));
        $order->refresh();
        $this->assertEquals('voided', $order->status);
        $this->assertDatabaseHas('void_logs', [
            'order_id' => $order->id,
            'requested_by' => $this->cashier->name,
            'authorized_by' => $this->manager->name,
        ]);

        // Stock restored
        $this->coffeeBeans->refresh();
        $this->assertEquals(1000, $this->coffeeBeans->getCurrentStock());
    }

    public function test_void_single_item_restores_only_that_items_stock()
    {
        // Order with 2 cups
        $this->actingAs($this->cashier)->post(route('pos.store'), [
            'cashier_name' => 'Barista Joe',
            'payment_method' => 'cash',
            'amount_received' => 400,
            'items' => [
                [
                    'product_size_id' => $this->size->id,
                    'quantity' => 1, // Item 1 (20g)
                    'comment' => 'Item 1',
                ],
                [
                    'product_size_id' => $this->size->id,
                    'quantity' => 1, // Item 2 (20g)
                    'comment' => 'Item 2',
                ],
            ],
        ]);

        $order = Order::latest()->first();
        $this->coffeeBeans->refresh();
        $this->assertEquals(960, $this->coffeeBeans->getCurrentStock());

        $firstItem = $order->orderItems()->first();

        // Void first item
        $response = $this->actingAs($this->manager)->post(route('orders.items.void', [$order, $firstItem]), [
            'reason' => 'Item was spilled before serving',
        ]);

        $response->assertRedirect(route('orders.show', $order));
        $firstItem->refresh();
        $this->assertEquals('voided', $firstItem->status);

        // 20g restored, remaining item (20g) is still deducted
        $this->coffeeBeans->refresh();
        $this->assertEquals(980, $this->coffeeBeans->getCurrentStock());

        $voidLog = VoidLog::where('order_item_id', $firstItem->id)->first();
        $this->assertNotNull($voidLog);
        $this->assertEquals('item', $voidLog->void_type);
    }

    public function test_item_void_restores_the_recorded_quantity_after_recipe_changes(): void
    {
        $this->actingAs($this->cashier)->post(route('pos.store'), [
            'cashier_name' => $this->cashier->name,
            'payment_method' => 'cash',
            'amount_received' => 400,
            'items' => [[
                'product_size_id' => $this->size->id,
                'quantity' => 1,
            ]],
        ]);

        $order = Order::latest()->firstOrFail();
        $item = $order->orderItems()->firstOrFail();
        $recipe = Recipe::where('product_size_id', $this->size->id)->firstOrFail();
        $recipe->recipeIngredients()->update(['quantity' => 50]);

        $this->actingAs($this->manager)->post(route('orders.items.void', [$order, $item]), [
            'reason' => 'Customer cancelled this item',
        ])->assertRedirect(route('orders.show', $order));

        $this->assertEquals(1000, $this->coffeeBeans->fresh()->getCurrentStock());
        $this->assertDatabaseHas('inventory_transactions', [
            'ingredient_id' => $this->coffeeBeans->id,
            'type' => 'sales_consumption',
            'quantity' => 20,
            'reference_type' => OrderItem::class,
            'reference_id' => $item->id,
        ]);
        $this->assertDatabaseHas('inventory_transactions', [
            'ingredient_id' => $this->coffeeBeans->id,
            'type' => 'sales_return',
            'quantity' => 20,
            'reference_type' => OrderItem::class,
            'reference_id' => $item->id,
        ]);
    }

    public function test_order_void_restores_recorded_addon_usage_after_addon_recipe_changes(): void
    {
        $milk = Ingredient::create([
            'name' => 'Oat Milk',
            'unit' => 'ml',
            'minimum_stock' => 100,
            'status' => 'active',
        ]);
        $milk->inventory()->create(['current_stock' => 500]);
        $addon = ProductAddon::create(['name' => 'Oat Milk', 'price' => 10, 'status' => 'active']);
        $addonIngredient = AddonIngredient::create([
            'product_addon_id' => $addon->id,
            'ingredient_id' => $milk->id,
            'quantity' => 25,
        ]);

        $this->actingAs($this->cashier)->post(route('pos.store'), [
            'cashier_name' => $this->cashier->name,
            'payment_method' => 'cash',
            'amount_received' => 400,
            'items' => [[
                'product_size_id' => $this->size->id,
                'quantity' => 2,
                'addon_ids' => [$addon->id],
            ]],
        ])->assertSessionHasNoErrors();

        $order = Order::latest()->firstOrFail();
        $item = $order->orderItems()->firstOrFail();
        $this->assertSame(450.0, (float) $milk->fresh()->getCurrentStock());
        $addonIngredient->update(['quantity' => 100]);

        $this->actingAs($this->cashier)->post(route('orders.void', $order), [
            'reason' => 'Customer cancelled the order',
            'authorizer_email' => $this->manager->email,
            'authorizer_password' => 'password',
        ])->assertRedirect(route('orders.show', $order));

        $this->assertSame(500.0, (float) $milk->fresh()->getCurrentStock());
        $this->assertDatabaseHas('inventory_transactions', [
            'ingredient_id' => $milk->id,
            'type' => 'sales_consumption',
            'quantity' => 50,
            'reference_type' => OrderItem::class,
            'reference_id' => $item->id,
        ]);
        $this->assertDatabaseHas('inventory_transactions', [
            'ingredient_id' => $milk->id,
            'type' => 'sales_return',
            'quantity' => 50,
            'reference_type' => OrderItem::class,
            'reference_id' => $item->id,
        ]);
    }

    public function test_legacy_order_level_consumption_restores_exact_recorded_quantity_on_full_void(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-LEGACY-VOID-001',
            'cashier_name' => $this->cashier->name,
            'subtotal' => 150,
            'total' => 150,
            'status' => 'completed',
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->size->product_id,
            'product_size_id' => $this->size->id,
            'quantity' => 1,
            'unit_price' => 150,
            'subtotal' => 150,
            'payable_total' => 150,
            'status' => 'active',
        ]);
        $this->coffeeBeans->inventory()->update(['current_stock' => 980]);
        InventoryTransaction::create([
            'ingredient_id' => $this->coffeeBeans->id,
            'type' => 'sales_consumption',
            'quantity' => 20,
            'previous_stock' => 1000,
            'new_stock' => 980,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'reason' => 'Sale: '.$order->order_number,
            'performed_by' => $this->cashier->name,
            'performed_role' => 'cashier',
        ]);
        Recipe::where('product_size_id', $this->size->id)
            ->firstOrFail()
            ->recipeIngredients()
            ->update(['quantity' => 50]);

        $this->actingAs($this->manager)->post(route('orders.void', $order), [
            'reason' => 'Cancel legacy order',
        ])->assertRedirect(route('orders.show', $order));

        $this->assertSame(1000.0, (float) $this->coffeeBeans->fresh()->getCurrentStock());
        $this->assertDatabaseHas('inventory_transactions', [
            'ingredient_id' => $this->coffeeBeans->id,
            'type' => 'sales_return',
            'quantity' => 20,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
        ]);

        $this->actingAs($this->manager)->post(route('orders.void', $order), [
            'reason' => 'Try duplicate legacy restore',
        ]);
        $this->assertSame(1000.0, (float) $this->coffeeBeans->fresh()->getCurrentStock());
        $this->assertSame(1, InventoryTransaction::where('type', 'sales_return')
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->count());
    }

    public function test_legacy_order_level_consumption_blocks_inexact_item_void(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-LEGACY-ITEM-001',
            'cashier_name' => $this->cashier->name,
            'subtotal' => 150,
            'total' => 150,
            'status' => 'completed',
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->size->product_id,
            'product_size_id' => $this->size->id,
            'quantity' => 1,
            'unit_price' => 150,
            'subtotal' => 150,
            'payable_total' => 150,
            'status' => 'active',
        ]);
        $this->coffeeBeans->inventory()->update(['current_stock' => 980]);
        InventoryTransaction::create([
            'ingredient_id' => $this->coffeeBeans->id,
            'type' => 'sales_consumption',
            'quantity' => 20,
            'previous_stock' => 1000,
            'new_stock' => 980,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'reason' => 'Sale: '.$order->order_number,
            'performed_by' => $this->cashier->name,
            'performed_role' => 'cashier',
        ]);
        Recipe::where('product_size_id', $this->size->id)
            ->firstOrFail()
            ->recipeIngredients()
            ->update(['quantity' => 50]);

        $this->actingAs($this->manager)->from(route('orders.show', $order))
            ->post(route('orders.items.void', [$order, $item]), [
                'reason' => 'Cancel legacy item',
            ])
            ->assertSessionHasErrors('item');

        $this->assertSame(980.0, (float) $this->coffeeBeans->fresh()->getCurrentStock());
        $this->assertSame('active', $item->fresh()->status);
        $this->assertDatabaseMissing('inventory_transactions', [
            'type' => 'sales_return',
            'reference_type' => OrderItem::class,
            'reference_id' => $item->id,
        ]);
    }

    public function test_legacy_order_level_consumption_restores_exact_recorded_quantity_on_refund(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-LEGACY-REFUND-001',
            'cashier_name' => $this->cashier->name,
            'subtotal' => 150,
            'total' => 150,
            'status' => 'completed',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->size->product_id,
            'product_size_id' => $this->size->id,
            'quantity' => 1,
            'unit_price' => 150,
            'subtotal' => 150,
            'payable_total' => 150,
            'status' => 'active',
        ]);
        Payment::create([
            'order_id' => $order->id,
            'method' => 'cash',
            'amount_received' => 150,
            'amount_paid' => 150,
            'change_amount' => 0,
            'status' => 'paid',
        ]);
        $this->coffeeBeans->inventory()->update(['current_stock' => 980]);
        InventoryTransaction::create([
            'ingredient_id' => $this->coffeeBeans->id,
            'type' => 'sales_consumption',
            'quantity' => 20,
            'previous_stock' => 1000,
            'new_stock' => 980,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'reason' => 'Sale: '.$order->order_number,
            'performed_by' => $this->cashier->name,
            'performed_role' => 'cashier',
        ]);
        Recipe::where('product_size_id', $this->size->id)
            ->firstOrFail()
            ->recipeIngredients()
            ->update(['quantity' => 50]);

        $this->actingAs($this->cashier)->post(route('refunds.refund', $order), [
            'authorizer_email' => $this->manager->email,
            'authorizer_password' => 'password',
            'reason' => 'Customer requested refund for this drink.',
            'restore_stock' => 1,
        ])->assertRedirect(route('orders.show', $order));

        $this->assertSame(1000.0, (float) $this->coffeeBeans->fresh()->getCurrentStock());
        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'stock_restored' => 1,
        ]);
        $this->assertDatabaseHas('inventory_transactions', [
            'ingredient_id' => $this->coffeeBeans->id,
            'type' => 'sales_return',
            'quantity' => 20,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
        ]);
    }

    public function test_recipe_update_rejects_duplicate_ingredients_without_changing_existing_recipe(): void
    {
        $recipe = Recipe::where('product_size_id', $this->size->id)->firstOrFail();
        $originalIngredient = $recipe->recipeIngredients()->firstOrFail();
        $otherIngredient = Ingredient::create([
            'name' => 'Milk',
            'unit' => 'ml',
            'minimum_stock' => 100,
            'status' => 'active',
        ]);

        $this->actingAs($this->manager)->put(route('recipes.update', $this->size), [
            'name' => 'Updated Latte',
            'ingredients' => [
                ['ingredient_id' => $otherIngredient->id, 'quantity' => 150],
                ['ingredient_id' => $otherIngredient->id, 'quantity' => 50],
            ],
        ])->assertSessionHasErrors('ingredients.1.ingredient_id');

        $this->assertSame(1, $recipe->recipeIngredients()->count());
        $this->assertDatabaseHas('recipe_ingredients', [
            'id' => $originalIngredient->id,
            'ingredient_id' => $this->coffeeBeans->id,
            'quantity' => 20,
        ]);
        $this->assertDatabaseMissing('recipe_ingredients', [
            'recipe_id' => $recipe->id,
            'ingredient_id' => $otherIngredient->id,
        ]);
    }

    public function test_recipe_editor_preserves_archived_ingredients_but_prevents_new_archived_mappings(): void
    {
        $recipe = Recipe::where('product_size_id', $this->size->id)->firstOrFail();
        $this->coffeeBeans->update(['status' => 'inactive']);
        $otherArchivedIngredient = Ingredient::create([
            'name' => 'Archived Milk',
            'unit' => 'ml',
            'minimum_stock' => 100,
            'status' => 'inactive',
        ]);

        $this->actingAs($this->manager)
            ->get(route('recipes.edit', $this->size))
            ->assertOk()
            ->assertSee('Espresso Beans (g) — Archived')
            ->assertSee('value="'.$this->coffeeBeans->id.'" selected', false);

        $this->actingAs($this->manager)->put(route('recipes.update', $this->size), [
            'name' => 'Archived Ingredient Recipe',
            'ingredients' => [
                ['ingredient_id' => $this->coffeeBeans->id, 'quantity' => 20],
            ],
        ])->assertRedirect(route('recipes.index'));

        $this->assertDatabaseHas('recipe_ingredients', [
            'recipe_id' => $recipe->id,
            'ingredient_id' => $this->coffeeBeans->id,
            'quantity' => 20,
        ]);

        $this->actingAs($this->manager)->put(route('recipes.update', $this->size), [
            'name' => 'Invalid Archived Ingredient Recipe',
            'ingredients' => [
                ['ingredient_id' => $otherArchivedIngredient->id, 'quantity' => 100],
            ],
        ])->assertSessionHasErrors('ingredients.0.ingredient_id');

        $this->assertDatabaseHas('recipe_ingredients', [
            'recipe_id' => $recipe->id,
            'ingredient_id' => $this->coffeeBeans->id,
            'quantity' => 20,
        ]);
        $this->assertDatabaseMissing('recipe_ingredients', [
            'recipe_id' => $recipe->id,
            'ingredient_id' => $otherArchivedIngredient->id,
        ]);
    }

    public function test_recipe_update_rejects_quantity_precision_above_inventory_storage(): void
    {
        $recipe = Recipe::where('product_size_id', $this->size->id)->firstOrFail();

        $this->actingAs($this->manager)->put(route('recipes.update', $this->size), [
            'name' => 'Precision Test Recipe',
            'ingredients' => [
                ['ingredient_id' => $this->coffeeBeans->id, 'quantity' => '20.0001'],
            ],
        ])->assertSessionHasErrors('ingredients.0.quantity');

        $this->assertSame(20.0, (float) $recipe->recipeIngredients()->firstOrFail()->quantity);
    }

    public function test_product_prices_reject_precision_above_currency_storage(): void
    {
        $product = $this->size->product;

        $this->actingAs($this->manager)->from(route('products.edit', $product))->put(route('products.update', $product), [
            'category_id' => $product->category_id,
            'name' => $product->name,
            'status' => $product->status,
            'sizes' => [
                [
                    'id' => $this->size->id,
                    'size_name' => $this->size->size_name,
                    'price' => '150.001',
                    'grab_price' => '180.001',
                    'status' => $this->size->status,
                ],
            ],
        ])->assertSessionHasErrors(['sizes.0.price', 'sizes.0.grab_price']);

        $this->assertSame(150.0, (float) $this->size->fresh()->price);

        $this->actingAs($this->manager)->from(route('products.create'))->post(route('products.store'), [
            'category_id' => $product->category_id,
            'name' => 'Invalid Precision Product',
            'status' => 'active',
            'sizes' => [
                [
                    'size_name' => 'Regular',
                    'price' => '100.001',
                    'grab_price' => '120.00',
                    'status' => 'active',
                ],
            ],
        ])->assertSessionHasErrors('sizes.0.price');

        $this->assertDatabaseMissing('products', ['name' => 'Invalid Precision Product']);
    }

    public function test_product_category_must_be_active_but_existing_inactive_category_is_preserved_in_edit_form(): void
    {
        $product = $this->size->product;
        $inactiveCategory = Category::create(['name' => 'Archived Menu Group', 'status' => 'inactive']);

        $product->update(['category_id' => $inactiveCategory->id]);

        $edit = $this->actingAs($this->manager)->get(route('products.edit', $product));
        $edit->assertOk()
            ->assertSee('Archived Menu Group (Archived - choose an unarchived category)')
            ->assertSee('option value="'.$inactiveCategory->id.'" selected', false);

        $this->put(route('products.update', $product), [
            'category_id' => $inactiveCategory->id,
            'name' => $product->name,
            'status' => $product->status,
        ])->assertRedirect(route('products.index'));
        $this->assertSame($inactiveCategory->id, $product->fresh()->category_id);

        $this->from(route('products.create'))->post(route('products.store'), [
            'category_id' => $inactiveCategory->id,
            'name' => 'Product In Inactive Category',
            'status' => 'active',
            'sizes' => [[
                'size_name' => 'Regular',
                'price' => '100.00',
                'status' => 'active',
            ]],
        ])->assertSessionHasErrors('category_id');

        $this->assertDatabaseMissing('products', ['name' => 'Product In Inactive Category']);
    }

    public function test_grabfood_prices_are_independently_editable_and_zero_is_preserved(): void
    {
        $product = $this->size->product;

        $this->actingAs($this->manager)->post(route('products.store'), [
            'category_id' => $product->category_id,
            'name' => 'Separate Grab Price Product',
            'status' => 'active',
            'sizes' => [[
                'size_name' => 'Regular',
                'price' => '150.00',
                'grab_price' => '180.00',
                'status' => 'active',
            ]],
        ])->assertRedirect(route('products.index'));
        $createdSize = ProductSize::query()
            ->whereHas('product', fn ($query) => $query->where('name', 'Separate Grab Price Product'))
            ->firstOrFail();
        $this->assertSame(150.0, (float) $createdSize->price);
        $this->assertSame(180.0, (float) $createdSize->grab_price);

        $this->actingAs($this->manager)->put(route('products.update', $product), [
            'category_id' => $product->category_id,
            'name' => $product->name,
            'status' => $product->status,
            'sizes' => [[
                'id' => $this->size->id,
                'size_name' => $this->size->size_name,
                'price' => '150.00',
                'grab_price' => '180.00',
                'status' => $this->size->status,
            ]],
        ])->assertRedirect(route('products.index'));

        $this->assertSame(150.0, (float) $this->size->fresh()->price);
        $this->assertSame(180.0, (float) $this->size->fresh()->grab_price);
        $this->assertSame(180.0, $this->size->fresh()->getGrabPrice());

        $this->actingAs($this->manager)->put(route('products.update', $product), [
            'category_id' => $product->category_id,
            'name' => $product->name,
            'status' => $product->status,
            'sizes' => [[
                'id' => $this->size->id,
                'size_name' => $this->size->size_name,
                'price' => '150.00',
                'grab_price' => '0.00',
                'status' => $this->size->status,
            ]],
        ])->assertRedirect(route('products.index'));

        $this->assertSame(150.0, (float) $this->size->fresh()->price);
        $this->assertSame(0.0, (float) $this->size->fresh()->grab_price);
        $this->assertSame(0.0, $this->size->fresh()->getGrabPrice());
        $this->actingAs($this->manager)->get(route('products.index'))
            ->assertOk()
            ->assertSee('Regular ₱150.00')
            ->assertSee('GrabFood ₱0.00');
    }

    public function test_product_edit_and_size_delete_reject_sizes_owned_by_another_product(): void
    {
        $product = $this->size->product;
        $otherProduct = Product::create([
            'category_id' => $product->category_id,
            'name' => 'Other Product',
            'status' => 'active',
        ]);
        $otherSize = ProductSize::create([
            'product_id' => $otherProduct->id,
            'size_name' => 'Large',
            'price' => 200,
            'grab_price' => 230,
            'status' => 'active',
        ]);

        $this->actingAs($this->manager)->from(route('products.edit', $product))
            ->put(route('products.update', $product), [
                'category_id' => $product->category_id,
                'name' => 'Tampered Product Name',
                'status' => $product->status,
                'sizes' => [[
                    'id' => $otherSize->id,
                    'size_name' => $otherSize->size_name,
                    'price' => '1.00',
                    'grab_price' => '2.00',
                    'status' => $otherSize->status,
                ]],
            ])
            ->assertSessionHasErrors('sizes.0.id');

        $this->assertSame($product->name, $product->fresh()->name);
        $this->assertSame(200.0, (float) $otherSize->fresh()->price);
        $this->assertSame(230.0, (float) $otherSize->fresh()->grab_price);

        $this->actingAs($this->manager)
            ->delete(route('products.sizes.destroy', [$product, $otherSize]))
            ->assertNotFound();

        $this->assertDatabaseHas('product_sizes', ['id' => $otherSize->id, 'product_id' => $otherProduct->id]);
    }

    public function test_product_size_delete_form_submits_delete_method_and_removes_unused_size(): void
    {
        $product = $this->size->product;
        $editResponse = $this->actingAs($this->manager)->get(route('products.edit', $product));

        $editResponse->assertOk()
            ->assertSee('method="POST"', false)
            ->assertSee('action="'.route('products.sizes.destroy', [$product, $this->size]).'"', false)
            ->assertSee('name="_method" value="DELETE"', false);

        $this->delete(route('products.sizes.destroy', [$product, $this->size]))
            ->assertRedirect();

        $this->assertDatabaseMissing('product_sizes', ['id' => $this->size->id]);
    }

    public function test_existing_product_size_labels_are_fixed_and_new_sizes_are_added_with_add_size(): void
    {
        $product = $this->size->product;
        $editResponse = $this->actingAs($this->manager)->get(route('products.edit', $product));
        $editResponse->assertOk()
            ->assertSee('Existing size labels are fixed; use Add Size for another size or temperature.', false)
            ->assertSee('name="sizes[0][size_name]"', false)
            ->assertSee('readonly', false)
            ->assertSee('function addSizeRow()', false)
            ->assertSee('name="sizes[${newIdx}][size_name]"', false);

        $this->put(route('products.update', $product), [
            'category_id' => $product->category_id,
            'name' => $product->name,
            'status' => $product->status,
            'sizes' => [[
                'id' => $this->size->id,
                'size_name' => 'Hot 8oz (renamed)',
                'price' => '150.00',
                'grab_price' => '180.00',
                'status' => $this->size->status,
            ]],
        ])->assertSessionHasErrors('sizes.0.size_name');
        $this->assertSame('Regular', $this->size->fresh()->size_name);

        $this->put(route('products.update', $product), [
            'category_id' => $product->category_id,
            'name' => $product->name,
            'status' => $product->status,
            'sizes' => [
                [
                    'id' => $this->size->id,
                    'size_name' => $this->size->size_name,
                    'price' => '150.00',
                    'grab_price' => '180.00',
                    'status' => $this->size->status,
                ],
                [
                    'size_name' => 'Hot 8oz',
                    'price' => '160.00',
                    'grab_price' => '190.00',
                    'status' => 'active',
                ],
            ],
        ])->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('product_sizes', [
            'product_id' => $product->id,
            'size_name' => 'Regular',
            'price' => 150,
            'grab_price' => 180,
        ]);
        $this->assertDatabaseHas('product_sizes', [
            'product_id' => $product->id,
            'size_name' => 'Hot 8oz',
            'price' => 160,
            'grab_price' => 190,
        ]);
    }

    public function test_ingredient_unit_cannot_change_after_recipe_or_inventory_usage(): void
    {
        $this->actingAs($this->manager)->put(route('ingredients.update', $this->coffeeBeans), [
            'name' => $this->coffeeBeans->name,
            'unit' => 'kg',
            'minimum_stock' => 100,
            'reorder_level' => 0,
            'cost' => 0.5,
            'status' => 'active',
        ])->assertSessionHasErrors('unit');

        $this->assertSame('g', $this->coffeeBeans->fresh()->unit);
        $this->assertDatabaseHas('recipe_ingredients', [
            'ingredient_id' => $this->coffeeBeans->id,
            'quantity' => 20,
        ]);
    }

    public function test_voiding_a_paid_assigned_item_reduces_order_total_and_refunds_cash_to_shift(): void
    {
        $this->actingAs($this->cashier)->post(route('pos.store'), [
            'cashier_name' => $this->cashier->name,
            'payment_method' => 'cash',
            'amount_paid' => 150,
            'amount_received' => 150,
            'person_name' => 'Alex',
            'items' => [
                ['product_size_id' => $this->size->id, 'quantity' => 1, 'assigned_to' => 'Alex'],
                ['product_size_id' => $this->size->id, 'quantity' => 1, 'assigned_to' => 'Blair'],
            ],
        ])->assertSessionHasNoErrors();

        $order = Order::latest()->firstOrFail();
        $this->actingAs($this->cashier)->post(route('orders.payments.store', $order), [
            'person_name' => 'Blair',
            'amount_paid' => 150,
            'payment_method' => 'cash',
            'amount_received' => 150,
        ])->assertSessionHasNoErrors();
        $item = $order->orderItems()->where('assigned_to', 'Alex')->firstOrFail();

        $this->actingAs($this->manager)->post(route('orders.items.void', [$order, $item]), [
            'reason' => 'Alex cancelled their drink',
        ])->assertRedirect(route('orders.show', $order));

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame(150.0, (float) $order->fresh()->total);
        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'payment_id' => $order->payments()->first()->id,
            'shift_id' => CashierShift::activeForUser($this->manager->id)->id,
            'amount' => 150,
            'method' => 'cash',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('void_logs', [
            'order_id' => $order->id,
            'shift_id' => CashierShift::activeForUser($this->manager->id)->id,
            'void_type' => 'item',
        ]);
        $this->actingAs($this->cashier)->postJson(route('shifts.preview-end'), ['actual_cash' => 800])
            ->assertJsonPath('summary.cash_sales', 300)
            ->assertJsonPath('summary.cash_refunds', 0)
            ->assertJsonPath('expected_cash', 800);
        $this->actingAs($this->manager)->postJson(route('shifts.preview-end'), ['actual_cash' => 350])
            ->assertJsonPath('summary.cash_voids', 150)
            ->assertJsonPath('expected_cash', 350);

        $this->actingAs($this->manager)->post(route('refunds.refund', $order), [
            'authorizer_email' => $this->manager->email,
            'authorizer_password' => 'password',
            'reason' => 'Customer requested refund for the remaining order.',
            'restore_stock' => 0,
        ])->assertRedirect(route('refunds.index'));

        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertSame(300.0, (float) $order->refunds()->sum('amount'));
        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'shift_id' => CashierShift::activeForUser($this->manager->id)->id,
            'amount' => 150,
            'reason' => 'Customer requested refund for the remaining order.',
        ]);
        $this->actingAs($this->manager)->postJson(route('shifts.preview-end'), ['actual_cash' => 200])
            ->assertJsonPath('summary.cash_voids', 150)
            ->assertJsonPath('summary.cash_refunds', 150)
            ->assertJsonPath('expected_cash', 200);
    }

    public function test_voiding_an_item_from_a_held_ticket_is_rejected(): void
    {
        $heldOrder = $this->actingAs($this->manager)->postJson(route('pos.hold'), [
            'items' => [
                ['product_size_id' => $this->size->id, 'quantity' => 1],
                ['product_size_id' => $this->size->id, 'quantity' => 1],
            ],
        ])->assertOk()->json('order');
        $order = Order::findOrFail($heldOrder['id']);
        $item = $order->orderItems()->firstOrFail();

        $this->actingAs($this->manager)->get(route('orders.show', $order))
            ->assertOk()
            ->assertDontSee('Void Item');
        $this->postJson(route('orders.items.void', [$order, $item]), [
            'reason' => 'Remove one saved drink',
        ])->assertJsonValidationErrors('order');

        $this->assertSame('held', $order->fresh()->status);
        $this->assertSame(2, $order->orderItems()->where('status', 'active')->count());
        $this->assertDatabaseCount('void_logs', 0);
    }

    public function test_item_void_preserves_legacy_order_total_and_pending_status(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-LEGACY-ITEM-VOID',
            'cashier_name' => $this->cashier->name,
            'shift_id' => CashierShift::activeForUser($this->cashier->id)->id,
            'subtotal' => 300,
            'total' => 300,
            'status' => 'pending',
        ]);
        foreach (['First', 'Second'] as $comment) {
            $order->orderItems()->create([
                'product_id' => $this->size->product_id,
                'product_size_id' => $this->size->id,
                'quantity' => 1,
                'unit_price' => 150,
                'subtotal' => 150,
                'comment' => $comment,
                'status' => 'active',
            ]);
        }
        $item = $order->orderItems()->firstOrFail();

        $this->actingAs($this->manager)->post(route('orders.items.void', [$order, $item]), [
            'reason' => 'Customer removed one legacy item',
        ])->assertRedirect(route('orders.show', $order));

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(150.0, (float) $order->fresh()->total);
        $this->assertSame(150.0, (float) $order->voidLogs()->firstOrFail()->amount);
        $this->assertSame(1, $order->orderItems()->where('status', 'active')->count());
    }

    public function test_voiding_order_after_item_void_does_not_refund_cash_twice(): void
    {
        $this->actingAs($this->cashier)->post(route('pos.store'), [
            'cashier_name' => $this->cashier->name,
            'payment_method' => 'cash',
            'amount_paid' => 150,
            'amount_received' => 150,
            'person_name' => 'Alex',
            'items' => [
                ['product_size_id' => $this->size->id, 'quantity' => 1, 'assigned_to' => 'Alex'],
                ['product_size_id' => $this->size->id, 'quantity' => 1, 'assigned_to' => 'Blair'],
            ],
        ])->assertSessionHasNoErrors();
        $order = Order::latest()->firstOrFail();

        $this->actingAs($this->cashier)->post(route('orders.payments.store', $order), [
            'person_name' => 'Blair',
            'amount_paid' => 150,
            'amount_received' => 150,
            'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();

        $alexItem = $order->orderItems()->where('assigned_to', 'Alex')->firstOrFail();
        $this->actingAs($this->manager)->post(route('orders.items.void', [$order, $alexItem]), [
            'reason' => 'Alex cancelled their drink',
        ])->assertRedirect(route('orders.show', $order));
        $this->assertSame(150.0, (float) $order->refunds()->where('method', 'cash')->sum('amount'));

        $this->actingAs($this->manager)->post(route('orders.void', $order), [
            'reason' => 'Customer cancelled the remaining drink',
        ])->assertRedirect(route('orders.show', $order));

        $this->assertSame(300.0, (float) $order->fresh()->refunds()->where('method', 'cash')->sum('amount'));
        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'reason' => 'Order void: Customer cancelled the remaining drink',
            'amount' => 150,
            'method' => 'cash',
            'status' => 'completed',
        ]);
        $this->actingAs($this->manager)->postJson(route('shifts.preview-end'), ['actual_cash' => 200])
            ->assertJsonPath('summary.cash_voids', 300)
            ->assertJsonPath('expected_cash', 200);
    }

    public function test_voiding_online_order_refunds_only_online_payment_balances_not_already_processing(): void
    {
        $this->actingAs($this->cashier)->post(route('pos.store'), [
            'cashier_name' => $this->cashier->name,
            'payment_method' => 'online',
            'amount_paid' => 150,
            'person_name' => 'Alex',
            'amount_received' => 0,
            'reference_number' => 'ONLINE-ORDER-VOID',
            'items' => [
                ['product_size_id' => $this->size->id, 'quantity' => 1, 'assigned_to' => 'Alex'],
                ['product_size_id' => $this->size->id, 'quantity' => 1, 'assigned_to' => 'Blair'],
            ],
        ])->assertSessionHasNoErrors();
        $order = Order::latest()->firstOrFail();
        $this->actingAs($this->cashier)->post(route('orders.payments.store', $order), [
            'person_name' => 'Blair',
            'amount_paid' => 150,
            'payment_method' => 'online',
            'reference_number' => 'ONLINE-BLAIR-VOID',
        ])->assertSessionHasNoErrors();

        $blairItem = $order->orderItems()->where('assigned_to', 'Blair')->firstOrFail();
        $this->actingAs($this->manager)->post(route('orders.items.void', [$order, $blairItem]), [
            'reason' => 'Blair cancelled their drink',
        ])->assertRedirect(route('orders.show', $order));
        $existingRefund = $order->refunds()->where('method', 'online')->firstOrFail();
        $this->assertSame(150.0, (float) $existingRefund->amount);

        $this->actingAs($this->manager)->post(route('orders.void', $order), [
            'reason' => 'Customer cancelled online order',
        ])->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'payment_id' => $existingRefund->payment_id,
            'shift_id' => CashierShift::activeForUser($this->manager->id)->id,
            'amount' => 150,
            'method' => 'online',
            'status' => 'processing',
            'reason' => 'Item void: Blair cancelled their drink',
        ]);
        $this->assertSame(300.0, (float) $order->fresh()->refunds()->where('method', 'online')->sum('amount'));
        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'reason' => 'Order void: Customer cancelled online order',
            'amount' => 150,
            'method' => 'online',
            'status' => 'processing',
        ]);
        $this->assertDatabaseCount('payments', 2);
        $this->assertSame(2, $order->payments()->where('status', 'voided')->count());
        $this->assertSame('voided', $order->fresh()->status);
    }

    public function test_voiding_a_paid_assigned_item_marks_online_refund_as_processing(): void
    {
        $this->actingAs($this->cashier)->post(route('pos.store'), [
            'cashier_name' => $this->cashier->name,
            'payment_method' => 'cash',
            'amount_paid' => 150,
            'amount_received' => 150,
            'person_name' => 'Alex',
            'items' => [
                ['product_size_id' => $this->size->id, 'quantity' => 1, 'assigned_to' => 'Alex'],
                ['product_size_id' => $this->size->id, 'quantity' => 1, 'assigned_to' => 'Blair'],
            ],
        ])->assertSessionHasNoErrors();

        $order = Order::latest()->firstOrFail();
        $this->actingAs($this->cashier)->post(route('orders.payments.store', $order), [
            'person_name' => 'Blair',
            'amount_paid' => 150,
            'payment_method' => 'online',
            'reference_number' => 'ONLINE-VOID-150',
        ])->assertSessionHasNoErrors();
        $item = $order->orderItems()->where('assigned_to', 'Blair')->firstOrFail();

        $this->actingAs($this->manager)->post(route('orders.items.void', [$order, $item]), [
            'reason' => 'Blair cancelled their drink',
        ])->assertRedirect(route('orders.show', $order));

        $onlinePayment = $order->payments()->where('customer_name', 'Blair')->firstOrFail();
        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'payment_id' => $onlinePayment->id,
            'amount' => 150,
            'method' => 'online',
            'status' => 'processing',
        ]);
        $this->assertSame(150.0, (float) $order->fresh()->total);
        $this->actingAs($this->cashier)->postJson(route('shifts.preview-end'), ['actual_cash' => 650])
            ->assertJsonPath('summary.cash_sales', 150)
            ->assertJsonPath('summary.cash_refunds', 0)
            ->assertJsonPath('expected_cash', 650);
    }

    public function test_user_controller_rejects_removed_role()
    {
        $response = $this->actingAs($this->owner)->post(route('users.store'), [
            'name' => 'Invalid Role User',
            'email' => 'invalid-role@heimcoffee.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'supervisor',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'invalid-role@heimcoffee.com']);
    }
}
