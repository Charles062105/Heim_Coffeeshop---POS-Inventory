<?php

namespace Tests\Feature;

use App\Models\AddonIngredient;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Notification;
use App\Models\NotificationRead;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductSize;
use App\Models\TaxSetting;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SystemAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_are_limited_to_the_authenticated_users_role(): void
    {
        $managerAlert = Notification::create([
            'type' => 'low_stock',
            'title' => 'Low stock',
            'message' => 'Milk is low.',
            'target_role' => 'manager',
        ]);
        $sharedAlert = Notification::create([
            'type' => 'general',
            'title' => 'General notice',
            'message' => 'Store notice.',
            'target_role' => 'all',
        ]);

        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->actingAs($cashier)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertViewHas('notifications', fn ($notifications) => $notifications->total() === 1
                && $notifications->first()->is($sharedAlert))
            ->assertViewHas('unreadCount', 1)
            ->assertViewHas('openCount', 1);

        $this->actingAs($cashier)
            ->patch(route('notifications.read', $managerAlert))
            ->assertNotFound();
        $this->actingAs($cashier)
            ->patch(route('notifications.resolve', $managerAlert))
            ->assertNotFound();

        $this->actingAs(User::factory()->create(['role' => 'manager']))
            ->get(route('notifications.index'))
            ->assertViewHas('notifications', fn ($notifications) => $notifications->total() === 2);

        $this->actingAs(User::factory()->create(['role' => 'owner']))
            ->get(route('notifications.index'))
            ->assertViewHas('notifications', fn ($notifications) => $notifications->total() === 2);
    }

    public function test_mark_all_read_only_affects_notifications_visible_to_the_user_role(): void
    {
        $managerAlert = Notification::create([
            'type' => 'low_stock',
            'title' => 'Low stock',
            'message' => 'Milk is low.',
            'target_role' => 'manager',
        ]);
        $sharedAlert = Notification::create([
            'type' => 'general',
            'title' => 'General notice',
            'message' => 'Store notice.',
            'target_role' => 'all',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'cashier']))
            ->post(route('notifications.markAllRead'))
            ->assertRedirect();

        $this->assertNull($managerAlert->fresh()->read_at);
        $this->assertNull($sharedAlert->fresh()->read_at);
        $this->assertDatabaseHas('notification_reads', [
            'notification_id' => $sharedAlert->id,
        ]);
        $this->assertDatabaseMissing('notification_reads', [
            'notification_id' => $managerAlert->id,
        ]);

        $anotherCashier = User::factory()->create(['role' => 'cashier']);
        $this->actingAs($anotherCashier)
            ->get(route('notifications.index'))
            ->assertViewHas('unreadCount', 1);
    }

    public function test_reading_notification_is_per_user_but_resolving_is_shared(): void
    {
        $notification = Notification::create([
            'type' => 'general',
            'title' => 'Store notice',
            'message' => 'Review this notice.',
            'target_role' => 'manager',
        ]);
        $firstManager = User::factory()->create(['role' => 'manager']);
        $secondManager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($firstManager)
            ->patch(route('notifications.read', $notification))
            ->assertRedirect();

        $this->actingAs($firstManager)
            ->get(route('notifications.index'))
            ->assertViewHas('unreadCount', 0);
        $this->actingAs($secondManager)
            ->get(route('notifications.index'))
            ->assertViewHas('unreadCount', 1);

        $this->actingAs($firstManager)
            ->patch(route('notifications.resolve', $notification))
            ->assertRedirect();

        $this->assertTrue($notification->fresh()->is_resolved);
        $this->assertSame(1, NotificationRead::where('notification_id', $notification->id)->count());
        $this->actingAs($secondManager)
            ->get(route('notifications.index'))
            ->assertViewHas('openCount', 0)
            ->assertViewHas('unreadCount', 1);
    }

    public function test_notification_filters_reject_unsupported_values(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)
            ->get(route('notifications.index', ['type' => 'unknown']))
            ->assertSessionHasErrors('type');

        $this->actingAs($manager)
            ->get(route('notifications.index', ['unread' => 'sometimes']))
            ->assertSessionHasErrors('unread');
    }

    public function test_audit_log_records_reason_and_ip_address(): void
    {
        $user = User::factory()->create(['name' => 'Alice Manager', 'role' => 'manager']);

        $log = AuditService::logFromUser(
            $user,
            'updated_user',
            'Users',
            ['user' => $user->name],
            $user,
            'Customer profile updated during manual verification.',
            '203.0.113.42'
        );

        $this->assertSame('Customer profile updated during manual verification.', $log->reason);
        $this->assertSame('203.0.113.42', $log->ip_address);
        $this->assertDatabaseHas('audit_logs', [
            'actor_name' => 'Alice Manager',
            'reason' => 'Customer profile updated during manual verification.',
            'ip_address' => '203.0.113.42',
        ]);
    }

    public function test_inventory_filter_maintains_summary_counters(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        // Ingredient 1: good stock
        $ing1 = Ingredient::create(['name' => 'Beans', 'unit' => 'g', 'minimum_stock' => 100, 'status' => 'active']);
        Inventory::create(['ingredient_id' => $ing1->id, 'current_stock' => 500]);

        // Ingredient 2: low stock
        $ing2 = Ingredient::create(['name' => 'Milk', 'unit' => 'ml', 'minimum_stock' => 200, 'status' => 'active']);
        Inventory::create(['ingredient_id' => $ing2->id, 'current_stock' => 50]);

        // Ingredient 3: out of stock
        $ing3 = Ingredient::create(['name' => 'Syrup', 'unit' => 'ml', 'minimum_stock' => 50, 'status' => 'active']);
        Inventory::create(['ingredient_id' => $ing3->id, 'current_stock' => 0]);

        // When filtered by low_stock
        $response = $this->actingAs($manager)->get(route('inventory.index', ['stock_status' => 'low_stock']));
        $response->assertOk();

        // The view should receive correct counts for all categories
        $response->assertViewHas('good', 1);
        $response->assertViewHas('low', 1);
        $response->assertViewHas('outOfStock', 1);

        // But the filtered ingredients collection only contains the low stock item
        $ingredients = $response->viewData('ingredients');
        $this->assertCount(1, $ingredients);
        $this->assertEquals('Milk', $ingredients->first()->name);
    }

    public function test_inventory_and_transaction_filters_reject_unsupported_values(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)
            ->get(route('inventory.index', ['stock_status' => 'unknown']))
            ->assertSessionHasErrors('stock_status');

        $this->actingAs($manager)
            ->get(route('inventory.transactions', ['ingredient_id' => 999999]))
            ->assertSessionHasErrors('ingredient_id');

        $this->actingAs($manager)
            ->get(route('inventory.transactions', ['from' => 'not-a-date', 'to' => '2026-10-08']))
            ->assertSessionHasErrors('from');

        $this->actingAs($manager)
            ->get(route('inventory.transactions', ['from' => '2026-10-08', 'to' => '2026-10-07']))
            ->assertSessionHasErrors('to');
    }

    public function test_product_ingredient_and_user_list_filters_reject_unsupported_values(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $owner = User::factory()->create(['role' => 'owner']);

        $this->actingAs($manager)
            ->get(route('products.index', ['status' => 'archived']))
            ->assertSessionHasErrors('status');
        $this->actingAs($manager)
            ->get(route('products.index', ['category_id' => 999999]))
            ->assertSessionHasErrors('category_id');
        $this->actingAs($manager)
            ->get(route('ingredients.index', ['stock_status' => 'good']))
            ->assertSessionHasErrors('stock_status');
        $this->actingAs($manager)
            ->get(route('ingredients.index', ['status' => 'archived']))
            ->assertSessionHasErrors('status');
        $this->actingAs($owner)
            ->get(route('users.index', ['role' => 'supervisor']))
            ->assertSessionHasErrors('role');
        $this->actingAs($owner)
            ->get(route('users.index', ['status' => 'archived']))
            ->assertSessionHasErrors('status');
    }

    public function test_inventory_capacity_percentage_matches_reorder_level_bar(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $ingredient = Ingredient::create([
            'name' => 'Capacity Check Beans',
            'unit' => 'g',
            'minimum_stock' => 50,
            'reorder_level' => 200,
            'status' => 'active',
        ]);
        Inventory::create(['ingredient_id' => $ingredient->id, 'current_stock' => 120]);
        $adequateIngredient = Ingredient::create([
            'name' => 'Above Threshold Beans',
            'unit' => 'g',
            'minimum_stock' => 50,
            'reorder_level' => 200,
            'status' => 'active',
        ]);
        Inventory::create(['ingredient_id' => $adequateIngredient->id, 'current_stock' => 400]);

        $response = $this->actingAs($manager)->get(route('inventory.index'));

        $response->assertOk()
            ->assertSee('60%')
            ->assertSee('width: 60%', false)
            ->assertSee('100%')
            ->assertSee('width: 100%', false)
            ->assertDontSee('200%')
            ->assertDontSee('240%');
    }

    public function test_ingredient_threshold_precision_matches_inventory_storage(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $this->actingAs($manager)->from(route('ingredients.create'))->post(route('ingredients.store'), [
            'name' => 'Precision Threshold Beans',
            'unit' => 'g',
            'minimum_stock' => '1.2345',
            'reorder_level' => '2.3456',
            'cost' => '3.456',
            'status' => 'active',
        ])->assertSessionHasErrors(['minimum_stock', 'reorder_level', 'cost']);

        $this->assertDatabaseMissing('ingredients', ['name' => 'Precision Threshold Beans']);

        $ingredient = Ingredient::create([
            'name' => 'Editable Threshold Beans',
            'unit' => 'g',
            'minimum_stock' => 1.234,
            'reorder_level' => 2.345,
            'cost' => 3.45,
            'status' => 'active',
        ]);

        $this->actingAs($manager)->from(route('ingredients.edit', $ingredient))->put(route('ingredients.update', $ingredient), [
            'name' => $ingredient->name,
            'unit' => $ingredient->unit,
            'minimum_stock' => '1.2345',
            'reorder_level' => '2.345',
            'cost' => '3.45',
            'status' => $ingredient->status,
        ])->assertSessionHasErrors('minimum_stock');

        $this->assertSame('1.234', $ingredient->fresh()->minimum_stock);
    }

    public function test_tax_rate_rejects_precision_above_tax_configuration_storage(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $taxSetting = TaxSetting::current();
        $originalRate = (float) $taxSetting->rate;

        $this->actingAs($manager)->from(route('settings.tax.edit'))->put(route('settings.tax.update'), [
            'name' => $taxSetting->name,
            'rate' => '12.345',
            'is_inclusive' => '1',
            'is_active' => '1',
        ])->assertSessionHasErrors('rate');

        $this->assertSame($originalRate, (float) $taxSetting->fresh()->rate);
    }

    public function test_historical_consumption_opens_net_inventory_report(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $pastDate = '2026-09-01';

        $ing = Ingredient::create(['name' => 'Espresso Beans', 'unit' => 'g', 'minimum_stock' => 50, 'status' => 'active']);
        // Live stock today is 10
        Inventory::create(['ingredient_id' => $ing->id, 'current_stock' => 10]);

        // On 2026-09-01: started at 100, sold 30, remaining at end of day was 70
        $tx = new InventoryTransaction([
            'ingredient_id' => $ing->id,
            'type' => 'sales_consumption',
            'quantity' => 30,
            'previous_stock' => 100,
            'new_stock' => 70,
            'performed_by' => 'Cashier',
            'performed_role' => 'cashier',
        ]);
        $tx->created_at = "{$pastDate} 10:00:00";
        $tx->updated_at = "{$pastDate} 10:00:00";
        $tx->save();

        $response = $this->actingAs($manager)->get(route('consumption.index', ['date' => $pastDate]));
        $response->assertRedirect(route('reports.inventory', [
            'from' => $pastDate,
            'to' => $pastDate,
        ]));

        $ledger = $this->actingAs($manager)->get(route('adjustments.index', [
            'from' => $pastDate,
            'to' => $pastDate,
            'type' => 'sales_consumption',
        ]));
        $ledger->assertOk();
        $this->assertCount(1, $ledger->viewData('transactions'));
        $this->assertEquals(70, $ledger->viewData('transactions')->first()->new_stock);
        $this->assertEquals(30, $ledger->viewData('summary')['deductions']->first()->total);
    }

    public function test_cashier_can_submit_refund_with_manager_authorization(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier', 'name' => 'Cashier One']);
        $this->actingAs($cashier)->postJson(route('shifts.start'), ['beginning_cash' => 500])->assertOk();
        $manager = User::factory()->create([
            'role' => 'manager',
            'name' => 'Manager Bob',
            'email' => 'manager.auth@example.com',
            'password' => Hash::make('supersecret123'),
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-001',
            'cashier_name' => 'Cashier One',
            'subtotal' => 150,
            'discount' => 0,
            'total' => 150,
            'status' => 'completed',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'method' => 'cash',
            'amount_received' => 200,
            'amount_paid' => 150,
            'change_amount' => 50,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($cashier)->post(route('refunds.refund', $order), [
            'authorizer_email' => 'manager.auth@example.com',
            'authorizer_password' => 'supersecret123',
            'reason' => 'Customer requested refund for spilled drink.',
            'restore_stock' => 0,
        ]);

        $response->assertRedirect(route('orders.show', $order));
        $this->assertEquals('refunded', $order->fresh()->status);
        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'authorized_by' => 'Manager Bob',
        ]);
    }

    public function test_cashier_cannot_submit_refund_with_invalid_credentials(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->actingAs($cashier)->postJson(route('shifts.start'), ['beginning_cash' => 500])->assertOk();

        $order = Order::create([
            'order_number' => 'ORD-TEST-002',
            'cashier_name' => 'Cashier One',
            'subtotal' => 100,
            'discount' => 0,
            'total' => 100,
            'status' => 'completed',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'method' => 'cash',
            'amount_received' => 100,
            'amount_paid' => 100,
            'change_amount' => 0,
            'status' => 'paid',
        ]);

        $response = $this->actingAs($cashier)->post(route('refunds.refund', $order), [
            'authorizer_email' => 'wrong@example.com',
            'authorizer_password' => 'wrongpass',
            'reason' => 'Customer requested refund for drink.',
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals('completed', $order->fresh()->status);
    }

    public function test_owner_can_archive_and_unarchive_staff_accounts_and_filter_them_by_status(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'status' => 'active']);
        $staff = User::factory()->create(['role' => 'cashier', 'status' => 'active']);

        $this->actingAs($owner)
            ->get(route('users.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee('Archive')
            ->assertSee('text-green-700', false);

        $this->from(route('users.index', ['status' => 'active']))
            ->patch(route('users.toggle', $staff))
            ->assertRedirect(route('users.index', ['status' => 'active']))
            ->assertSessionHas('success', "User \"{$staff->name}\" is now archived.");

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'status' => 'inactive',
        ]);
        $archivedLog = AuditLog::where('action', 'toggled_user_status')->orderByDesc('id')->firstOrFail();
        $this->assertSame($owner->id, $archivedLog->actor_user_id);
        $this->assertSame($staff->id, $archivedLog->reference_id);
        $this->assertSame(['user' => $staff->name, 'status' => 'inactive'], $archivedLog->details);

        $this->get(route('users.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee($staff->email)
            ->assertSee('Unarchive')
            ->assertSee('text-red-700', false);

        $this->from(route('users.index', ['status' => 'inactive']))
            ->patch(route('users.toggle', $staff))
            ->assertRedirect(route('users.index', ['status' => 'inactive']))
            ->assertSessionHas('success', "User \"{$staff->name}\" is now unarchived.");

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'status' => 'active',
        ]);
        $unarchivedLog = AuditLog::where('action', 'toggled_user_status')->orderByDesc('id')->firstOrFail();
        $this->assertSame($owner->id, $unarchivedLog->actor_user_id);
        $this->assertSame($staff->id, $unarchivedLog->reference_id);
        $this->assertSame(['user' => $staff->name, 'status' => 'active'], $unarchivedLog->details);
    }

    public function test_owner_cannot_archive_their_own_account(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'status' => 'active']);

        $this->actingAs($owner)
            ->patch(route('users.toggle', $owner))
            ->assertRedirect()
            ->assertSessionHas('error', 'You cannot archive your own account.');

        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'toggled_user_status',
            'target_id' => $owner->id,
        ]);
    }

    public function test_archived_account_sessions_are_logged_out_and_denied_authenticated_routes(): void
    {
        $staff = User::factory()->create(['role' => 'cashier', 'status' => 'inactive']);

        $this->actingAs($staff)
            ->get(route('pos.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'Your account is archived. Please contact store management or the owner.',
            ]);

        $this->assertGuest();
    }

    public function test_user_accounts_are_archived_instead_of_permanently_deleted(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'status' => 'active']);
        $staff = User::factory()->create(['role' => 'cashier', 'status' => 'active']);

        $this->actingAs($owner)
            ->delete('/users/'.$staff->id)
            ->assertStatus(405);

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'status' => 'active',
        ]);
    }

    public function test_user_administration_requires_password_confirmation_for_create_and_reset(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'status' => 'active']);
        $staff = User::factory()->create(['role' => 'cashier', 'status' => 'active']);
        $originalPassword = $staff->password;

        $this->actingAs($owner)
            ->from(route('users.create'))
            ->post(route('users.store'), [
                'name' => 'Unconfirmed User',
                'email' => 'unconfirmed@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Different123!',
                'role' => 'cashier',
            ])
            ->assertRedirect(route('users.create'))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'unconfirmed@example.com']);

        $this->from(route('users.edit', $staff))
            ->put(route('users.update', $staff), [
                'name' => $staff->name,
                'email' => $staff->email,
                'password' => 'Password123!',
                'password_confirmation' => 'Different123!',
            ])
            ->assertRedirect(route('users.edit', $staff))
            ->assertSessionHasErrors('password');

        $this->assertSame($originalPassword, $staff->fresh()->password);
    }

    public function test_owner_can_reset_staff_password_through_user_administration(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'status' => 'active']);
        $staff = User::factory()->create(['role' => 'cashier', 'status' => 'active']);

        $this->actingAs($owner)
            ->put(route('users.update', $staff), [
                'name' => $staff->name,
                'email' => $staff->email,
                'password' => 'OwnerSetPass123!',
                'password_confirmation' => 'OwnerSetPass123!',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'User updated.');

        $this->assertTrue(Hash::check('OwnerSetPass123!', $staff->fresh()->password));
    }

    public function test_dashboard_top_products_only_counts_completed_orders(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $cat = Category::create(['name' => 'Coffee', 'status' => 'active']);
        $prod = Product::create(['category_id' => $cat->id, 'name' => 'Latte', 'status' => 'active']);
        $size = ProductSize::create(['product_id' => $prod->id, 'size_name' => 'Regular', 'price' => 100, 'status' => 'active']);

        // Cancelled order with 50 units
        $cancelledOrder = Order::create([
            'order_number' => 'ORD-CANCELLED',
            'cashier_name' => 'Cashier',
            'subtotal' => 5000,
            'discount' => 0,
            'total' => 5000,
            'status' => 'cancelled',
        ]);
        OrderItem::create([
            'order_id' => $cancelledOrder->id,
            'product_id' => $prod->id,
            'product_size_id' => $size->id,
            'quantity' => 50,
            'unit_price' => 100,
            'subtotal' => 5000,
        ]);

        // Completed order with 2 units
        $completedOrder = Order::create([
            'order_number' => 'ORD-COMPLETED',
            'cashier_name' => 'Cashier',
            'subtotal' => 200,
            'discount' => 0,
            'total' => 200,
            'status' => 'completed',
        ]);
        OrderItem::create([
            'order_id' => $completedOrder->id,
            'product_id' => $prod->id,
            'product_size_id' => $size->id,
            'quantity' => 2,
            'unit_price' => 100,
            'subtotal' => 200,
        ]);
        OrderItem::create([
            'order_id' => $completedOrder->id,
            'product_id' => $prod->id,
            'product_size_id' => $size->id,
            'quantity' => 3,
            'unit_price' => 100,
            'subtotal' => 300,
            'status' => 'voided',
        ]);

        $response = $this->actingAs($owner)->get(route('dashboard'));
        $response->assertOk();

        $topProducts = $response->viewData('topProducts');
        $this->assertCount(1, $topProducts);
        $this->assertEquals(2, $topProducts->first()['sold']);
    }

    public function test_ingredient_controller_stock_status_filter(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $ing1 = Ingredient::create(['name' => 'Ingredient In Stock', 'unit' => 'g', 'minimum_stock' => 10, 'status' => 'active']);
        Inventory::create(['ingredient_id' => $ing1->id, 'current_stock' => 100]);

        $ing2 = Ingredient::create(['name' => 'Ingredient Out Of Stock', 'unit' => 'g', 'minimum_stock' => 10, 'status' => 'active']);
        Inventory::create(['ingredient_id' => $ing2->id, 'current_stock' => 0]);

        Ingredient::create(['name' => 'Ingredient Missing Inventory', 'unit' => 'g', 'minimum_stock' => 10, 'status' => 'active']);

        $response = $this->actingAs($manager)->get(route('ingredients.index', ['stock_status' => 'out_of_stock']));
        $response->assertOk();
        $ingredients = $response->viewData('ingredients');
        $this->assertSame(2, $ingredients->total());
        $this->assertEqualsCanonicalizing(
            ['Ingredient Out Of Stock', 'Ingredient Missing Inventory'],
            $ingredients->getCollection()->pluck('name')->all()
        );
    }

    public function test_ingredient_delete_preserves_stock_and_referenced_inventory_history(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $stockedIngredient = Ingredient::create([
            'name' => 'Ingredient With Remaining Stock',
            'unit' => 'g',
            'minimum_stock' => 1,
            'status' => 'active',
        ]);
        Inventory::create(['ingredient_id' => $stockedIngredient->id, 'current_stock' => 2]);

        $historyIngredient = Ingredient::create([
            'name' => 'Ingredient With History',
            'unit' => 'g',
            'minimum_stock' => 1,
            'status' => 'active',
        ]);
        Inventory::create(['ingredient_id' => $historyIngredient->id, 'current_stock' => 0]);
        $transaction = InventoryTransaction::create([
            'ingredient_id' => $historyIngredient->id,
            'type' => 'waste',
            'quantity' => 1,
            'previous_stock' => 1,
            'new_stock' => 0,
            'performed_by' => $manager->name,
            'performed_role' => 'manager',
        ]);

        $addonIngredient = Ingredient::create([
            'name' => 'Ingredient Used By Addon',
            'unit' => 'g',
            'minimum_stock' => 1,
            'status' => 'active',
        ]);
        Inventory::create(['ingredient_id' => $addonIngredient->id, 'current_stock' => 0]);
        $addon = ProductAddon::create(['name' => 'Extra Test Shot', 'price' => 10, 'status' => 'active']);
        AddonIngredient::create([
            'product_addon_id' => $addon->id,
            'ingredient_id' => $addonIngredient->id,
            'quantity' => 1,
        ]);

        foreach ([$stockedIngredient, $historyIngredient, $addonIngredient] as $ingredient) {
            $this->actingAs($manager)
                ->from(route('ingredients.index'))
                ->delete(route('ingredients.destroy', $ingredient))
                ->assertRedirect(route('ingredients.index'))
                ->assertSessionHas('error', 'Cannot delete an ingredient with recipe usage, stock, or inventory history. Archive it instead.');
        }

        $this->assertDatabaseHas('ingredients', ['id' => $stockedIngredient->id]);
        $this->assertDatabaseHas('inventory', ['ingredient_id' => $stockedIngredient->id, 'current_stock' => 2]);
        $this->assertDatabaseHas('ingredients', ['id' => $historyIngredient->id]);
        $this->assertDatabaseHas('inventory_transactions', ['id' => $transaction->id]);
        $this->assertDatabaseHas('ingredients', ['id' => $addonIngredient->id]);
        $this->assertDatabaseHas('product_addon_ingredients', [
            'product_addon_id' => $addon->id,
            'ingredient_id' => $addonIngredient->id,
        ]);
    }
}
