<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_view_their_own_order(): void
    {
        $user = User::factory()->create(['role' => 'cashier', 'name' => 'Jane Cashier']);
        $this->startShiftFor($user);
        $order = Order::create([
            'order_number' => 'ORD-OWN-001',
            'cashier_name' => 'Jane Cashier',
            'subtotal' => 100,
            'discount' => 0,
            'total' => 100,
            'status' => 'completed',
        ]);

        $this->actingAs($user)->get(route('orders.show', $order))->assertOk();
    }

    public function test_cashier_can_view_order_actions_that_require_manager_authorization(): void
    {
        $user = User::factory()->create(['role' => 'cashier', 'name' => 'Jane Cashier']);
        $this->startShiftFor($user);
        $order = Order::create([
            'order_number' => 'ORD-OWN-ACTIONS',
            'cashier_name' => 'Jane Cashier',
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

        $response = $this->actingAs($user)
            ->get(route('orders.show', $order));
        $response->assertOk()
            ->assertSee('Order Actions')
            ->assertSee('Process Refund')
            ->assertDontSee('Cancel Unpaid Order')
            ->assertSee('Void Full Order')
            ->assertSee('Enter an active Manager or Owner email and password to authorize refunds, cancellations, and voids.');

        $page = $response->getContent();
        $callbackCapture = strpos($page, 'const callback = _authCallback;');
        $modalClose = strpos($page, 'closeAuthModal();', $callbackCapture ?: 0);
        $callbackInvoke = strpos($page, 'if (callback) callback({ authorizer_email: email, authorizer_password: password, reason });', $modalClose ?: 0);

        $this->assertNotFalse($callbackCapture);
        $this->assertNotFalse($modalClose);
        $this->assertNotFalse($callbackInvoke);
        $this->assertLessThan($modalClose, $callbackCapture);
        $this->assertLessThan($callbackInvoke, $modalClose);
    }

    public function test_refund_action_is_not_offered_for_completed_order_without_paid_cash_payment(): void
    {
        $user = User::factory()->create(['role' => 'cashier', 'name' => 'Jane Cashier']);
        $this->startShiftFor($user);
        $order = Order::create([
            'order_number' => 'ORD-OWN-NO-PAYMENT',
            'cashier_name' => 'Jane Cashier',
            'subtotal' => 100,
            'discount' => 0,
            'total' => 100,
            'status' => 'completed',
        ]);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Order Actions')
            ->assertSee('Refund Unavailable')
            ->assertDontSee('Cancel Unpaid Order')
            ->assertSee('Void Full Order');
    }

    public function test_paid_order_cannot_be_cancelled_without_voiding_or_refunding_its_payment(): void
    {
        $manager = User::factory()->create(['role' => 'manager', 'status' => 'active']);
        $order = Order::create([
            'order_number' => 'ORD-CANCEL-PAID',
            'cashier_name' => 'Jane Cashier',
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

        $this->actingAs($manager)->post(route('refunds.cancel', $order), [
            'authorizer_email' => $manager->email,
            'authorizer_password' => 'password',
            'reason' => 'Attempt to cancel completed paid order',
        ])->assertSessionHasErrors('order');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'paid']);
        $this->assertDatabaseMissing('order_adjustments', ['order_id' => $order->id, 'action' => 'cancel']);
    }

    public function test_unpaid_pending_order_can_be_cancelled_with_authorization(): void
    {
        $manager = User::factory()->create(['role' => 'manager', 'status' => 'active']);
        $order = Order::create([
            'order_number' => 'ORD-CANCEL-PENDING',
            'cashier_name' => 'Jane Cashier',
            'subtotal' => 100,
            'discount' => 0,
            'total' => 100,
            'status' => 'pending',
        ]);

        $this->actingAs($manager)->post(route('refunds.cancel', $order), [
            'authorizer_email' => $manager->email,
            'authorizer_password' => 'password',
            'reason' => 'Customer cancelled before payment',
        ])->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('order_adjustments', ['order_id' => $order->id, 'action' => 'cancel']);
    }

    public function test_cashier_can_view_another_cashiers_order_in_the_same_branch(): void
    {
        $user = User::factory()->create(['role' => 'cashier', 'name' => 'Jane Cashier']);
        $this->startShiftFor($user);
        $order = Order::create([
            'order_number' => 'ORD-OTHER-001',
            'cashier_name' => 'John Cashier',
            'subtotal' => 100,
            'discount' => 0,
            'total' => 100,
            'status' => 'completed',
        ]);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Order Actions');
        $this->get(route('pos.success', $order))
            ->assertOk()
            ->assertSee($order->order_number);
    }

    public function test_cashier_cannot_view_an_order_from_another_branch(): void
    {
        $branch = Branch::query()->where('is_active', true)->orderBy('id')->firstOrFail();
        $otherBranch = Branch::create([
            'name' => 'Other Order Branch',
            'code' => 'ORDER-OTHER',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'role' => 'cashier',
            'branch_id' => $branch->id,
            'name' => 'Jane Cashier',
        ]);
        $order = Order::create([
            'branch_id' => $otherBranch->id,
            'order_number' => 'ORD-OTHER-BRANCH-001',
            'cashier_name' => 'John Cashier',
            'subtotal' => 100,
            'discount' => 0,
            'total' => 100,
            'status' => 'completed',
        ]);

        $this->assertFalse($user->can('view', $order));
        $this->assertFalse($user->can('recordPayment', $order));
        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertNotFound();
        $this->get(route('orders.receipt', $order))->assertNotFound();
        $this->get(route('pos.success', $order))->assertNotFound();
    }

    public function test_cashier_without_an_assigned_branch_cannot_view_branch_orders(): void
    {
        $branch = Branch::query()->where('is_active', true)->orderBy('id')->firstOrFail();
        $user = User::factory()->create([
            'role' => 'cashier',
            'name' => 'Unassigned Cashier',
        ]);
        $user->forceFill(['branch_id' => null])->saveQuietly();
        $order = Order::create([
            'branch_id' => $branch->id,
            'order_number' => 'ORD-UNASSIGNED-CASHIER-001',
            'cashier_name' => 'Other Cashier',
            'subtotal' => 100,
            'discount' => 0,
            'total' => 100,
            'status' => 'completed',
        ]);

        $this->assertFalse($user->can('view', $order));
        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertNotFound();
    }

    public function test_cashier_can_record_payment_for_another_cashiers_order_in_the_same_branch(): void
    {
        $user = User::factory()->create(['role' => 'cashier', 'name' => 'Jane Cashier']);
        $this->startShiftFor($user);
        $order = Order::create([
            'order_number' => 'ORD-OTHER-PAYMENT',
            'cashier_name' => 'John Cashier',
            'subtotal' => 100,
            'discount' => 0,
            'total' => 100,
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->postJson(route('orders.payments.store', $order), [
                'amount_paid' => 100,
                'payment_method' => 'cash',
                'amount_received' => 100,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('remaining_balance', 0);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount_paid' => 100,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);
    }

    public function test_cashier_cannot_void_order_without_manager_or_owner_credentials(): void
    {
        $user = User::factory()->create(['role' => 'cashier', 'name' => 'Jane Cashier']);
        $this->startShiftFor($user);
        $order = Order::create([
            'order_number' => 'ORD-VOID-NO-AUTH',
            'cashier_name' => 'John Cashier',
            'subtotal' => 100,
            'discount' => 0,
            'total' => 100,
            'status' => 'completed',
        ]);

        $this->actingAs($user)
            ->post(route('orders.void', $order), ['reason' => 'Customer requested void'])
            ->assertSessionHasErrors('authorizer_email');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
    }

    public function test_cashier_cannot_refund_order_without_manager_or_owner_credentials(): void
    {
        $user = User::factory()->create(['role' => 'cashier', 'name' => 'Jane Cashier']);
        $this->startShiftFor($user);
        $order = Order::create([
            'order_number' => 'ORD-REFUND-NO-AUTH',
            'cashier_name' => 'John Cashier',
            'subtotal' => 100,
            'discount' => 0,
            'total' => 100,
            'status' => 'completed',
        ]);

        $this->actingAs($user)
            ->post(route('refunds.refund', $order), [
                'reason' => 'Customer requested a refund',
                'restore_stock' => 1,
            ])
            ->assertSessionHasErrors(['authorizer_email', 'authorizer_password']);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
    }

    public function test_cashier_refund_returns_to_order_after_manager_authorizes(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier', 'name' => 'Jane Cashier']);
        $this->startShiftFor($cashier);
        $manager = User::factory()->create(['role' => 'manager']);
        $order = Order::create([
            'order_number' => 'ORD-REFUND-AUTH',
            'cashier_name' => 'John Cashier',
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

        $this->actingAs($cashier)
            ->post(route('refunds.refund', $order), [
                'authorizer_email' => $manager->email,
                'authorizer_password' => 'password',
                'reason' => 'Customer requested a refund',
                'restore_stock' => 0,
            ])
            ->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'refunded']);
    }

    public function test_manager_can_view_any_order(): void
    {
        $user = User::factory()->create(['role' => 'manager']);
        $this->startShiftFor($user);
        $order = Order::create([
            'order_number' => 'ORD-MANAGER-001',
            'cashier_name' => 'John Cashier',
            'subtotal' => 100,
            'discount' => 0,
            'total' => 100,
            'status' => 'completed',
        ]);

        $this->actingAs($user)->get(route('orders.show', $order))->assertOk();
    }

    private function startShiftFor(User $user): void
    {
        $this->actingAs($user)
            ->postJson(route('shifts.start'), ['beginning_cash' => 500])
            ->assertOk();
    }
}
