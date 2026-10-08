<?php

namespace Tests\Feature;

use App\Models\AddonIngredient;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemAddon;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductSize;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Refund;
use App\Models\User;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosOrderTest extends TestCase
{
    use RefreshDatabase;


    public function test_cashier_can_create_a_pos_order_and_view_it(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);
        $this->startShiftFor($user);

        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Latte',
            'status' => 'active',
        ]);
        $size = ProductSize::create([
            'product_id' => $product->id,
            'size_name' => 'Regular',
            'price' => 120.00,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('pos.store'), [
            'cashier_name' => 'Jane Cashier',
            'payment_method' => 'cash',
            'amount_received' => 150,
            'discount' => 0,
            'items' => [[
                'product_size_id' => $size->id,
                'quantity' => 1,
            ]],
        ]);

        $order = Order::firstOrFail();
        $response->assertRedirect(route('pos.success', ['order' => $order]));
        $this->assertSame($user->name, $order->cashier_name);

        $this->assertEquals(1, $order->orderItems()->count());
        $this->assertEquals('Regular', $order->orderItems()->first()->size->size_name);

        $detail = $this->actingAs($user)->get(route('pos.success', ['order' => $order]));
        $detail->assertOk()
            ->assertSee("sessionStorage.removeItem('pos-checkout-request-id-{$user->id}')", false)
            ->assertSee("sessionStorage.removeItem('pos-cart-recovery-{$user->id}')", false);
    }

    public function test_pos_warns_before_shift_out_when_the_cart_has_unsaved_items(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->startShiftFor($cashier);

        $this->actingAs($cashier)->get(route('pos.index'))
            ->assertOk()
            ->assertSee('max-h-[calc(100dvh-2rem)]', false)
            ->assertSee('overflow-y-auto overscroll-contain', false)
            ->assertSee('if (cart.length > 0) {', false)
            ->assertSee('Save or complete the current cart, then clear it before ending the shift.', false)
            ->assertSee("document.getElementById('hold-btn')?.focus()", false);
    }

    public function test_pos_product_endpoints_only_return_active_products_categories_and_sizes(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $activeCategory = Category::create(['name' => 'Active Menu Category', 'status' => 'active']);
        $inactiveCategory = Category::create(['name' => 'Inactive Menu Category', 'status' => 'inactive']);
        $activeProduct = Product::create([
            'category_id' => $activeCategory->id,
            'name' => 'Active Menu Product',
            'status' => 'active',
        ]);
        $inactiveProduct = Product::create([
            'category_id' => $activeCategory->id,
            'name' => 'Inactive Menu Product',
            'status' => 'inactive',
        ]);
        $productInInactiveCategory = Product::create([
            'category_id' => $inactiveCategory->id,
            'name' => 'Product In Inactive Category',
            'status' => 'active',
        ]);
        foreach ([$activeProduct, $inactiveProduct, $productInInactiveCategory] as $product) {
            ProductSize::create([
                'product_id' => $product->id,
                'size_name' => 'Regular',
                'price' => 100,
                'status' => 'active',
            ]);
        }

        $this->actingAs($cashier)->getJson(route('pos.products'))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Active Menu Product');
        $this->getJson(route('pos.products', ['category_id' => $inactiveCategory->id]))
            ->assertOk()
            ->assertExactJson([]);
        $this->getJson(route('pos.sizes', $activeProduct))
            ->assertOk()
            ->assertJsonCount(1);
        $this->getJson(route('pos.sizes', $inactiveProduct))
            ->assertOk()
            ->assertExactJson([]);
        $this->getJson(route('pos.sizes', $productInInactiveCategory))
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_repeated_pos_checkout_request_returns_the_original_order_without_charging_twice(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Idempotent Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);
        $payload = [
            'checkout_request_id' => 'e2d91bf9-e973-4f55-8ca2-544320809abc',
            'payment_method' => 'cash',
            'amount_received' => 100,
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ];

        $firstResponse = $this->actingAs($cashier)->post(route('pos.store'), $payload);
        $firstOrder = Order::firstOrFail();
        $firstResponse->assertRedirect(route('pos.success', ['order' => $firstOrder]));

        $retryResponse = $this->actingAs($cashier)->post(route('pos.store'), $payload);
        $retryResponse->assertRedirect(route('pos.success', ['order' => $firstOrder]));

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('orders', [
            'id' => $firstOrder->id,
            'checkout_request_id' => $payload['checkout_request_id'],
        ]);
    }

    public function test_checkout_rejects_a_saved_ticket_that_another_cashier_already_used(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Saved Ticket Conflict Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);
        $heldOrder = $this->actingAs($cashier)->postJson(route('pos.hold'), [
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ])->assertOk()->json('order');

        Order::query()->findOrFail($heldOrder['id'])->update(['status' => 'completed']);

        $this->from(route('pos.index'))->post(route('pos.store'), [
            'held_order_id' => $heldOrder['id'],
            'payment_method' => 'cash',
            'amount_received' => 100,
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ])->assertSessionHasErrors('held_order_id');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_checkout_request_id_cannot_be_reused_by_a_different_cashier(): void
    {
        $firstCashier = User::factory()->create(['role' => 'cashier']);
        $secondCashier = User::factory()->create(['role' => 'cashier']);
        $this->startShiftFor($firstCashier);
        $this->startShiftFor($secondCashier);
        $category = Category::create(['name' => 'Scoped Checkout Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);
        $payload = [
            'checkout_request_id' => 'a97ad75c-cb19-4b08-9819-09211b56101a',
            'payment_method' => 'cash',
            'amount_received' => 100,
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ];

        $this->actingAs($firstCashier)->post(route('pos.store'), $payload)->assertRedirect();
        $this->actingAs($secondCashier)->from(route('pos.index'))
            ->post(route('pos.store'), $payload)
            ->assertSessionHasErrors('checkout_request_id');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_pos_checkout_rejects_cash_payment_when_no_cash_was_received(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Cash Validation Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);

        $response = $this->actingAs($cashier)->from(route('pos.index'))->post(route('pos.store'), [
            'payment_method' => 'cash',
            'amount_received' => 0,
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ]);
        $response->assertSessionHasErrors('amount_received');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->get(route('pos.index'))
            ->assertOk()
            ->assertSee('less than the payment amount', false)
            ->assertSee('function persistPosCartRecovery()', false)
            ->assertSee('function restorePosCartRecovery()', false)
            ->assertSee('discountIdInput.value = state.discount_id_number ||', false);
    }

    public function test_grabfood_settlement_is_rejected_for_non_grab_pos_orders(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Settlement Validation Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);

        $this->actingAs($cashier)->from(route('pos.index'))->post(route('pos.store'), [
            'order_type' => 'dine_in',
            'payment_method' => 'cash',
            'amount_received' => 100,
            'payments' => [[
                'method' => 'grabfood',
                'amount_paid' => 100,
            ]],
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ])->assertSessionHasErrors('payment_method');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_partial_pos_payment_is_labeled_as_payment_recorded_with_balance_due(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Partial Receipt Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);

        $this->actingAs($cashier)->post(route('pos.store'), [
            'payment_method' => 'cash',
            'amount_paid' => 40,
            'amount_received' => 40,
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ])->assertRedirect()->assertSessionHas('success', 'Payment recorded. Balance due: ₱60.00.');
        $order = Order::firstOrFail();

        $this->get(route('pos.success', $order))
            ->assertOk()
            ->assertSee('Payment Recorded')
            ->assertSee('Balance due: ₱60.00')
            ->assertDontSee('Order Complete!');
        $this->get(route('orders.receipt', $order))
            ->assertOk()
            ->assertSee('PARTIALLY PAID • BALANCE DUE')
            ->assertSee('₱60.00');
    }

    public function test_terminal_order_receipts_do_not_show_a_balance_due(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Terminal Receipt Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);

        $this->actingAs($cashier)->post(route('pos.store'), [
            'payment_method' => 'cash',
            'amount_received' => 100,
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ])->assertRedirect();

        $order = Order::firstOrFail();
        foreach (['voided', 'refunded', 'cancelled'] as $status) {
            $order->update(['status' => $status]);
            $order->payments()->update(['status' => $status === 'refunded' ? 'refunded' : 'voided']);

            $this->actingAs($cashier)->get(route('pos.success', $order))
                ->assertOk()
                ->assertSee(ucfirst($status))
                ->assertSee('No balance due')
                ->assertDontSee('Balance due: ₱100.00');

            $this->actingAs($cashier)->get(route('orders.receipt', $order))
                ->assertOk()
                ->assertSee('Order Status')
                ->assertSee('No balance due')
                ->assertDontSee('Remaining Balance:');

            $this->actingAs($cashier)->get(route('orders.show', $order))
                ->assertOk()
                ->assertSee('Order Settlement')
                ->assertSee('No balance due')
                ->assertDontSee('Paid / Remaining');
        }
    }

    public function test_checkout_and_held_orders_reject_item_quantities_above_the_pos_limit(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 50, 'status' => 'active']);
        $items = [['product_size_id' => $size->id, 'quantity' => 1000]];

        $this->actingAs($cashier)->from(route('pos.index'))->post(route('pos.store'), [
            'cashier_name' => $cashier->name,
            'payment_method' => 'cash',
            'amount_received' => 50000,
            'items' => $items,
        ])->assertSessionHasErrors('items.0.quantity');

        $this->actingAs($cashier)->postJson(route('pos.hold'), [
            'cashier_name' => $cashier->name,
            'items' => $items,
        ])->assertJsonValidationErrors('items.0.quantity');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_saved_orders_enforce_custom_discount_authorization_and_subtotal_limit(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $manager = User::factory()->create(['role' => 'manager']);
        $this->startShiftFor($cashier);
        $this->startShiftFor($manager);
        $category = Category::create(['name' => 'Saved Discount Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);
        $items = [['product_size_id' => $size->id, 'quantity' => 1]];

        $this->actingAs($cashier)->postJson(route('pos.hold'), [
            'discount' => 10,
            'discount_type' => 'custom',
            'items' => $items,
        ])->assertJsonValidationErrors('discount');
        $this->assertDatabaseCount('orders', 0);

        $this->actingAs($manager)->postJson(route('pos.hold'), [
            'discount' => 101,
            'discount_type' => 'custom',
            'items' => $items,
        ])->assertJsonValidationErrors('discount');
        $this->assertDatabaseCount('orders', 0);

        $this->actingAs($manager)->postJson(route('pos.hold'), [
            'discount' => 100,
            'discount_type' => 'custom',
            'items' => $items,
        ])->assertJsonValidationErrors('discount');
        $this->assertDatabaseCount('orders', 0);

        $this->actingAs($manager)->from(route('pos.index'))->post(route('pos.store'), [
            'payment_method' => 'cash',
            'amount_received' => 0,
            'discount' => 100,
            'discount_type' => 'custom',
            'items' => $items,
        ])->assertSessionHasErrors('discount');
        $this->assertDatabaseCount('orders', 0);

        $this->postJson(route('pos.hold'), [
            'discount' => 10,
            'discount_type' => 'custom',
            'items' => $items,
        ])->assertOk();
        $this->assertDatabaseHas('orders', [
            'status' => 'held',
            'discount_type' => 'custom',
            'discount' => 10,
            'total' => 90,
        ]);
    }

    public function test_resuming_a_held_order_returns_unique_cart_keys_for_duplicate_products(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Held Resume Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);

        $heldResponse = $this->actingAs($cashier)->postJson(route('pos.hold'), [
            'items' => [
                ['product_size_id' => $size->id, 'quantity' => 1, 'comment' => 'Less ice', 'assigned_to' => 'Alex'],
                ['product_size_id' => $size->id, 'quantity' => 1, 'comment' => 'No sugar', 'assigned_to' => 'Blair'],
            ],
        ])->assertOk();
        $heldOrderId = $heldResponse->json('order.id');

        $resumeResponse = $this->postJson(route('pos.resume-held', ['order' => $heldOrderId]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.held_order_id', $heldOrderId);

        $cart = $resumeResponse->json('data.cart');
        $this->assertCount(2, $cart);
        $this->assertNotSame($cart[0]['key'], $cart[1]['key']);
        $this->assertSame(['Less ice', 'No sugar'], array_column($cart, 'comment'));
        $this->assertSame(['Alex', 'Blair'], array_column($cart, 'assigned_to'));
        $this->assertDatabaseHas('orders', ['id' => $heldOrderId, 'status' => 'held']);
    }

    public function test_checkout_removes_a_resumed_held_ticket_only_when_order_is_created(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Held Checkout Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);
        $heldResponse = $this->actingAs($cashier)->postJson(route('pos.hold'), [
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ])->assertOk();
        $heldOrderId = $heldResponse->json('order.id');

        $this->postJson(route('pos.resume-held', ['order' => $heldOrderId]))
            ->assertOk()
            ->assertJsonPath('data.held_order_id', $heldOrderId);
        $this->assertDatabaseHas('orders', ['id' => $heldOrderId, 'status' => 'held']);

        $this->actingAs($cashier)->from(route('pos.index'))->post(route('pos.store'), [
            'held_order_id' => $heldOrderId,
            'payment_method' => 'cash',
            'amount_received' => 0,
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ])->assertSessionHasErrors('amount_received');
        $this->assertDatabaseHas('orders', ['id' => $heldOrderId, 'status' => 'held']);

        $this->post(route('pos.store'), [
            'held_order_id' => $heldOrderId,
            'payment_method' => 'cash',
            'amount_received' => 100,
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ])->assertRedirect();

        $this->assertDatabaseMissing('orders', ['id' => $heldOrderId]);
        $this->assertDatabaseHas('orders', ['status' => 'completed', 'total' => 100]);
    }

    public function test_reholding_a_resumed_ticket_replaces_the_saved_copy(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Held Rehold Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);
        $heldResponse = $this->actingAs($cashier)->postJson(route('pos.hold'), [
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ])->assertOk();
        $heldOrderId = $heldResponse->json('order.id');

        $this->postJson(route('pos.resume-held', ['order' => $heldOrderId]))
            ->assertOk();
        $reheldResponse = $this->postJson(route('pos.hold'), [
            'held_order_id' => $heldOrderId,
            'items' => [['product_size_id' => $size->id, 'quantity' => 2]],
        ])->assertOk();

        $this->assertDatabaseMissing('orders', ['id' => $heldOrderId]);
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(2, $reheldResponse->json('order.order_items.0.quantity'));
    }

    public function test_resumed_held_order_uses_current_addon_price_in_cart_total(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Held Add-on Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);
        $addon = ProductAddon::create(['name' => 'Extra Shot', 'price' => 10, 'status' => 'active']);

        $heldResponse = $this->actingAs($cashier)->postJson(route('pos.hold'), [
            'items' => [[
                'product_size_id' => $size->id,
                'quantity' => 1,
                'addon_ids' => [$addon->id],
            ]],
        ])->assertOk();
        $heldOrderId = $heldResponse->json('order.id');
        $addon->update(['price' => 20]);

        $resumeResponse = $this->postJson(route('pos.resume-held', ['order' => $heldOrderId]))
            ->assertOk();

        $this->assertSame(20.0, (float) $resumeResponse->json('data.cart.0.addons.0.price'));
        $this->assertSame(120.0, (float) $resumeResponse->json('data.cart.0.unit_price'));
    }

    public function test_large_order_supports_assigned_items_split_payments_and_single_inventory_deduction(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier', 'name' => 'Jane Cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $firstProduct = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $firstSize = ProductSize::create(['product_id' => $firstProduct->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);
        $secondProduct = Product::create(['category_id' => $category->id, 'name' => 'Mocha', 'status' => 'active']);
        $secondSize = ProductSize::create(['product_id' => $secondProduct->id, 'size_name' => 'Regular', 'price' => 200, 'status' => 'active']);
        $ingredient = Ingredient::create(['name' => 'Coffee Beans', 'unit' => 'g', 'minimum_stock' => 5, 'status' => 'active']);
        Inventory::create(['ingredient_id' => $ingredient->id, 'current_stock' => 100]);
        $recipe = Recipe::create(['product_size_id' => $firstSize->id, 'status' => 'active']);
        RecipeIngredient::create(['recipe_id' => $recipe->id, 'ingredient_id' => $ingredient->id, 'quantity' => 10]);

        $this->actingAs($cashier)->post(route('pos.store'), [
            'cashier_name' => $cashier->name,
            'payment_method' => 'cash',
            'amount_received' => 100,
            'amount_paid' => 50,
            'person_name' => 'Alex',
            'payment_comment' => 'First cash installment',
            'items' => [
                ['product_size_id' => $firstSize->id, 'quantity' => 1, 'assigned_to' => 'Alex'],
                ['product_size_id' => $secondSize->id, 'quantity' => 1, 'assigned_to' => 'Blair'],
            ],
        ])->assertSessionHasNoErrors();

        $order = Order::firstOrFail();
        $this->assertSame('partially_paid', $order->status);
        $this->assertEquals(250, $order->remainingBalance());
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'customer_name' => 'Alex',
            'comment' => 'First cash installment',
            'amount_paid' => 50,
        ]);
        $this->assertEquals(100, $order->orderItems()->where('assigned_to', 'Alex')->value('payable_total'));
        $this->assertEquals(90, $ingredient->fresh()->inventory->current_stock);
        $this->assertDatabaseCount('inventory_transactions', 1);

        $this->actingAs($cashier)->post(route('orders.payments.store', $order), [
            'person_name' => 'Alex',
            'amount_paid' => 50,
            'payment_method' => 'cash',
            'amount_received' => 50,
        ])->assertSessionHasNoErrors();

        $this->actingAs($cashier)->post(route('orders.payments.store', $order), [
            'person_name' => 'Blair',
            'amount_paid' => 200,
            'payment_method' => 'online',
            'reference_number' => 'REF-200',
            'payment_comment' => 'E-wallet',
        ])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertEquals(0, $order->remainingBalance());
        $this->assertDatabaseCount('payments', 3);
        $this->assertDatabaseCount('inventory_transactions', 1);
        $this->assertEquals(90, $ingredient->fresh()->inventory->current_stock);
    }

    public function test_pos_checkout_can_record_multiple_person_payments_and_deduct_inventory_once(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier', 'name' => 'Jane Cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Checkout Split Coffee', 'status' => 'active']);
        $ingredient = Ingredient::create(['name' => 'Checkout Split Beans', 'unit' => 'g', 'minimum_stock' => 5, 'status' => 'active']);
        Inventory::create(['ingredient_id' => $ingredient->id, 'current_stock' => 100]);

        $items = [];
        foreach (['Latte' => 'Alex', 'Mocha' => 'Blair'] as $name => $person) {
            $product = Product::create(['category_id' => $category->id, 'name' => $name, 'status' => 'active']);
            $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);
            $recipe = Recipe::create(['product_size_id' => $size->id, 'status' => 'active']);
            RecipeIngredient::create(['recipe_id' => $recipe->id, 'ingredient_id' => $ingredient->id, 'quantity' => 10]);
            $items[] = [
                'product_size_id' => $size->id,
                'quantity' => 1,
                'assigned_to' => $person,
            ];
        }

        $this->actingAs($cashier)->post(route('pos.store'), [
            'cashier_name' => $cashier->name,
            'payment_method' => 'cash',
            'amount_received' => 100,
            'amount_paid' => 200,
            'items' => $items,
            'payments' => [
                [
                    'method' => 'cash',
                    'amount_paid' => 100,
                    'amount_received' => 100,
                    'person_name' => 'Alex',
                    'comment' => 'Counter payment',
                ],
                [
                    'method' => 'online',
                    'amount_paid' => 100,
                    'amount_received' => 100,
                    'person_name' => 'Blair',
                    'reference_number' => 'SPLIT-ONLINE-100',
                ],
            ],
        ])->assertSessionHasNoErrors();

        $order = Order::firstOrFail();
        $this->assertSame('completed', $order->status);
        $this->assertEquals(200, $order->paidAmount());
        $this->assertDatabaseCount('payments', 2);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => 'cash',
            'customer_name' => 'Alex',
            'amount_paid' => 100,
            'comment' => 'Counter payment',
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => 'online',
            'customer_name' => 'Blair',
            'reference_number' => 'SPLIT-ONLINE-100',
            'amount_paid' => 100,
        ]);
        $this->assertDatabaseCount('inventory_transactions', 2);
        $this->assertEquals(80, $ingredient->fresh()->inventory->current_stock);
    }

    public function test_large_order_supports_three_people_and_deducts_inventory_only_once_across_all_payments(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier', 'name' => 'Jane Cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Large Order Coffee', 'status' => 'active']);
        $ingredient = Ingredient::create(['name' => 'Large Order Beans', 'unit' => 'g', 'minimum_stock' => 5, 'status' => 'active']);
        Inventory::create(['ingredient_id' => $ingredient->id, 'current_stock' => 1000]);

        $items = [];
        foreach (['Latte' => 'Alex', 'Mocha' => 'Alex', 'Matcha' => 'Blair', 'Americano' => 'Casey', 'Cold Brew' => 'Casey'] as $name => $person) {
            $product = Product::create(['category_id' => $category->id, 'name' => $name, 'status' => 'active']);
            $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);
            $recipe = Recipe::create(['product_size_id' => $size->id, 'status' => 'active']);
            RecipeIngredient::create(['recipe_id' => $recipe->id, 'ingredient_id' => $ingredient->id, 'quantity' => 10]);
            $items[] = [
                'product_size_id' => $size->id,
                'quantity' => 1,
                'assigned_to' => $person,
            ];
        }

        $this->actingAs($cashier)->post(route('pos.store'), [
            'cashier_name' => $cashier->name,
            'payment_method' => 'cash',
            'amount_received' => 100,
            'amount_paid' => 100,
            'person_name' => 'Alex',
            'items' => $items,
        ])->assertSessionHasNoErrors();

        $order = Order::firstOrFail();
        $this->assertSame('partially_paid', $order->status);
        $this->assertEquals(100, $order->paidAmount());
        $this->assertEquals(400, $order->remainingBalance());
        $this->assertSame(5, $order->orderItems()->count());
        $this->assertEquals(200, $order->orderItems()->where('assigned_to', 'Alex')->sum('payable_total'));
        $this->assertEquals(100, $order->orderItems()->where('assigned_to', 'Blair')->sum('payable_total'));
        $this->assertEquals(200, $order->orderItems()->where('assigned_to', 'Casey')->sum('payable_total'));
        $this->assertDatabaseCount('inventory_transactions', 5);
        $this->assertEquals(950, $ingredient->fresh()->inventory->current_stock);

        $this->actingAs($cashier)->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Split Payment Summary')
            ->assertSee('Alex')
            ->assertSee('Blair')
            ->assertSee('Casey')
            ->assertSee('Partially Paid')
            ->assertSee('Pending');

        foreach ([
            ['person_name' => 'Alex', 'amount_paid' => 100],
            ['person_name' => 'Blair', 'amount_paid' => 100],
            ['person_name' => 'Casey', 'amount_paid' => 200],
        ] as $payment) {
            $this->actingAs($cashier)->post(route('orders.payments.store', $order), [
                ...$payment,
                'payment_method' => 'cash',
                'amount_received' => $payment['amount_paid'],
            ])->assertSessionHasNoErrors();
        }

        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertEquals(500, $order->paidAmount());
        $this->assertEquals(0, $order->remainingBalance());
        $this->assertSame(4, $order->payments()->count());
        $this->assertDatabaseCount('inventory_transactions', 5);
        $this->assertEquals(950, $ingredient->fresh()->inventory->current_stock);

        $this->actingAs($cashier)->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Split Payment Summary')
            ->assertSee('Paid');
    }

    public function test_sale_is_rejected_without_enough_stock_for_recipe_ingredients(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier', 'name' => 'Jane Cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);
        $ingredient = Ingredient::create(['name' => 'Coffee Beans', 'unit' => 'g', 'minimum_stock' => 5, 'status' => 'active']);
        Inventory::create(['ingredient_id' => $ingredient->id, 'current_stock' => 4]);
        $recipe = Recipe::create(['product_size_id' => $size->id, 'status' => 'active']);
        RecipeIngredient::create(['recipe_id' => $recipe->id, 'ingredient_id' => $ingredient->id, 'quantity' => 10]);

        $this->actingAs($cashier)->from(route('pos.index'))->post(route('pos.store'), [
            'cashier_name' => $cashier->name,
            'payment_method' => 'cash',
            'amount_received' => 100,
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ])->assertSessionHasErrors('items');

        $this->assertSame(4.0, (float) $ingredient->fresh()->getCurrentStock());
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_duplicate_cart_lines_cannot_consume_more_than_the_available_ingredient_stock(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier', 'name' => 'Jane Cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 50, 'status' => 'active']);
        $ingredient = Ingredient::create(['name' => 'Coffee Beans', 'unit' => 'g', 'minimum_stock' => 1, 'status' => 'active']);
        Inventory::create(['ingredient_id' => $ingredient->id, 'current_stock' => 5]);
        $recipe = Recipe::create(['product_size_id' => $size->id, 'status' => 'active']);
        RecipeIngredient::create(['recipe_id' => $recipe->id, 'ingredient_id' => $ingredient->id, 'quantity' => 2]);
        $addon = ProductAddon::create(['name' => 'Extra Shot', 'price' => 10, 'status' => 'active']);
        AddonIngredient::create(['product_addon_id' => $addon->id, 'ingredient_id' => $ingredient->id, 'quantity' => 1]);

        $this->actingAs($cashier)->from(route('pos.index'))->post(route('pos.store'), [
            'cashier_name' => $cashier->name,
            'payment_method' => 'cash',
            'amount_received' => 120,
            'items' => [
                ['product_size_id' => $size->id, 'quantity' => 1, 'addon_ids' => [$addon->id]],
                ['product_size_id' => $size->id, 'quantity' => 1, 'addon_ids' => [$addon->id]],
            ],
        ])->assertSessionHasErrors('items');

        $this->assertSame(5.0, (float) $ingredient->fresh()->getCurrentStock());
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_sales_consumption_scales_recipe_and_addon_ingredients_by_item_quantity(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier', 'name' => 'Jane Cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 50, 'status' => 'active']);
        $espresso = Ingredient::create(['name' => 'Espresso', 'unit' => 'g', 'minimum_stock' => 5, 'status' => 'active']);
        $syrup = Ingredient::create(['name' => 'Vanilla Syrup', 'unit' => 'ml', 'minimum_stock' => 5, 'status' => 'active']);
        Inventory::create(['ingredient_id' => $espresso->id, 'current_stock' => 500]);
        Inventory::create(['ingredient_id' => $syrup->id, 'current_stock' => 200]);

        $recipe = Recipe::create(['product_size_id' => $size->id, 'status' => 'active']);
        RecipeIngredient::create(['recipe_id' => $recipe->id, 'ingredient_id' => $espresso->id, 'quantity' => 18.5]);
        $addon = ProductAddon::create(['name' => 'Vanilla Shot', 'price' => 10, 'status' => 'active']);
        AddonIngredient::create(['product_addon_id' => $addon->id, 'ingredient_id' => $espresso->id, 'quantity' => 1.25]);
        AddonIngredient::create(['product_addon_id' => $addon->id, 'ingredient_id' => $syrup->id, 'quantity' => 0.375]);

        $response = $this->actingAs($cashier)->post(route('pos.store'), [
            'cashier_name' => $cashier->name,
            'payment_method' => 'cash',
            'amount_received' => 120,
            'items' => [[
                'product_size_id' => $size->id,
                'quantity' => 2,
                'addon_ids' => [$addon->id],
            ]],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(460.5, (float) $espresso->fresh()->getCurrentStock());
        $this->assertSame(199.25, (float) $syrup->fresh()->getCurrentStock());
        $this->assertDatabaseHas('inventory_transactions', [
            'ingredient_id' => $espresso->id,
            'type' => 'sales_consumption',
            'quantity' => 37,
            'previous_stock' => 500,
            'new_stock' => 463,
        ]);
        $this->assertDatabaseHas('inventory_transactions', [
            'ingredient_id' => $espresso->id,
            'type' => 'sales_consumption',
            'quantity' => 2.5,
            'previous_stock' => 463,
            'new_stock' => 460.5,
        ]);
        $this->assertDatabaseHas('inventory_transactions', [
            'ingredient_id' => $syrup->id,
            'type' => 'sales_consumption',
            'quantity' => 0.75,
            'previous_stock' => 200,
            'new_stock' => 199.25,
        ]);
        $this->assertDatabaseCount('inventory_transactions', 3);
    }

    public function test_selected_substitute_replaces_recipe_stock_and_void_restores_exact_consumption(): void
    {
        $manager = User::factory()->create(['role' => 'manager', 'name' => 'Manager']);
        $this->startShiftFor($manager);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 50, 'status' => 'active']);
        $freshMilk = Ingredient::create(['name' => 'Fresh Milk', 'unit' => 'ml', 'minimum_stock' => 5, 'status' => 'active']);
        $oatMilk = Ingredient::create(['name' => 'Oat Milk', 'unit' => 'ml', 'minimum_stock' => 5, 'status' => 'active']);
        Inventory::create(['ingredient_id' => $freshMilk->id, 'current_stock' => 100]);
        Inventory::create(['ingredient_id' => $oatMilk->id, 'current_stock' => 500]);
        $recipe = Recipe::create(['product_size_id' => $size->id, 'status' => 'active']);
        RecipeIngredient::create(['recipe_id' => $recipe->id, 'ingredient_id' => $freshMilk->id, 'quantity' => 180]);
        $addon = ProductAddon::create(['name' => 'Oat Milk Substitute', 'price' => 0, 'status' => 'active']);
        AddonIngredient::create([
            'product_addon_id' => $addon->id,
            'ingredient_id' => $oatMilk->id,
            'replaces_ingredient_id' => $freshMilk->id,
            'quantity' => 180,
        ]);

        $this->actingAs($manager)->get(route('pos.index'))
            ->assertOk()
            ->assertSee('&quot;substitution_required&quot;:true', false);

        $this->actingAs($manager)->post(route('pos.store'), [
            'cashier_name' => $manager->name,
            'payment_method' => 'cash',
            'amount_received' => 100,
            'items' => [[
                'product_size_id' => $size->id,
                'quantity' => 2,
                'addon_ids' => [$addon->id],
            ]],
        ])->assertSessionHasNoErrors();

        $order = Order::firstOrFail();
        $this->assertSame(100.0, (float) $freshMilk->fresh()->getCurrentStock());
        $this->assertSame(140.0, (float) $oatMilk->fresh()->getCurrentStock());
        $this->assertDatabaseHas('inventory_transactions', [
            'ingredient_id' => $oatMilk->id,
            'type' => 'sales_consumption',
            'quantity' => 360,
        ]);
        $this->assertDatabaseMissing('inventory_transactions', [
            'ingredient_id' => $freshMilk->id,
            'type' => 'sales_consumption',
        ]);

        $this->actingAs($manager)->post(route('orders.void', $order), [
            'reason' => 'Customer changed order',
        ])->assertSessionHasNoErrors();

        $this->assertSame(100.0, (float) $freshMilk->fresh()->getCurrentStock());
        $this->assertSame(500.0, (float) $oatMilk->fresh()->getCurrentStock());
        $this->assertDatabaseHas('inventory_transactions', [
            'ingredient_id' => $oatMilk->id,
            'type' => 'sales_return',
            'quantity' => 360,
        ]);
    }

    public function test_legacy_substitution_fallback_restores_substitute_without_restoring_replaced_ingredient(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 50, 'status' => 'active']);
        $freshMilk = Ingredient::create(['name' => 'Fresh Milk', 'unit' => 'ml', 'minimum_stock' => 5, 'status' => 'active']);
        $oatMilk = Ingredient::create(['name' => 'Oat Milk', 'unit' => 'ml', 'minimum_stock' => 5, 'status' => 'active']);
        Inventory::create(['ingredient_id' => $freshMilk->id, 'current_stock' => 0]);
        Inventory::create(['ingredient_id' => $oatMilk->id, 'current_stock' => 0]);
        $recipe = Recipe::create(['product_size_id' => $size->id, 'status' => 'active']);
        RecipeIngredient::create(['recipe_id' => $recipe->id, 'ingredient_id' => $freshMilk->id, 'quantity' => 180]);
        $addon = ProductAddon::create(['name' => 'Oat Milk Substitute', 'price' => 0, 'status' => 'active']);
        AddonIngredient::create([
            'product_addon_id' => $addon->id,
            'ingredient_id' => $oatMilk->id,
            'replaces_ingredient_id' => $freshMilk->id,
            'quantity' => 180,
        ]);
        $order = Order::create([
            'order_number' => 'ORD-LEGACY-SUBSTITUTE-001',
            'cashier_name' => $manager->name,
            'subtotal' => 100,
            'total' => 100,
            'status' => 'completed',
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_size_id' => $size->id,
            'quantity' => 2,
            'unit_price' => 50,
            'subtotal' => 100,
            'status' => 'active',
        ]);
        OrderItemAddon::create([
            'order_item_id' => $item->id,
            'product_addon_id' => $addon->id,
            'price' => 0,
        ]);

        InventoryService::restoreFromVoid($order->load('orderItems.addons.addon'), $manager->name, $manager->role);

        $this->assertSame(0.0, (float) $freshMilk->fresh()->getCurrentStock());
        $this->assertSame(360.0, (float) $oatMilk->fresh()->getCurrentStock());
        $this->assertDatabaseMissing('inventory_transactions', [
            'ingredient_id' => $freshMilk->id,
            'type' => 'sales_return',
        ]);
        $this->assertDatabaseHas('inventory_transactions', [
            'ingredient_id' => $oatMilk->id,
            'type' => 'sales_return',
            'quantity' => 360,
        ]);
    }

    public function test_substitute_checkout_rolls_back_when_replacement_stock_is_insufficient(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier', 'name' => 'Jane Cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 50, 'status' => 'active']);
        $freshMilk = Ingredient::create(['name' => 'Fresh Milk', 'unit' => 'ml', 'minimum_stock' => 5, 'status' => 'active']);
        $oatMilk = Ingredient::create(['name' => 'Oat Milk', 'unit' => 'ml', 'minimum_stock' => 5, 'status' => 'active']);
        Inventory::create(['ingredient_id' => $freshMilk->id, 'current_stock' => 500]);
        Inventory::create(['ingredient_id' => $oatMilk->id, 'current_stock' => 100]);
        $recipe = Recipe::create(['product_size_id' => $size->id, 'status' => 'active']);
        RecipeIngredient::create(['recipe_id' => $recipe->id, 'ingredient_id' => $freshMilk->id, 'quantity' => 180]);
        $addon = ProductAddon::create(['name' => 'Oat Milk Substitute', 'price' => 0, 'status' => 'active']);
        AddonIngredient::create([
            'product_addon_id' => $addon->id,
            'ingredient_id' => $oatMilk->id,
            'replaces_ingredient_id' => $freshMilk->id,
            'quantity' => 180,
        ]);

        $this->actingAs($cashier)->from(route('pos.index'))->post(route('pos.store'), [
            'cashier_name' => $cashier->name,
            'payment_method' => 'cash',
            'amount_received' => 50,
            'items' => [[
                'product_size_id' => $size->id,
                'quantity' => 1,
                'addon_ids' => [$addon->id],
            ]],
        ])->assertSessionHasErrors('items');

        $this->assertSame(500.0, (float) $freshMilk->fresh()->getCurrentStock());
        $this->assertSame(100.0, (float) $oatMilk->fresh()->getCurrentStock());
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_two_selected_substitutes_cannot_replace_the_same_recipe_ingredient(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier', 'name' => 'Jane Cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 50, 'status' => 'active']);
        $freshMilk = Ingredient::create(['name' => 'Fresh Milk', 'unit' => 'ml', 'minimum_stock' => 5, 'status' => 'active']);
        $oatMilk = Ingredient::create(['name' => 'Oat Milk', 'unit' => 'ml', 'minimum_stock' => 5, 'status' => 'active']);
        $almondMilk = Ingredient::create(['name' => 'Almond Milk', 'unit' => 'ml', 'minimum_stock' => 5, 'status' => 'active']);
        Inventory::create(['ingredient_id' => $freshMilk->id, 'current_stock' => 500]);
        Inventory::create(['ingredient_id' => $oatMilk->id, 'current_stock' => 500]);
        Inventory::create(['ingredient_id' => $almondMilk->id, 'current_stock' => 500]);
        $recipe = Recipe::create(['product_size_id' => $size->id, 'status' => 'active']);
        RecipeIngredient::create(['recipe_id' => $recipe->id, 'ingredient_id' => $freshMilk->id, 'quantity' => 180]);
        $oatSubstitute = ProductAddon::create(['name' => 'Oat Milk Substitute', 'price' => 0, 'status' => 'active']);
        $almondSubstitute = ProductAddon::create(['name' => 'Almond Milk Substitute', 'price' => 0, 'status' => 'active']);
        AddonIngredient::create([
            'product_addon_id' => $oatSubstitute->id,
            'ingredient_id' => $oatMilk->id,
            'replaces_ingredient_id' => $freshMilk->id,
            'quantity' => 180,
        ]);
        AddonIngredient::create([
            'product_addon_id' => $almondSubstitute->id,
            'ingredient_id' => $almondMilk->id,
            'replaces_ingredient_id' => $freshMilk->id,
            'quantity' => 180,
        ]);

        $this->actingAs($cashier)->from(route('pos.index'))->post(route('pos.store'), [
            'cashier_name' => $cashier->name,
            'payment_method' => 'cash',
            'amount_received' => 50,
            'items' => [[
                'product_size_id' => $size->id,
                'quantity' => 1,
                'addon_ids' => [$oatSubstitute->id, $almondSubstitute->id],
            ]],
        ])->assertSessionHasErrors('items');

        $this->assertSame(500.0, (float) $freshMilk->fresh()->getCurrentStock());
        $this->assertSame(500.0, (float) $oatMilk->fresh()->getCurrentStock());
        $this->assertSame(500.0, (float) $almondMilk->fresh()->getCurrentStock());
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_checkout_rejects_a_substitute_for_an_ingredient_not_in_the_recipe(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier', 'name' => 'Jane Cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 50, 'status' => 'active']);
        $espresso = Ingredient::create(['name' => 'Espresso', 'unit' => 'ml', 'minimum_stock' => 5, 'status' => 'active']);
        $freshMilk = Ingredient::create(['name' => 'Fresh Milk', 'unit' => 'ml', 'minimum_stock' => 5, 'status' => 'active']);
        $oatMilk = Ingredient::create(['name' => 'Oat Milk', 'unit' => 'ml', 'minimum_stock' => 5, 'status' => 'active']);
        Inventory::create(['ingredient_id' => $espresso->id, 'current_stock' => 100]);
        Inventory::create(['ingredient_id' => $oatMilk->id, 'current_stock' => 100]);
        $recipe = Recipe::create(['product_size_id' => $size->id, 'status' => 'active']);
        RecipeIngredient::create(['recipe_id' => $recipe->id, 'ingredient_id' => $espresso->id, 'quantity' => 18]);
        $addon = ProductAddon::create(['name' => 'Oat Milk Substitute', 'price' => 0, 'status' => 'active']);
        AddonIngredient::create([
            'product_addon_id' => $addon->id,
            'ingredient_id' => $oatMilk->id,
            'replaces_ingredient_id' => $freshMilk->id,
            'quantity' => 180,
        ]);

        $this->actingAs($cashier)->from(route('pos.index'))->post(route('pos.store'), [
            'cashier_name' => $cashier->name,
            'payment_method' => 'cash',
            'amount_received' => 50,
            'items' => [[
                'product_size_id' => $size->id,
                'quantity' => 1,
                'addon_ids' => [$addon->id],
            ]],
        ])->assertSessionHasErrors('items');

        $this->assertSame(100.0, (float) $espresso->fresh()->getCurrentStock());
        $this->assertSame(100.0, (float) $oatMilk->fresh()->getCurrentStock());
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_duplicate_or_inactive_addons_cannot_be_submitted_to_an_order(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier', 'name' => 'Jane Cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 50, 'status' => 'active']);
        $addon = ProductAddon::create(['name' => 'Vanilla Shot', 'price' => 10, 'status' => 'active']);

        $this->actingAs($cashier)->from(route('pos.index'))->post(route('pos.store'), [
            'cashier_name' => $cashier->name,
            'payment_method' => 'cash',
            'amount_received' => 100,
            'items' => [[
                'product_size_id' => $size->id,
                'quantity' => 1,
                'addon_ids' => [$addon->id, $addon->id],
            ]],
        ])->assertSessionHasErrors('items.0.addon_ids');

        $addon->update(['status' => 'inactive']);
        $this->actingAs($cashier)->from(route('pos.index'))->post(route('pos.store'), [
            'cashier_name' => $cashier->name,
            'payment_method' => 'cash',
            'amount_received' => 60,
            'items' => [[
                'product_size_id' => $size->id,
                'quantity' => 1,
                'addon_ids' => [$addon->id],
            ]],
        ])->assertSessionHasErrors('items.0.addon_ids.0');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_sale_is_rejected_when_a_recipe_ingredient_has_no_inventory_record(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier', 'name' => 'Jane Cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);
        $ingredient = Ingredient::create(['name' => 'Unconfigured Milk', 'unit' => 'ml', 'minimum_stock' => 5, 'status' => 'active']);
        $recipe = Recipe::create(['product_size_id' => $size->id, 'status' => 'active']);
        RecipeIngredient::create(['recipe_id' => $recipe->id, 'ingredient_id' => $ingredient->id, 'quantity' => 100]);

        $this->actingAs($cashier)->from(route('pos.index'))->post(route('pos.store'), [
            'cashier_name' => $cashier->name,
            'payment_method' => 'cash',
            'amount_received' => 100,
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ])->assertSessionHasErrors('items');

        $this->assertDatabaseMissing('orders', ['cashier_name' => $cashier->name]);
        $this->assertDatabaseMissing('inventory_transactions', ['ingredient_id' => $ingredient->id]);
    }

    public function test_a_split_payer_cannot_pay_more_than_their_assigned_products(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier', 'name' => 'Jane Cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);

        $this->actingAs($cashier)->post(route('pos.store'), [
            'cashier_name' => $cashier->name,
            'payment_method' => 'cash',
            'amount_received' => 50,
            'amount_paid' => 50,
            'person_name' => 'Alex',
            'items' => [
                ['product_size_id' => $size->id, 'quantity' => 1, 'assigned_to' => 'Alex'],
            ],
        ]);
        $order = Order::firstOrFail();

        $this->actingAs($cashier)->from(route('orders.show', $order))->post(route('orders.payments.store', $order), [
            'person_name' => 'Alex',
            'amount_paid' => 51,
            'payment_method' => 'cash',
            'amount_received' => 51,
        ])->assertSessionHasErrors('amount_paid');

        $this->assertDatabaseCount('payments', 1);
        $this->assertSame('partially_paid', $order->fresh()->status);
    }

    public function test_pending_and_failed_payment_attempts_do_not_reduce_the_order_balance(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);

        $this->actingAs($cashier)->post(route('pos.store'), [
            'cashier_name' => $cashier->name,
            'payment_method' => 'cash',
            'amount_received' => 50,
            'amount_paid' => 50,
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ]);
        $order = Order::firstOrFail();

        foreach (['pending', 'failed'] as $status) {
            $this->actingAs($cashier)->post(route('orders.payments.store', $order), [
                'amount_paid' => 50,
                'payment_method' => 'online',
                'payment_status' => $status,
                'reference_number' => 'REF-'.$status,
            ])->assertSessionHasNoErrors();
        }

        $this->assertSame('partially_paid', $order->fresh()->status);
        $this->assertEquals(50, $order->fresh()->remainingBalance());
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'pending', 'amount_received' => 0]);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'failed', 'amount_received' => 0]);
    }

    public function test_shift_cash_is_attributed_to_its_cashier_and_cash_refunds_reduce_expected_cash(): void
    {
        $firstCashier = User::factory()->create(['role' => 'cashier', 'name' => 'First Cashier']);
        $secondCashier = User::factory()->create(['role' => 'cashier', 'name' => 'Second Cashier']);
        foreach ([[$firstCashier, 500], [$secondCashier, 2000]] as [$cashier, $beginningCash]) {
            $this->actingAs($cashier)->post(route('shifts.start'), ['beginning_cash' => $beginningCash])
                ->assertSessionHasNoErrors();
        }

        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);
        $this->actingAs($secondCashier)->post(route('pos.store'), [
            'cashier_name' => $secondCashier->name,
            'payment_method' => 'cash',
            'amount_received' => 100,
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ]);
        $order = Order::firstOrFail();

        $this->actingAs($firstCashier)->getJson(route('shifts.current'))
            ->assertJsonPath('shift.beginning_cash', 500);
        $this->actingAs($secondCashier)->getJson(route('shifts.current'))
            ->assertJsonPath('shift.non_cash_summary.dine_in_sales', 100)
            ->assertJsonPath('shift.non_cash_summary.take_out_sales', 0);
        $this->actingAs($secondCashier)->postJson(route('shifts.preview-end'), ['actual_cash' => 2100])
            ->assertJsonPath('summary.cash_sales', 100)
            ->assertJsonPath('expected_cash', 2100);

        Refund::create([
            'order_id' => $order->id,
            'shift_id' => CashierShift::activeForUser($secondCashier->id)->id,
            'amount' => 40,
            'method' => 'cash',
            'status' => 'completed',
            'reason' => 'Cash refund test',
            'authorized_by' => 'Manager',
            'authorized_role' => 'manager',
            'refunded_at' => now(),
        ]);
        $order->payments()->update(['status' => 'refunded']);

        $this->actingAs($secondCashier)->postJson(route('shifts.preview-end'), ['actual_cash' => 2060])
            ->assertJsonPath('summary.cash_refunds', 40)
            ->assertJsonPath('expected_cash', 2060);
    }

    public function test_held_orders_are_not_included_in_shift_sales_totals(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->startShiftFor($cashier);
        $shift = CashierShift::activeForUser($cashier->id);

        foreach ([
            ['order_number' => 'ORD-HELD-SHIFT-001', 'order_type' => 'dine_in'],
            ['order_number' => 'ORD-HELD-SHIFT-002', 'order_type' => 'grab'],
        ] as $attributes) {
            Order::create(array_merge($attributes, [
                'cashier_name' => $cashier->name,
                'shift_id' => $shift->id,
                'subtotal' => 100,
                'total' => 100,
                'status' => 'held',
            ]));
        }

        $this->actingAs($cashier)->getJson(route('shifts.current'))
            ->assertOk()
            ->assertJsonPath('shift.unresolved_orders', 2)
            ->assertJsonPath('shift.non_cash_summary.dine_in_sales', 0)
            ->assertJsonPath('shift.non_cash_summary.grab_sales', 0);
    }

    public function test_follow_up_order_payment_is_attributed_to_the_shift_receiving_it(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier', 'name' => 'Order Cashier']);
        $manager = User::factory()->create(['role' => 'manager', 'name' => 'Payment Manager']);
        $this->startShiftFor($cashier, 100);
        $this->startShiftFor($manager, 500);

        $category = Category::create(['name' => 'Split Shift Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create([
            'product_id' => $product->id,
            'size_name' => 'Regular',
            'price' => 100,
            'status' => 'active',
        ]);

        $this->actingAs($cashier)->post(route('pos.store'), [
            'cashier_name' => $cashier->name,
            'payment_method' => 'cash',
            'amount_received' => 100,
            'amount_paid' => 100,
            'person_name' => 'Person A',
            'items' => [
                ['product_size_id' => $size->id, 'quantity' => 1, 'assigned_to' => 'Person A'],
                ['product_size_id' => $size->id, 'quantity' => 1, 'assigned_to' => 'Person B'],
            ],
        ])->assertSessionHasNoErrors();
        $order = Order::firstOrFail();

        $this->actingAs($manager)->post(route('orders.payments.store', $order), [
            'person_name' => 'Person B',
            'amount_paid' => 100,
            'amount_received' => 100,
            'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();

        $managerShift = CashierShift::activeForUser($manager->id);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'shift_id' => $managerShift->id,
            'customer_name' => 'Person B',
            'amount_paid' => 100,
            'status' => 'paid',
        ]);
        $this->actingAs($manager)->postJson(route('shifts.preview-end'), ['actual_cash' => 600])
            ->assertJsonPath('summary.cash_sales', 100)
            ->assertJsonPath('expected_cash', 600);
    }

    public function test_follow_up_grabfood_settlement_is_rejected_for_non_grab_orders(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier', 'name' => 'Settlement Cashier']);
        $this->startShiftFor($cashier);
        $order = Order::create([
            'order_number' => 'ORD-NO-GRAB-SETTLEMENT',
            'order_type' => 'dine_in',
            'cashier_name' => $cashier->name,
            'subtotal' => 100,
            'total' => 100,
            'status' => 'pending',
        ]);

        $this->actingAs($cashier)->from(route('orders.show', $order))
            ->post(route('orders.payments.store', $order), [
                'amount_paid' => 100,
                'payment_method' => 'grabfood',
            ])
            ->assertSessionHasErrors('payment_method');

        $this->assertDatabaseMissing('payments', ['order_id' => $order->id]);
    }

    public function test_follow_up_cash_payment_requires_cash_received(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Cash Tender Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);

        $this->actingAs($cashier)->post(route('pos.store'), [
            'payment_method' => 'cash',
            'amount_paid' => 50,
            'amount_received' => 50,
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ])->assertSessionHasNoErrors();
        $order = Order::firstOrFail();

        $this->actingAs($cashier)->from(route('orders.show', $order))->post(route('orders.payments.store', $order), [
            'amount_paid' => 50,
            'payment_method' => 'cash',
        ])->assertSessionHasErrors('amount_received');

        $this->assertDatabaseCount('payments', 1);
        $this->assertSame(50.0, $order->fresh()->remainingBalance());
    }

    public function test_manager_can_create_consecutive_pos_orders_with_a_discount(): void
    {
        $user = User::factory()->create(['role' => 'manager']);
        $this->startShiftFor($user);

        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Americano',
            'status' => 'active',
        ]);
        $size = ProductSize::create([
            'product_id' => $product->id,
            'size_name' => 'Large',
            'price' => 150.00,
            'status' => 'active',
        ]);

        // First order
        $response1 = $this->actingAs($user)->post(route('pos.store'), [
            'cashier_name' => 'John Cashier',
            'payment_method' => 'cash',
            'amount_received' => 200,
            'discount' => 0,
            'items' => [[
                'product_size_id' => $size->id,
                'quantity' => 1,
            ]],
        ]);
        $order1 = Order::where('subtotal', 150)->firstOrFail();
        $response1->assertRedirect(route('pos.success', ['order' => $order1]));

        // Second order directly afterwards
        $response2 = $this->actingAs($user)->post(route('pos.store'), [
            'cashier_name' => 'John Cashier',
            'payment_method' => 'cash',
            'amount_received' => 500,
            'discount' => 10,
            'items' => [[
                'product_size_id' => $size->id,
                'quantity' => 2,
            ]],
        ]);
        $order2 = Order::where('subtotal', 300)->firstOrFail();
        $response2->assertRedirect(route('pos.success', ['order' => $order2]));
        $this->assertEquals(290, $order2->total);
        $this->assertEquals(2, Order::count());
    }

    public function test_custom_discount_is_submitted_and_computed_with_tax(): void
    {
        $user = User::factory()->create(['role' => 'manager']);
        $this->startShiftFor($user);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 120, 'status' => 'active']);

        $response = $this->actingAs($user)->post(route('pos.store'), [
            'cashier_name' => 'Manager',
            'payment_method' => 'cash',
            'amount_received' => 100,
            'discount' => 20,
            'discount_type' => 'custom',
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ]);

        $order = Order::firstOrFail();
        $response->assertRedirect(route('pos.success', ['order' => $order]));
        $this->assertSame(20.0, (float) $order->discount);
        $this->assertSame('custom', $order->discount_type);
        $this->assertSame(100.0, (float) $order->total);
        $this->assertSame(10.71, (float) $order->tax_amount);
        $this->assertSame(89.29, (float) $order->vatable_sales);
        $discountAudit = \App\Models\AuditLog::where('action', 'discount_applied')
            ->where('reference_id', $order->id)
            ->firstOrFail();
        $this->assertSame($user->id, $discountAudit->actor_user_id);
        $this->assertSame('custom', $discountAudit->details['discount_type']);
        $this->assertSame(20.0, (float) $discountAudit->details['discount_amount']);
        $this->assertArrayHasKey('applied_at', $discountAudit->details);
    }

    public function test_pos_rejects_currency_inputs_more_precise_than_cents(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Precision Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);

        $this->actingAs($cashier)->from(route('pos.index'))->post(route('pos.store'), [
            'payment_method' => 'cash',
            'amount_received' => '100.001',
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ])->assertSessionHasErrors('amount_received');

        $this->actingAs($cashier)->from(route('pos.index'))->post(route('pos.store'), [
            'payment_method' => 'cash',
            'amount_received' => '100.00',
            'discount' => '0.001',
            'discount_type' => 'custom',
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ])->assertSessionHasErrors('discount');

        $this->actingAs($cashier)->from(route('pos.index'))->post(route('pos.store'), [
            'payment_method' => 'cash',
            'amount_received' => '100.00',
            'payments' => [[
                'method' => 'cash',
                'amount_paid' => '100.001',
                'amount_received' => '100.01',
            ]],
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ])->assertSessionHasErrors('payments.0.amount_paid');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_follow_up_order_payment_rejects_currency_precision_below_one_cent(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Follow-up Precision Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Mocha', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);

        $this->actingAs($cashier)->post(route('pos.store'), [
            'payment_method' => 'cash',
            'amount_received' => 50,
            'amount_paid' => 50,
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ])->assertSessionHasNoErrors();
        $order = Order::firstOrFail();

        $this->actingAs($cashier)->from(route('orders.show', $order))->post(route('orders.payments.store', $order), [
            'amount_paid' => '0.001',
            'amount_received' => '0.001',
            'payment_method' => 'cash',
        ])->assertSessionHasErrors(['amount_paid', 'amount_received']);

        $this->assertDatabaseCount('payments', 1);
        $this->assertSame(50.0, (float) $order->fresh()->remainingBalance());
    }

    public function test_senior_discount_computes_twenty_percent_and_exempts_tax(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);
        $this->startShiftFor($user);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 112, 'status' => 'active']);

        $response = $this->actingAs($user)->post(route('pos.store'), [
            'cashier_name' => 'Cashier',
            'payment_method' => 'cash',
            'amount_received' => 80,
            'discount_type' => 'senior',
            'discount_id_number' => 'SC-12345',
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ]);

        $order = Order::firstOrFail();
        $response->assertRedirect(route('pos.success', ['order' => $order]));
        $this->assertSame(20.0, (float) $order->discount);
        $this->assertSame('senior', $order->discount_type);
        $this->assertSame(80.0, (float) $order->total);
        $this->assertSame(0.0, (float) $order->tax_amount);
        $this->assertSame(80.0, (float) $order->vat_exempt_sales);
    }

    public function test_senior_discount_requires_customer_id_number(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);
        $this->startShiftFor($user);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);

        $response = $this->actingAs($user)->post(route('pos.store'), [
            'cashier_name' => 'Cashier',
            'payment_method' => 'cash',
            'amount_received' => 100,
            'discount_type' => 'senior',
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ]);

        $response->assertSessionHasErrors('discount_id_number');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_regular_orders_do_not_retain_a_discount_id_number(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);

        $this->actingAs($cashier)->post(route('pos.store'), [
            'cashier_name' => $cashier->name,
            'payment_method' => 'cash',
            'amount_received' => 100,
            'discount_type' => 'none',
            'discount_id_number' => 'SC-PRIVATE-123',
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ])->assertRedirect();

        $this->assertNull(Order::firstOrFail()->discount_id_number);
    }

    public function test_pos_page_wires_discount_selection_and_tax_calculation(): void
    {
        $user = User::factory()->create(['role' => 'manager']);

        $response = $this->actingAs($user)->get(route('pos.index'));

        $response->assertOk()
            ->assertSee('function onDiscountTypeChange()', false)
            ->assertSee('idInput.dataset.discountType !== discountType', false)
            ->assertSee('Received ₱${payment.amount_received.toFixed(2)} · Change ₱${Math.max(0, payment.amount_received - payment.amount_paid).toFixed(2)}', false)
            ->assertSee("document.getElementById('f-discount-type').value = discountType", false)
            ->assertSee("document.getElementById('f-discount-id-number').value = discountIdNumber", false)
            ->assertSee('subtotal / (1 + (taxRate / 100))', false)
            ->assertSee('setInterval(updateShiftInDateTime, 15000)', false)
            ->assertSee("timeZone: businessTimeZone", false)
            ->assertSee('function updateOrderSummary()', false)
            ->assertSee('VAT-Exempt Sales');
        $this->assertSame(1, substr_count($response->getContent(), 'onclick="confirmAddToCart()"'));
        $this->assertStringContainsString('max-h-[calc(100dvh-4rem)]', $response->getContent());
    }

    public function test_cashier_cannot_apply_a_discount(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);
        $this->startShiftFor($user);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Mocha', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 120, 'status' => 'active']);

        $response = $this->actingAs($user)->post(route('pos.store'), [
            'cashier_name' => 'Jane Cashier',
            'payment_method' => 'cash',
            'amount_received' => 120,
            'discount' => 10,
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ]);

        $response->assertSessionHasErrors('discount');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_discount_cannot_exceed_subtotal(): void
    {
        $user = User::factory()->create(['role' => 'manager']);
        $this->startShiftFor($user);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Mocha', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 120, 'status' => 'active']);

        $response = $this->actingAs($user)->post(route('pos.store'), [
            'cashier_name' => 'Manager',
            'payment_method' => 'cash',
            'amount_received' => 120,
            'discount' => 121,
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ]);

        $response->assertSessionHasErrors('discount');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_inactive_product_size_cannot_be_sold(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);
        $this->startShiftFor($user);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Mocha', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 120, 'status' => 'inactive']);

        $this->actingAs($user)->post(route('pos.store'), [
            'cashier_name' => 'Jane Cashier',
            'payment_method' => 'cash',
            'amount_received' => 120,
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ])->assertNotFound();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_online_payment_requires_reference_number(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);
        $this->startShiftFor($user);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Caramel Macchiato', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 140, 'status' => 'active']);

        $response = $this->actingAs($user)->post(route('pos.store'), [
            'cashier_name' => 'Jane Cashier',
            'payment_method' => 'online',
            'amount_received' => 140,
            'discount' => 0,
            'reference_number' => '', // Empty reference number
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ]);

        $response->assertSessionHasErrors('reference_number');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_cashier_can_create_pos_order_with_online_payment_and_reference_number(): void
    {
        $user = User::factory()->create(['role' => 'cashier', 'name' => 'Jane Cashier']);
        $this->startShiftFor($user);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Caramel Macchiato', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 140, 'status' => 'active']);

        $response = $this->actingAs($user)->post(route('pos.store'), [
            'cashier_name' => 'Jane Cashier',
            'payment_method' => 'online',
            'amount_received' => 140,
            'discount' => 0,
            'reference_number' => 'REF-ONLINE-998877',
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ]);

        if ($response->status() !== 302) {
            dump($response->status(), $response->exception ? $response->exception->getMessage() : 'No exception');
        }
        $response->assertSessionHasNoErrors();
        $order = Order::firstOrFail();
        $response->assertRedirect(route('pos.success', ['order' => $order]));

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => 'online',
            'reference_number' => 'REF-ONLINE-998877',
            'status' => 'paid',
        ]);

        // Verify success page shows payment details & reference number
        $successRes = $this->actingAs($user)->get(route('pos.success', ['order' => $order]));
        $successRes->assertOk();
        $successRes->assertSee('REF-ONLINE-998877');
        $successRes->assertSee('Online Payment');
        $successRes->assertSee('Print Receipt');

        // Verify printable receipt view
        $receiptRes = $this->actingAs($user)->get(route('orders.receipt', $order));
        $receiptRes->assertOk();
        $receiptRes->assertSee($order->order_number);
        $receiptRes->assertSee('REF-ONLINE-998877');
        $receiptRes->assertSee('Online Payment');
        $receiptRes->assertSee('Caramel Macchiato');
        $receiptRes->assertSee('Print Receipt');
    }

    public function test_pos_terminal_renders_only_cash_and_online_payment_options(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);
        $category = Category::create(['name' => 'Price Precision', 'status' => 'active']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Centavo Test Coffee',
            'status' => 'active',
        ]);
        ProductSize::create([
            'product_id' => $product->id,
            'size_name' => 'Regular',
            'price' => 12.34,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('pos.index'));
        $response->assertOk();

        $response->assertSee('style="zoom: 90%"', false);
        $response->assertSee('pos-main', false);
        $response->assertSee('pos-terminal', false);
        $response->assertSee('.pos-main [class~="text-xs"]', false);
        $response->assertSee('input.text-lg', false);
        $response->assertSee('flex-1 py-2.5 text-sm font-extrabold transition-all', false);
        $response->assertSee('id="total-display"', false);
        $response->assertSee('₱0.00</span>', false);
        $response->assertSee('text-base', false);
        // Must see Cash and Online Payment
        $response->assertSee('pm-cash', false);
        $response->assertSee('pm-online', false);
        $response->assertSee('Online Payment');
        $response->assertSee('pm-grabfood', false);
        $response->assertSee('disabled aria-disabled="true"', false);
        $response->assertSee('pos-cart-recovery-', false);
        $response->assertSee('persistPosCartRecovery()', false)
            ->assertSee('restorePosCartRecovery()', false);
        $response->assertSee("onclick=\"setCashAmount('exact')\"", false)
            ->assertSee("const amount = val === 'exact' ? due : Number(val)", false)
            ->assertSee("onclick=\"setCashAmount(100)\"", false);
        $response->assertSee('Platform settlement');
        $response->assertSee('Grab orders can be paid in cash at the counter');
        $response->assertSee("cashSec.style.display = showCash ? '' : 'none'", false);
        $response->assertDontSee('checkout-hold-btn', false);
        $response->assertSee('Saved Orders');
        $response->assertSee('>Orders</span>', false);
        $response->assertSee('Senior/PWD discounts require an ID.', false);
        $response->assertSee('Custom discounts are limited to managers and owners.', false);
        $response->assertSee('Current Order');
        $response->assertSee('Customer / Table');
        $response->assertSee('Split Payment');
        $response->assertSee('PAY ₱0.00');
        $response->assertSee('id="cashier-name" type="hidden"', false);
        $response->assertDontSee('id="cashier-name" type="text"', false);
        $response->assertSee('payment-person-name', false);
        $response->assertSee('Payment Amount');
        $response->assertSee('Paying Person');
        $response->assertSee('aria-labelledby="checkout-modal-title"', false);
        $response->assertSee('selectPaymentMethod(currentMethod)', false);
        $response->assertSee('refreshCartItemKey(item)', false);
        $response->assertSee("['pl-customer-name', 'pl-customer-phone', 'pl-due-date', 'pl-notes']", false);
        $response->assertSee('Customer / Table');
        $response->assertSee('id="cashier-name"', false);
        $response->assertSee('ot-dine-in', false);
        $response->assertSee('ot-take-out', false);
        $response->assertSee('split-toggle', false);
        $response->assertSee('Order type', false);
        $response->assertSee('aria-pressed="true"', false);
        $response->assertSee('100dvh', false);
        $response->assertSee('overscroll-contain', false);
        $response->assertSee('order-1', false);
        $response->assertSee('lg:grid-cols-[minmax(0,1fr)_minmax(24rem,30rem)]', false);
        $response->assertSee('h-auto min-h-0 w-full min-w-0', false);
        $response->assertSee('min-h-[15rem] flex-1 space-y-3 overflow-y-auto', false);
        $response->assertSee('lg:min-h-[15rem]', false);
        $response->assertSee('pos-order-panel', false);
        $response->assertSee('class="shrink-0 space-y-2 border-b', false);
        $response->assertSee('class="shrink-0 px-3 pb-3', false);
        $response->assertSee('@media (min-width: 1280px) and (max-height: 950px)', false);
        $response->assertSee('.pos-order-panel #cart-items', false);
        $response->assertSee('flex: 0 0 15rem', false);
        $response->assertSee('max-height: 32vh', false);
        $response->assertSee('@media (max-width: 1279px)', false);
        $response->assertSee('.pos-shell', false);
        $response->assertSee('zoom: 100% !important', false);
        $response->assertSee('.pos-terminal > .grid > .pos-order-panel', false);
        $response->assertSee('.pos-order-panel > .sticky.bottom-0', false);
        $response->assertSee('position: static', false);
        $response->assertSee('height: min(55vh, 34rem)', false);
        $response->assertSee('lg:grid-rows-[minmax(0,1fr)]', false);
        $response->assertSee('sticky bottom-0 z-20', false);
        $response->assertSee('toggleItemDetails', false);
        $response->assertSee('Edit item', false);
        $response->assertSee('revealCartItem(key)', false);
        $response->assertSee("item?.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' })", false);
        $response->assertSee('id="mobile-order-shortcut"', false);
        $response->assertSee('function updateMobileOrderShortcut()', false);
        $response->assertSee('function scrollToOrderPanel()', false);
        $response->assertSee('const visibleRatio = cartBounds?.height ? visibleHeight / cartBounds.height : 0', false);
        $response->assertSee('visibleRatio >= 0.65', false);
        $response->assertSee('checkout-order-review', false);
        $response->assertSee('checkout-review-rows', false);
        $response->assertSee('Assigned person', false);
        $response->assertSee('product-grid-empty', false);
        $response->assertSee('₱12.34');
        $response->assertSee("Number(addon.price).toFixed(2)", false);
        $response->assertSee("parseFloat(displayPrice).toFixed(2)", false);
        $response->assertSee("escapeHtml(addon.name.replace('Flavor: ', ''))", false);
        $response->assertSee('More ⋮');
        $response->assertSee('+ Add comment');
        $response->assertSee('data-product-size-prices', false);
        $response->assertSee('Grab Price: ON');
        $response->assertSee('Platform / Payment Note');
        $response->assertSee('function focusItemAssignee(key)', false);
        $response->assertSee('max="999"', false);
        $response->assertSee('step="1"', false);
        $response->assertSee("String(value).trim() === '' || !Number.isInteger(parsed)", false);
        $response->assertSee('if (combinedQuantity > 999)', false);
        $response->assertSee('Matching order items cannot be combined above 999 units.', false);
        $response->assertSee('currentHeldOrderId = null;', false);
        $response->assertSee('clearProductFilters()', false);
        $response->assertSee("payLaterAmount.textContent = formatCurrency(orderTotal)", false);
        $response->assertSee('!Number.isInteger(parsed)', false);
        $response->assertSee('char => `%${char.charCodeAt(0).toString(16)}`', false);
        $response->assertDontSee('Sales today');
        $response->assertDontSee('Orders today');
        $response->assertDontSee('Grab sales today');
        $response->assertDontSee('Unpaid Pay Later');

        // Must NOT see Maya, Card, or Bank transfer buttons or mentions
        $response->assertDontSee('pm-maya', false);
        $response->assertDontSee('pm-card', false);
        $response->assertDontSee('pm-bank', false);
        $response->assertDontSee('pm-gcash', false);
        $response->assertDontSee('GCash, Maya, QR, Transfer');
    }

    public function test_pos_product_cards_show_stock_status_and_disable_unavailable_products(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $unavailableProduct = Product::create(['category_id' => $category->id, 'name' => 'Unavailable Latte', 'status' => 'active']);
        $unavailableSize = ProductSize::create(['product_id' => $unavailableProduct->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);
        $emptyIngredient = Ingredient::create(['name' => 'Out Coffee', 'unit' => 'g', 'minimum_stock' => 2, 'status' => 'active']);
        Inventory::create(['ingredient_id' => $emptyIngredient->id, 'current_stock' => 0]);
        $unavailableRecipe = Recipe::create(['product_size_id' => $unavailableSize->id, 'status' => 'active']);
        RecipeIngredient::create(['recipe_id' => $unavailableRecipe->id, 'ingredient_id' => $emptyIngredient->id, 'quantity' => 1]);

        $lowStockProduct = Product::create(['category_id' => $category->id, 'name' => 'Low Stock Mocha', 'status' => 'active']);
        $lowStockSize = ProductSize::create(['product_id' => $lowStockProduct->id, 'size_name' => 'Regular', 'price' => 120, 'status' => 'active']);
        $lowIngredient = Ingredient::create(['name' => 'Low Coffee', 'unit' => 'g', 'minimum_stock' => 5, 'status' => 'active']);
        Inventory::create(['ingredient_id' => $lowIngredient->id, 'current_stock' => 2]);
        $lowStockRecipe = Recipe::create(['product_size_id' => $lowStockSize->id, 'status' => 'active']);
        RecipeIngredient::create(['recipe_id' => $lowStockRecipe->id, 'ingredient_id' => $lowIngredient->id, 'quantity' => 1]);

        $this->actingAs($user)->get(route('pos.index'))
            ->assertOk()
            ->assertSee('aria-label="Unavailable Latte, out of stock"', false)
            ->assertSee('OUT OF STOCK')
            ->assertSee('Low stock');
    }

    public function test_cashier_can_create_grab_order_with_grab_pricing_and_codes(): void
    {
        $user = User::factory()->create(['role' => 'cashier', 'name' => 'Charles Reyes']);
        $this->startShiftFor($user);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Iced Latte', 'status' => 'active']);
        $size = ProductSize::create([
            'product_id' => $product->id,
            'size_name' => '16oz',
            'price' => 150.00,
            'grab_price' => 170.00,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('pos.store'), [
            'order_type' => 'grab',
            'grab_order_code' => ' gf-20260927-0012 ',
            'rider_code' => ' rdr-025 ',
            'customer_name' => 'Grab Customer',
            'cashier_name' => 'Charles Reyes',
            'payment_method' => 'grabfood',
            'items' => [[
                'product_size_id' => $size->id,
                'quantity' => 1,
                'comment' => 'Less ice',
            ]],
        ]);

        $order = Order::where('grab_order_code', 'GF-20260927-0012')->firstOrFail();
        $response->assertRedirect(route('pos.success', ['order' => $order]));

        // Subtotal and total should reflect the Grab pricing of 170.00, not regular 150.00
        $this->assertEquals(170.00, $order->subtotal);
        $this->assertEquals(170.00, $order->total);
        $this->assertEquals('grab', $order->order_type);
        $this->assertEquals('RDR-025', $order->rider_code);
        $this->assertEquals('Less ice', $order->orderItems->first()->comment);

        // Payment check
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => 'grabfood',
            'amount_paid' => 170.00,
        ]);

        // Success and Receipt views check
        $successRes = $this->actingAs($user)->get(route('pos.success', ['order' => $order]));
        $successRes->assertOk();
        $successRes->assertSee('GF-20260927-0012');
        $successRes->assertSee('RDR-025');

        $receiptRes = $this->actingAs($user)->get(route('orders.receipt', $order));
        $receiptRes->assertOk();
        $receiptRes->assertSee('GF-20260927-0012');
        $receiptRes->assertSee('RDR-025');
    }

    public function test_cashier_can_take_cash_payment_for_grab_order(): void
    {
        $user = User::factory()->create(['role' => 'cashier', 'name' => 'Charles Reyes']);
        $this->actingAs($user)->post(route('shifts.start'), ['beginning_cash' => 500])
            ->assertSessionHasNoErrors();

        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Iced Latte', 'status' => 'active']);
        $size = ProductSize::create([
            'product_id' => $product->id,
            'size_name' => '16oz',
            'price' => 150.00,
            'grab_price' => 170.00,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('pos.store'), [
            'order_type' => 'grab',
            'grab_order_code' => 'GF-CASH-001',
            'rider_code' => 'RDR-CASH-001',
            'cashier_name' => $user->name,
            'payment_method' => 'cash',
            'amount_received' => 200,
            'items' => [[
                'product_size_id' => $size->id,
                'quantity' => 1,
            ]],
        ]);

        $order = Order::where('grab_order_code', 'GF-CASH-001')->firstOrFail();
        $response->assertRedirect(route('pos.success', ['order' => $order]));

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => 'cash',
            'amount_received' => 200,
            'amount_paid' => 170,
            'change_amount' => 30,
            'status' => 'paid',
        ]);

        $this->actingAs($user)->postJson(route('shifts.preview-end'), ['actual_cash' => 670])
            ->assertJsonPath('summary.cash_sales', 170)
            ->assertJsonPath('expected_cash', 670);

        $this->actingAs($user)->get(route('pos.success', ['order' => $order]))
            ->assertOk()
            ->assertSee('Cash');

        $manager = User::factory()->create(['role' => 'manager']);
        $this->actingAs($manager)->get(route('reports.grab'))
            ->assertOk()
            ->assertSee('GF-CASH-001')
            ->assertSee('170.00')
            ->assertSee('Payment Method')
            ->assertSee('Cash');

        $grabExport = $this->actingAs($manager)->get(route('reports.grab', ['export' => 'excel']));
        $grabExport->assertOk();
        $this->assertStringContainsString('Payment Methods (Amount)', $grabExport->streamedContent());
        $this->assertStringContainsString('Cash (170.00)', $grabExport->streamedContent());

        $businessDate = now(config('app.business_timezone', 'Asia/Manila'))->toDateString();
        $this->actingAs($manager)->get(route('reports.sales', ['date' => $businessDate]))
            ->assertOk()
            ->assertSee('Cash')
            ->assertSee('170.00');
    }

    public function test_cashier_shift_in_and_shift_out_flow(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier', 'name' => 'Charles Reyes']);

        // 1. Shift In (Start Shift)
        $startRes = $this->actingAs($cashier)->post(route('shifts.start'), [
            'beginning_cash' => 2000.00,
        ]);
        $startRes->assertSessionHasNoErrors();

        $this->assertDatabaseHas('cashier_shifts', [
            'user_id' => $cashier->id,
            'cashier_name' => 'Charles Reyes',
            'beginning_cash' => 2000.00,
            'status' => 'open',
        ]);

        // 2. Check active shift endpoint
        $currentRes = $this->actingAs($cashier)->getJson(route('shifts.current'));
        $currentRes->assertOk();
        $currentRes->assertJson([
            'active' => true,
            'shift' => [
                'cashier_name' => 'Charles Reyes',
                'beginning_cash' => 2000.00,
            ],
        ]);

        // 3. Make cash order
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100.00, 'status' => 'active']);

        $this->actingAs($cashier)->post(route('pos.store'), [
            'cashier_name' => 'Charles Reyes',
            'payment_method' => 'cash',
            'amount_received' => 100.00,
            'discount' => 0,
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ]);

        // Check active shift now reflects cash sales
        $currentRes2 = $this->actingAs($cashier)->postJson(route('shifts.preview-end'), ['actual_cash' => 2100]);
        $currentRes2->assertOk()
            ->assertJsonPath('summary.cash_sales', 100)
            ->assertJsonPath('expected_cash', 2100);

        // 4. Shift Out (End Shift)
        $endRes = $this->actingAs($cashier)->post(route('shifts.end'), [
            'actual_cash' => 2090.00,
            'comment' => 'Short ₱10 difference',
        ]);
        $endRes->assertSessionHasNoErrors();

        $this->assertDatabaseHas('cashier_shifts', [
            'user_id' => $cashier->id,
            'status' => 'closed',
            'beginning_cash' => 2000.00,
            'cash_sales' => 100.00,
            'expected_cash' => 2100.00,
            'actual_cash' => 2090.00,
            'difference' => -10.00,
            'notes' => 'Short ₱10 difference',
        ]);
    }

    public function test_shift_cash_inputs_reject_precision_beyond_cents(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($cashier)->from(route('pos.index'))->post(route('shifts.start'), [
            'beginning_cash' => '100.001',
        ])->assertSessionHasErrors('beginning_cash');
        $this->assertDatabaseCount('cashier_shifts', 0);

        $this->actingAs($cashier)->post(route('shifts.start'), [
            'beginning_cash' => '100.00',
        ])->assertSessionHasNoErrors();
        $shift = CashierShift::activeForUser($cashier->id);

        $this->actingAs($cashier)->postJson(route('shifts.preview-end'), [
            'actual_cash' => '100.001',
        ])->assertUnprocessable()->assertJsonValidationErrors('actual_cash');

        $this->actingAs($cashier)->postJson(route('shifts.end'), [
            'actual_cash' => '100.001',
        ])->assertUnprocessable()->assertJsonValidationErrors('actual_cash');

        $this->assertSame('open', $shift->fresh()->status);
    }

    public function test_shift_start_and_end_use_local_business_time_and_store_the_actual_instants(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $manager = User::factory()->create(['role' => 'manager']);
        $businessTimezone = config('app.business_timezone');

        try {
            Carbon::setTestNow(Carbon::parse('2026-10-01 20:34:21', 'UTC'));
            $this->actingAs($cashier)->postJson(route('shifts.start'), ['beginning_cash' => 500])
                ->assertOk()
                ->assertJsonPath('shift.start_time', '2026-10-02 04:34:21')
                ->assertJsonPath('shift.started_at', '2026-10-02T04:34:21+08:00');
            $this->actingAs($cashier)->getJson(route('shifts.current'))
                ->assertOk()
                ->assertJsonPath('shift.shift_date', 'Oct 02, 2026')
                ->assertJsonPath('shift.start_time', '04:34 AM')
                ->assertJsonPath('shift.started_at', '2026-10-02T04:34:21+08:00');

            $shift = CashierShift::activeForUser($cashier->id);
            $this->assertDatabaseHas('cashier_shifts', [
                'id' => $shift->id,
                'shift_date' => '2026-10-02 00:00:00',
                'start_time' => '2026-10-01 20:34:21',
            ]);

            Carbon::setTestNow(Carbon::parse('2026-10-01 21:15:00', 'UTC'));
            $this->actingAs($cashier)->postJson(route('shifts.end'), ['actual_cash' => 500])
                ->assertOk()
                ->assertJsonPath('shift.end_time', '2026-10-02 05:15:00')
                ->assertJsonPath('shift.ended_at', '2026-10-02T05:15:00+08:00');

            $shift->refresh();
            $this->assertSame('2026-10-01 21:15:00', $shift->getRawOriginal('end_time'));
            $this->assertSame('05:15 AM', $shift->localEndTime()->format('h:i A'));
            $this->actingAs($manager)->get(route('reports.shifts.show', $shift))
                ->assertOk()
                ->assertSee('Oct 02, 04:34 AM')
                ->assertSee('Oct 02, 05:15 AM');
        } finally {
            Carbon::setTestNow();
            config(['app.business_timezone' => $businessTimezone]);
        }
    }

    public function test_order_number_date_prefix_uses_philippine_business_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-06 16:30:00', 'UTC'));

        try {
            $orderNumber = Order::generateOrderNumber();

            $this->assertStringStartsWith('ORD-20261007-', $orderNumber);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_shift_count_can_be_previewed_and_closed_by_denomination(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->actingAs($cashier)->post(route('shifts.start'), ['beginning_cash' => 200])
            ->assertSessionHasNoErrors();

        $this->actingAs($cashier)->getJson(route('shifts.current'))
            ->assertOk()
            ->assertJsonMissingPath('shift.expected_cash');

        $count = ['denomination_count' => ['100' => 2, '50' => 1]];
        $this->actingAs($cashier)->postJson(route('shifts.preview-end'), $count)
            ->assertOk()
            ->assertJsonPath('actual_cash', 250)
            ->assertJsonPath('expected_cash', 200)
            ->assertJsonPath('difference', 50);

        $this->actingAs($cashier)->post(route('shifts.end'), $count + ['comment' => 'Extra cash found'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('cashier_shifts', [
            'user_id' => $cashier->id,
            'status' => 'closed',
            'actual_cash' => 250,
            'difference' => 50,
            'denomination_count' => json_encode(['100' => 2, '50' => 1]),
        ]);
    }

    public function test_cashier_cannot_close_shift_with_held_orders_without_manager_approval(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $manager = User::factory()->create(['role' => 'manager', 'status' => 'active']);
        $this->actingAs($cashier)->post(route('shifts.start'), ['beginning_cash' => 100])
            ->assertSessionHasNoErrors();
        $shift = CashierShift::activeForUser($cashier->id);

        Order::create([
            'order_number' => 'ORD-HELD-SHIFT-001',
            'cashier_name' => $cashier->name,
            'shift_id' => $shift->id,
            'subtotal' => 50,
            'total' => 50,
            'status' => 'held',
        ]);

        $this->actingAs($cashier)->postJson(route('shifts.end'), ['actual_cash' => 100])
            ->assertUnprocessable()
            ->assertJsonPath('open_orders', 1)
            ->assertJsonPath('manager_approval_required', true);

        $this->actingAs($cashier)->postJson(route('shifts.end'), [
            'actual_cash' => 100,
            'authorizer_email' => $manager->email,
            'authorizer_password' => 'password',
            'override_reason' => 'Manager approved unresolved ticket',
        ])->assertOk();

        $this->assertDatabaseHas('cashier_shifts', [
            'id' => $shift->id,
            'status' => 'closed',
            'closed_by' => $cashier->id,
        ]);
    }

    public function test_cashier_cannot_start_a_second_open_shift(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($cashier)->post(route('shifts.start'), ['beginning_cash' => 100])
            ->assertSessionHasNoErrors();
        $this->actingAs($cashier)->post(route('shifts.start'), ['beginning_cash' => 200])
            ->assertSessionHasErrors('shift');

        $this->assertDatabaseCount('cashier_shifts', 1);
    }


    public function test_thermal_receipt_renders_pure_receipt_data_and_print_isolation(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier', 'name' => 'Charles Reyes']);
        $this->startShiftFor($cashier);
        $category = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Spanish Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => '16oz', 'price' => 140.00, 'status' => 'active']);

        $this->actingAs($cashier)->post(route('pos.store'), [
            'cashier_name' => 'Charles Reyes',
            'payment_method' => 'cash',
            'amount_received' => 200.00,
            'discount' => 0,
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ]);

        $order = Order::firstOrFail();

        // 1. Dedicated thermal receipt endpoint
        $receiptRes = $this->actingAs($cashier)->get(route('orders.receipt', $order));
        $receiptRes->assertOk();
        $receiptRes->assertSee('80mm auto', false); // 80mm thermal page size
        $receiptRes->assertSee('OFFICIAL SALES RECEIPT');
        $receiptRes->assertSee('HEIM COFFEE');
        $receiptRes->assertSee('Spanish Latte');
        $receiptRes->assertSee('Change Due');
        $receiptRes->assertSee('thermal-receipt-body');

        // 2. POS Success Page print isolation
        $successRes = $this->actingAs($cashier)->get(route('pos.success', $order));
        $successRes->assertOk();
        $successRes->assertSee('thermal-receipt-print-area');
        $successRes->assertSee('printThermalReceipt');
        $successRes->assertSee('thermal-receipt-iframe');
    }

    public function test_pos_rejects_order_creation_without_an_open_shift(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $category = Category::create(['name' => 'Shift Lock Coffee', 'status' => 'active']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $product->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);

        $this->actingAs($cashier)->get(route('pos.index'))
            ->assertOk()
            ->assertSee('POS Locked · Shift Required')
            ->assertSee('Start Shift');

        $this->actingAs($cashier)->from(route('pos.index'))->post(route('pos.store'), [
            'cashier_name' => $cashier->name,
            'payment_method' => 'cash',
            'amount_received' => 100,
            'items' => [['product_size_id' => $size->id, 'quantity' => 1]],
        ])->assertSessionHasErrors('shift');

        $this->assertDatabaseCount('orders', 0);
    }

    private function startShiftFor(User $user, float $beginningCash = 0): void
    {
        $this->actingAs($user)
            ->postJson(route('shifts.start'), ['beginning_cash' => $beginningCash])
            ->assertOk();
    }
}
