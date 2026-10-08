<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\Order;
use App\Models\OrderAdjustment;
use App\Models\Refund;
use App\Models\User;
use App\Services\AuditService;
use App\Support\BusinessDateRange;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RefundController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $query = Refund::with(['order', 'authorizedUser'])
            ->orderByDesc(DB::raw('COALESCE(refunded_at, created_at)'));

        if ($from = $filters['from'] ?? null) {
            $query->whereRaw('COALESCE(refunded_at, created_at) >= ?', [BusinessDateRange::startUtc($from)]);
        }
        if ($to = $filters['to'] ?? null) {
            $query->whereRaw('COALESCE(refunded_at, created_at) < ?', [BusinessDateRange::endExclusiveUtc($to)]);
        }

        $refunds = $query->paginate(20)->withQueryString();

        // Orders eligible for refund (completed cash orders only)
        $eligibleOrders = Order::where('status', 'completed')
            ->whereDoesntHave('payments', fn ($q) => $q->where('method', '!=', 'cash'))
            ->whereHas('payments', fn ($q) => $q->where('method', 'cash')->where('status', 'paid'))
            ->with('payments')
            ->latest()
            ->limit(50)
            ->get();

        return view('refunds.index', compact('refunds', 'eligibleOrders'));
    }

    public function refund(Request $request, Order $order)
    {
        $shift = CashierShift::activeForUser($request->user()->id);
        if (! $shift) {
            return back()->with('error', 'Start a cashier shift before issuing a cash refund.');
        }

        $request->validate([
            'authorizer_email' => 'required|email',
            'authorizer_password' => 'required|string',
            'reason' => 'required|string|min:10|max:500',
            'restore_stock' => 'boolean',
        ]);

        // Verify authorizer credentials
        $authorizer = User::where('email', $request->authorizer_email)
            ->where('status', 'active')
            ->first();

        if (! $authorizer || ! Hash::check($request->authorizer_password, $authorizer->password)) {
            return back()->with('error', 'Authorization failed. Invalid credentials.')->withInput();
        }

        if (! $authorizer->canAuthorize()) {
            return back()->with('error', 'Authorization failed. Authorizer does not have permission.')->withInput();
        }

        if ($order->status !== 'completed') {
            return back()->with('error', 'Only completed orders can be refunded.');
        }

        if ($order->payments()->where('method', '!=', 'cash')->exists()) {
            return back()->with('error', 'Only cash orders can be refunded. Online payments are non-refundable.');
        }

        $refundAmount = DB::transaction(function () use ($request, $order, $authorizer) {
            $shift = CashierShift::lockActiveForUser($request->user()->id);
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->status !== 'completed') {
                return 0.0;
            }

            $alreadyRefunded = (float) $lockedOrder->refunds()->sum('amount');
            $refundAmount = max(0, min(
                (float) $lockedOrder->total,
                (float) $lockedOrder->payments()->whereIn('status', ['paid', 'refunded'])->sum('amount_paid') - $alreadyRefunded
            ));
            if ($refundAmount <= 0) {
                return 0.0;
            }

            $refund = Refund::create([
                'order_id' => $lockedOrder->id,
                'shift_id' => $shift->id,
                'amount' => $refundAmount,
                'method' => 'cash',
                'status' => 'completed',
                'reason' => $request->reason,
                'authorized_by' => $authorizer->name,
                'authorized_role' => $authorizer->role,
                'authorized_user_id' => $authorizer->id,
                'stock_restored' => (bool) $request->restore_stock,
                'refunded_at' => now(),
            ]);

            $lockedOrder->update(['status' => 'refunded']);
            $lockedOrder->payments()->where('status', 'paid')->update(['status' => 'refunded']);

            // Optionally restore stock
            if ($request->restore_stock) {
                $lockedOrder->load('orderItems');
                InventoryService::restoreFromRefund($lockedOrder, $authorizer->name, $authorizer->role);
            }

            AuditService::log(
                action: 'refund_processed',
                module: 'Refunds',
                actorName: $authorizer->name,
                actorRole: $authorizer->role,
                details: [
                    'order_number' => $lockedOrder->order_number,
                    'cashier_name' => $lockedOrder->cashier_name,
                    'amount' => $refundAmount,
                    'reason' => $request->reason,
                    'stock_restored' => (bool) $request->restore_stock,
                ],
                reference: $refund,
                actorUserId: $authorizer->id,
            );

            return $refundAmount;
        }, 3);

        if ($refundAmount <= 0) {
            return back()->with('error', 'This order has no remaining paid amount to refund.');
        }

        $redirect = $request->user()?->isCashier()
            ? redirect()->route('orders.show', $order)
            : redirect()->route('refunds.index');

        return $redirect
            ->with('success', "Order #{$order->order_number} has been refunded.");
    }

    public function cancel(Request $request, Order $order)
    {
        $request->validate([
            'authorizer_email' => 'required|email',
            'authorizer_password' => 'required|string',
            'reason' => 'required|string|min:10|max:500',
        ]);

        $authorizer = User::where('email', $request->authorizer_email)
            ->where('status', 'active')
            ->first();

        if (! $authorizer || ! Hash::check($request->authorizer_password, $authorizer->password)) {
            return back()->with('error', 'Authorization failed. Invalid credentials.')->withInput();
        }

        if (! $authorizer->canAuthorize()) {
            return back()->with('error', 'Authorizer does not have permission.')->withInput();
        }

        DB::transaction(function () use ($request, $order, $authorizer) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->status !== 'pending' || $lockedOrder->payments()->where('status', 'paid')->exists()) {
                throw ValidationException::withMessages([
                    'order' => 'Only unpaid pending orders can be cancelled. Use the void or refund action for paid orders.',
                ]);
            }

            // Store before-state snapshot
            $beforeState = $lockedOrder->toArray();

            OrderAdjustment::create([
                'order_id' => $lockedOrder->id,
                'action' => 'cancel',
                'reason' => $request->reason,
                'authorized_by' => $authorizer->name,
                'authorized_role' => $authorizer->role,
                'authorized_user_id' => $authorizer->id,
                'before_state' => $beforeState,
            ]);

            $lockedOrder->update(['status' => 'cancelled']);

            AuditService::log(
                action: 'order_cancelled',
                module: 'Orders',
                actorName: $authorizer->name,
                actorRole: $authorizer->role,
                details: [
                    'order_number' => $lockedOrder->order_number,
                    'reason' => $request->reason,
                ],
                reference: $lockedOrder,
                actorUserId: $authorizer->id,
            );
        }, 3);

        return redirect()->route('orders.show', $order)
            ->with('success', "Order #{$order->order_number} has been cancelled.");
    }
}
