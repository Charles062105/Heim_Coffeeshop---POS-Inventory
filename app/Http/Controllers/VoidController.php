<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Refund;
use App\Models\User;
use App\Models\VoidLog;
use App\Services\AuditService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class VoidController extends Controller
{
    public function index(Request $request)
    {
        $query = VoidLog::with(['order', 'orderItem.product', 'orderItem.size', 'authorizedUser'])
            ->latest('voided_at');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('cashier_name', 'like', "%{$search}%")
                    ->orWhere('authorized_by', 'like', "%{$search}%")
                    ->orWhereHas('order', fn ($oq) => $oq->where('order_number', 'like', "%{$search}%"));
            });
        }

        if ($voidType = $request->get('void_type')) {
            $query->where('void_type', $voidType);
        }

        if ($from = $request->get('from')) {
            $query->whereDate('voided_at', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->whereDate('voided_at', '<=', $to);
        }

        $voidLogs = $query->paginate(20)->withQueryString();

        return view('voids.index', compact('voidLogs'));
    }

    public function voidOrder(Request $request, Order $order)
    {
        $shift = CashierShift::activeForUser($request->user()->id);
        if (! $shift) {
            return $request->wantsJson()
                ? response()->json(['message' => 'Start a cashier shift before voiding an order.'], 422)
                : back()->with('error', 'Start a cashier shift before voiding an order.');
        }

        $request->validate([
            'reason' => 'required|string|min:5|max:500',
            'authorizer_email' => 'nullable|email',
            'authorizer_password' => 'nullable|string',
        ]);

        $authorizer = $this->verifyAuthorizer($request);

        if (in_array($order->status, ['voided', 'refunded'])) {
            if ($request->wantsJson()) {
                return response()->json(['error' => "Order cannot be voided because it is already {$order->status}."], 422);
            }

            return back()->with('error', "Order cannot be voided because it is already {$order->status}.");
        }

        $inventoryDeducted = in_array($order->status, ['completed', 'partially_paid', 'pay_later']);
        $requester = $request->user();

        DB::transaction(function () use ($order, $authorizer, $request, $inventoryDeducted, $requester) {
            $shift = CashierShift::lockActiveForUser($requester->id);
            $cashPaid = (float) $order->payments()
                ->where('method', 'cash')
                ->whereIn('status', ['paid', 'refunded'])
                ->sum('amount_paid');
            if ($cashPaid > 0) {
                Refund::create([
                    'order_id' => $order->id,
                    'shift_id' => $shift->id,
                    'amount' => $cashPaid,
                    'method' => 'cash',
                    'status' => 'completed',
                    'reason' => 'Order void: '.$request->reason,
                    'authorized_by' => $authorizer->name,
                    'authorized_role' => $authorizer->role,
                    'authorized_user_id' => $authorizer->id,
                    'stock_restored' => $inventoryDeducted,
                    'refunded_at' => now(),
                ]);
            }
            if ($inventoryDeducted) {
                $order->load(['orderItems.product', 'orderItems.size', 'orderItems.addons.addon']);
                InventoryService::restoreFromVoid($order, $authorizer->name, $authorizer->role);
            }
            $order->payments()->where('status', 'paid')->update(['status' => 'voided']);

            // Mark order and all active items as voided
            $order->update(['status' => 'voided']);
            $order->orderItems()->where('status', 'active')->update(['status' => 'voided']);

            $voidLog = VoidLog::create([
                'order_id' => $order->id,
                'shift_id' => $shift->id,
                'order_item_id' => null,
                'amount' => (float) $order->total,
                'void_type' => 'order',
                'reason' => $request->reason,
                'cashier_name' => $order->cashier_name,
                'requested_by_user_id' => $requester?->id,
                'requested_by' => $requester?->name ?? $authorizer->name,
                'requested_role' => $requester?->role ?? $authorizer->role,
                'authorized_user_id' => $authorizer->id,
                'authorized_by' => $authorizer->name,
                'authorized_role' => $authorizer->role,
                'stock_restored' => $inventoryDeducted,
                'voided_at' => now(),
            ]);

            AuditService::log(
                action: 'order_voided',
                module: 'Voids',
                actorName: $requester?->name ?? $authorizer->name,
                actorRole: $requester?->role ?? $authorizer->role,
                details: [
                    'order_number' => $order->order_number,
                    'cashier_name' => $order->cashier_name,
                    'reason' => $request->reason,
                    'requested_by' => $requester?->name ?? $authorizer->name,
                    'approved_by' => $authorizer->name,
                    'stock_restored' => $inventoryDeducted,
                    'total' => $order->total,
                ],
                reference: $voidLog,
                actorUserId: $requester?->id ?? $authorizer->id,
            );
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Order #{$order->order_number} has been voided.",
            ]);
        }

        return redirect()->route('orders.show', $order)->with('success', "Order #{$order->order_number} has been voided.");
    }

    public function voidItem(Request $request, Order $order, OrderItem $item)
    {
        $shift = CashierShift::activeForUser($request->user()->id);
        if (! $shift) {
            return $request->wantsJson()
                ? response()->json(['message' => 'Start a cashier shift before voiding an item.'], 422)
                : back()->with('error', 'Start a cashier shift before voiding an item.');
        }

        $request->validate([
            'reason' => 'required|string|min:5|max:500',
            'authorizer_email' => 'nullable|email',
            'authorizer_password' => 'nullable|string',
        ]);

        if ($item->order_id !== $order->id) {
            abort(404, 'Item does not belong to this order.');
        }

        $authorizer = $this->verifyAuthorizer($request);
        $requester = $request->user();

        $result = DB::transaction(function () use ($order, $item, $authorizer, $request, $requester) {
            $shift = CashierShift::lockActiveForUser($requester->id);
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $lockedItem = OrderItem::query()
                ->where('order_id', $lockedOrder->id)
                ->lockForUpdate()
                ->findOrFail($item->id);

            if ($lockedItem->status === 'voided') {
                throw ValidationException::withMessages(['item' => 'This item is already voided.']);
            }

            if (in_array($lockedOrder->status, ['voided', 'refunded', 'cancelled'], true)) {
                throw ValidationException::withMessages(['order' => 'Items cannot be voided in the current order status.']);
            }

            $beforeState = [
                'subtotal' => (float) $lockedOrder->subtotal,
                'discount' => (float) $lockedOrder->discount,
                'tax_amount' => (float) $lockedOrder->tax_amount,
                'total' => (float) $lockedOrder->total,
                'item_payable_total' => (float) $lockedItem->payable_total,
            ];
            $inventoryDeducted = in_array($lockedOrder->status, ['completed', 'partially_paid', 'pay_later'], true);

            if ($inventoryDeducted) {
                $lockedItem->load(['product', 'size', 'addons.addon']);
                InventoryService::restoreFromVoid($lockedOrder, $authorizer->name, $authorizer->role, $lockedItem);
            }

            $oldSubtotal = (float) $lockedOrder->subtotal;
            $oldTotal = (float) $lockedOrder->total;
            $itemSubtotal = (float) $lockedItem->subtotal;
            $itemTotal = (float) $lockedItem->payable_total;
            if ($itemTotal <= 0 && $oldSubtotal > 0) {
                $itemTotal = round($oldTotal * $itemSubtotal / $oldSubtotal, 2);
            }

            $lockedItem->update(['status' => 'voided']);
            $activeItems = $lockedOrder->orderItems()->where('status', 'active')->get();
            $newSubtotal = round((float) $activeItems->sum('subtotal'), 2);
            $newTotal = round((float) $activeItems->sum('payable_total'), 2);
            if ($activeItems->isEmpty()) {
                $newSubtotal = 0;
                $newTotal = 0;
            }

            $subtotalRatio = $oldSubtotal > 0 ? min(1, $itemSubtotal / $oldSubtotal) : 1;
            $totalRatio = $oldTotal > 0 ? min(1, $itemTotal / $oldTotal) : 1;
            $lockedOrder->update([
                'subtotal' => $newSubtotal,
                'discount' => max(0, round((float) $lockedOrder->discount * (1 - $subtotalRatio), 2)),
                'tax_amount' => max(0, round((float) $lockedOrder->tax_amount * (1 - $totalRatio), 2)),
                'vatable_sales' => max(0, round((float) $lockedOrder->vatable_sales * (1 - $totalRatio), 2)),
                'vat_exempt_sales' => max(0, round((float) $lockedOrder->vat_exempt_sales * (1 - $totalRatio), 2)),
                'zero_rated_sales' => max(0, round((float) $lockedOrder->zero_rated_sales * (1 - $totalRatio), 2)),
                'total' => $newTotal,
            ]);

            $voidLog = VoidLog::create([
                'order_id' => $lockedOrder->id,
                'shift_id' => $shift->id,
                'order_item_id' => $lockedItem->id,
                'amount' => $itemTotal,
                'void_type' => 'item',
                'reason' => $request->reason,
                'cashier_name' => $lockedOrder->cashier_name,
                'requested_by_user_id' => $requester?->id,
                'requested_by' => $requester?->name ?? $authorizer->name,
                'requested_role' => $requester?->role ?? $authorizer->role,
                'authorized_user_id' => $authorizer->id,
                'authorized_by' => $authorizer->name,
                'authorized_role' => $authorizer->role,
                'stock_restored' => $inventoryDeducted,
                'voided_at' => now(),
            ]);

            $refunds = $this->recordItemVoidOverpayment(
                $lockedOrder,
                $lockedItem,
                $authorizer,
                $request->reason,
                $shift->id
            );

            $debt = $lockedOrder->debt;
            if ($debt) {
                $debt->update(['original_amount' => $newTotal]);
                $debt->recalculate();
            }

            if ($activeItems->isEmpty()) {
                $lockedOrder->update(['status' => 'voided']);
            } elseif ($debt) {
                $lockedOrder->update(['status' => 'pay_later']);
            } else {
                $lockedOrder->update([
                    'status' => $lockedOrder->paidAmount() >= $newTotal ? 'completed' : 'partially_paid',
                ]);
            }

            AuditService::log(
                action: 'order_item_voided',
                module: 'Voids',
                actorName: $requester?->name ?? $authorizer->name,
                actorRole: $requester?->role ?? $authorizer->role,
                details: [
                    'order_number' => $lockedOrder->order_number,
                    'item_id' => $lockedItem->id,
                    'product' => $lockedItem->product?->name ?? 'Unknown',
                    'reason' => $request->reason,
                    'requested_by' => $requester?->name ?? $authorizer->name,
                    'approved_by' => $authorizer->name,
                    'stock_restored' => $inventoryDeducted,
                    'before' => $beforeState,
                    'after' => [
                        'subtotal' => $newSubtotal,
                        'total' => $newTotal,
                    ],
                    'refunds' => $refunds,
                ],
                reference: $voidLog,
                actorUserId: $requester?->id ?? $authorizer->id,
            );

            return ['order_number' => $lockedOrder->order_number, 'refunds' => $refunds];
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Item voided successfully.',
                'refunds' => $result['refunds'],
            ]);
        }

        $message = "Item has been voided from Order #{$result['order_number']}.";
        if ($result['refunds'] !== []) {
            $message .= ' Any excess payment has been recorded for refund processing.';
        }

        return redirect()->route('orders.show', $order)->with('success', $message);
    }

    private function recordItemVoidOverpayment(
        Order $order,
        OrderItem $item,
        User $authorizer,
        string $reason,
        int $shiftId
    ): array {
        $debt = $order->debt;
        if ($debt) {
            $paid = (float) $debt->payments()->sum('amount');
            $alreadyRefunded = (float) $order->refunds()->sum('amount');
            $refundAmount = max(0, round($paid - (float) $order->total - $alreadyRefunded, 2));
            $availablePayments = $debt->payments()->orderByDesc('id')->get();
            $foreignKey = 'debt_payment_id';
        } else {
            $payments = $order->payments()->where('status', 'paid')->orderByDesc('id')->get();
            $payer = trim((string) $item->assigned_to);
            $payerPayments = $payer !== ''
                ? $payments->where('customer_name', $payer)->values()
                : collect();

            if ($payerPayments->isNotEmpty()) {
                $paid = (float) $payerPayments->sum('amount_paid');
                $payerDue = (float) $order->orderItems()
                    ->where('status', 'active')
                    ->where('assigned_to', $payer)
                    ->sum('payable_total');
                $payerPaymentIds = $payerPayments->pluck('id');
                $alreadyRefunded = (float) $order->refunds()
                    ->whereIn('payment_id', $payerPaymentIds)
                    ->sum('amount');
                $refundAmount = max(0, round($paid - $payerDue - $alreadyRefunded, 2));
                $availablePayments = $payerPayments;
            } else {
                $paid = (float) $payments->sum('amount_paid');
                $alreadyRefunded = (float) $order->refunds()->sum('amount');
                $refundAmount = max(0, round($paid - (float) $order->total - $alreadyRefunded, 2));
                $availablePayments = $payments;
            }
            $foreignKey = 'payment_id';
        }

        if ($refundAmount <= 0) {
            return [];
        }

        $refunds = [];
        foreach ($availablePayments as $payment) {
            $linkedRefunds = (float) Refund::where($foreignKey, $payment->id)->sum('amount');
            $availableAmount = max(0, round((float) ($payment->amount_paid ?? $payment->amount) - $linkedRefunds, 2));
            $amount = min($refundAmount, $availableAmount);
            if ($amount <= 0) {
                continue;
            }

            $method = ($payment->method ?? $payment->payment_method) === 'cash' ? 'cash' : 'online';
            $status = $method === 'cash' ? 'completed' : 'processing';
            $refund = Refund::create([
                'order_id' => $order->id,
                'shift_id' => $shiftId,
                $foreignKey => $payment->id,
                'amount' => $amount,
                'method' => $method,
                'status' => $status,
                'reason' => "Item void: {$reason}",
                'authorized_by' => $authorizer->name,
                'authorized_role' => $authorizer->role,
                'authorized_user_id' => $authorizer->id,
                'stock_restored' => true,
                'refunded_at' => $method === 'cash' ? now() : null,
            ]);

            $refunds[] = [
                'refund_id' => $refund->id,
                'payment_method' => $method,
                'status' => $status,
                'amount' => $amount,
            ];
            $refundAmount = round($refundAmount - $amount, 2);
            if ($refundAmount <= 0) {
                break;
            }
        }

        if ($refundAmount > 0) {
            throw new \RuntimeException("Unable to allocate the full overpayment refund for order {$order->order_number}.");
        }

        return $refunds;
    }

    private function verifyAuthorizer(Request $request): User
    {
        $user = $request->user();

        // If the authenticated user can already authorize, we can accept them
        if ($user && $user->canAuthorize() && ! $request->filled('authorizer_email')) {
            return $user;
        }

        if (! $request->filled('authorizer_email') || ! $request->filled('authorizer_password')) {
            throw ValidationException::withMessages([
                'authorizer_email' => 'Authorizer email and password are required.',
            ]);
        }

        $authorizer = User::where('email', $request->authorizer_email)
            ->where('status', 'active')
            ->first();

        if (! $authorizer || ! Hash::check($request->authorizer_password, $authorizer->password)) {
            throw ValidationException::withMessages([
                'authorizer_email' => 'Authorization failed. Invalid credentials.',
            ]);
        }

        if (! $authorizer->canAuthorize()) {
            throw ValidationException::withMessages([
                'authorizer_email' => 'Authorization failed. Authorizer must be a manager or owner.',
            ]);
        }

        return $authorizer;
    }
}
