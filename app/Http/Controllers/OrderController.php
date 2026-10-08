<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\Order;
use App\Models\Payment;
use App\Services\AuditService;
use App\Support\BusinessDateRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $query = Order::with(['payments', 'orderItems'])
            ->latest();

        // Search by order number or cashier name
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('cashier_name', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Filter by date
        if ($date = $filters['date'] ?? null) {
            $query->where('created_at', '>=', BusinessDateRange::startUtc($date))
                ->where('created_at', '<', BusinessDateRange::endExclusiveUtc($date));
        }

        // Date range
        if ($from = $filters['from'] ?? null) {
            $query->where('created_at', '>=', BusinessDateRange::startUtc($from));
        }
        if ($to = $filters['to'] ?? null) {
            $query->where('created_at', '<', BusinessDateRange::endExclusiveUtc($to));
        }

        $orders = $query->paginate(20)->withQueryString();
        $statuses = ['completed', 'partially_paid', 'pending', 'cancelled', 'refunded', 'voided'];

        return view('orders.index', compact('orders', 'statuses'));
    }

    public function show(Order $order)
    {
        Gate::authorize('view', $order);

        $order->load(['orderItems.product', 'orderItems.size', 'orderItems.addons.addon', 'payments', 'refunds', 'refund']);

        $paidByPerson = $order->payments
            ->where('status', 'paid')
            ->filter(fn ($payment) => filled($payment->customer_name))
            ->groupBy(fn ($payment) => trim($payment->customer_name))
            ->map(fn ($payments) => round((float) $payments->sum('amount_paid'), 2));

        $splitPayments = $order->orderItems
            ->where('status', 'active')
            ->filter(fn ($item) => filled($item->assigned_to))
            ->groupBy(fn ($item) => trim($item->assigned_to))
            ->map(function ($items, $person) use ($paidByPerson) {
                $due = round((float) $items->sum('payable_total'), 2);
                $paid = (float) $paidByPerson->get($person, 0);
                $remaining = max(0, round($due - $paid, 2));

                return [
                    'person' => $person,
                    'items' => (int) $items->sum('quantity'),
                    'due' => $due,
                    'paid' => $paid,
                    'remaining' => $remaining,
                    'status' => $remaining <= 0 ? 'paid' : ($paid > 0 ? 'partially_paid' : 'pending'),
                ];
            })
            ->values();

        return view('orders.show', compact('order', 'splitPayments'));
    }

    public function receipt(Order $order)
    {
        Gate::authorize('view', $order);

        $order->load(['orderItems.product', 'orderItems.size', 'orderItems.addons.addon', 'payments']);

        return view('orders.receipt', compact('order'));
    }

    public function recordPayment(Request $request, Order $order)
    {
        Gate::authorize('recordPayment', $order);

        $shift = CashierShift::activeForUser($request->user()->id);
        if (! $shift) {
            throw ValidationException::withMessages([
                'shift' => 'Start a cashier shift before recording a payment.',
            ]);
        }

        $validated = $request->validate([
            'amount_paid' => 'required|numeric|decimal:0,2|min:0.01',
            'amount_received' => 'required_if:payment_method,cash|numeric|decimal:0,2|min:0',
            'payment_method' => 'required|in:cash,online,grabfood,grab',
            'payment_status' => 'nullable|in:paid,pending,failed',
            'person_name' => 'nullable|string|max:150',
            'payment_comment' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:100',
        ]);

        if (($order->order_type ?? 'dine_in') !== 'grab' && in_array($validated['payment_method'], ['grabfood', 'grab'], true)) {
            throw ValidationException::withMessages([
                'payment_method' => 'GrabFood platform settlements are only valid for Grab orders.',
            ]);
        }

        if ($validated['payment_method'] === 'online' && blank($validated['reference_number'] ?? null)) {
            throw ValidationException::withMessages([
                'reference_number' => 'A reference number is required for online payments.',
            ]);
        }

        $paymentStatus = $validated['payment_status'] ?? 'paid';
        if ($paymentStatus !== 'paid' && $validated['payment_method'] === 'cash') {
            throw ValidationException::withMessages([
                'payment_status' => 'Cash payments must be recorded as paid.',
            ]);
        }

        $payment = DB::transaction(function () use ($validated, $order, $request, $paymentStatus) {
            $shift = CashierShift::lockActiveForUser($request->user()->id);
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $balance = $lockedOrder->remainingBalance();

            if (! in_array($lockedOrder->status, ['pending', 'partially_paid']) || $balance <= 0) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'This order has no outstanding payable balance.',
                ]);
            }

            $amountPaid = round((float) $validated['amount_paid'], 2);
            if ($amountPaid > $balance) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'Payment cannot exceed the remaining order balance.',
                ]);
            }

            $items = $lockedOrder->orderItems()->where('status', 'active')->get();
            $participants = $items->pluck('assigned_to')->filter()->unique();
            $personName = trim($validated['person_name'] ?? '');

            if ($participants->isNotEmpty()) {
                if ($personName === '' || ! $participants->contains($personName)) {
                    throw ValidationException::withMessages([
                        'person_name' => 'Choose a person assigned to products in this order.',
                    ]);
                }

                $personDue = round((float) $items->where('assigned_to', $personName)->sum('payable_total'), 2);
                $personPaid = (float) $lockedOrder->payments()
                    ->where('status', 'paid')
                    ->where('customer_name', $personName)
                    ->sum('amount_paid');
                if ($amountPaid > round($personDue - $personPaid, 2)) {
                    throw ValidationException::withMessages([
                        'amount_paid' => 'Payment exceeds this person’s remaining assigned-product total.',
                    ]);
                }
            }

            $amountReceived = $paymentStatus !== 'paid'
                ? 0
                : ($validated['payment_method'] === 'cash'
                    ? (float) $validated['amount_received']
                    : $amountPaid);
            if ($validated['payment_method'] === 'cash' && $amountReceived < $amountPaid) {
                throw ValidationException::withMessages([
                    'amount_received' => 'Cash received must cover this payment amount.',
                ]);
            }

            $payment = Payment::create([
                'order_id' => $lockedOrder->id,
                'shift_id' => $shift->id,
                'method' => $validated['payment_method'],
                'amount_received' => $amountReceived,
                'amount_paid' => $amountPaid,
                'change_amount' => max(0, $amountReceived - $amountPaid),
                'status' => $paymentStatus,
                'reference_number' => $validated['reference_number'] ?? null,
                'customer_name' => $personName !== '' ? $personName : null,
                'comment' => $validated['payment_comment'] ?? null,
            ]);

            $newBalance = $balance;
            if ($paymentStatus === 'paid') {
                $newBalance = round($balance - $amountPaid, 2);
                $lockedOrder->update(['status' => $newBalance <= 0 ? 'completed' : 'partially_paid']);
            }

            AuditService::logFromUser($request->user(), 'order_payment_recorded', 'Orders', [
                'order_number' => $lockedOrder->order_number,
                'payment_id' => $payment->id,
                'payer' => $personName ?: null,
                'amount_paid' => $amountPaid,
                'remaining_balance' => $newBalance,
                'payment_method' => $validated['payment_method'],
                'payment_status' => $paymentStatus,
            ], $payment);

            return $payment;
        }, 3);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'payment' => $payment,
                'remaining_balance' => $order->fresh()->remainingBalance(),
            ]);
        }

        return redirect()->route('orders.show', $order)
            ->with('success', 'Payment recorded successfully.');
    }
}
