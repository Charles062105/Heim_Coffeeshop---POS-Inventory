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

        $order->load(['orderItems.product', 'orderItems.size', 'orderItems.addons.addon', 'payments', 'paymentPortions', 'refunds', 'refund']);

        $paidByPerson = $order->payments
            ->where('status', 'paid')
            ->filter(fn ($payment) => filled($payment->customer_name))
            ->groupBy(fn ($payment) => trim($payment->customer_name))
            ->map(fn ($payments) => round((float) $payments->sum('amount_paid'), 2));

        $splitPayments = $order->paymentPortions->isNotEmpty()
            ? $order->paymentPortions->map(function ($portion) use ($order) {
                $due = (float) $portion->due_amount;
                $paid = (float) $portion->amount_paid;
                $remaining = max(0, round($due - $paid, 2));

                return [
                    'person' => $portion->payer_name,
                    'items' => (int) $order->orderItems
                        ->where('status', 'active')
                        ->where('assigned_to', $portion->payer_name)
                        ->sum('quantity'),
                    'due' => $due,
                    'paid' => $paid,
                    'remaining' => $remaining,
                    'status' => $portion->status,
                ];
            })->values()
            : $order->orderItems
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

        if ($request->has('payments')) {
            $validated = $request->validate([
                'payments' => 'required|array|min:1',
                'payments.*.amount_paid' => 'required|numeric|decimal:0,2|min:0.01',
                'payments.*.amount_received' => 'nullable|numeric|decimal:0,2|min:0',
                'payments.*.payment_method' => 'required_without:payments.*.method|nullable|in:cash,online,grabfood,grab',
                'payments.*.method' => 'nullable|in:cash,online,grabfood,grab',
                'payments.*.payment_status' => 'nullable|in:paid,pending,failed',
                'payments.*.person_name' => 'nullable|string|max:150',
                'payments.*.payment_comment' => 'nullable|string|max:255',
                'payments.*.comment' => 'nullable|string|max:255',
                'payments.*.reference_number' => 'nullable|string|max:100',
                'payments.*.request_id' => 'nullable|uuid',
            ]);

            $paymentItems = collect($validated['payments'])->map(function ($item) {
                $method = $item['method'] ?? ($item['payment_method'] ?? 'cash');
                $comment = $item['comment'] ?? ($item['payment_comment'] ?? null);

                return [
                    'method' => $method,
                    'amount_paid' => round((float) $item['amount_paid'], 2),
                    'amount_received' => isset($item['amount_received']) ? round((float) $item['amount_received'], 2) : ($method === 'cash' ? 0 : round((float) $item['amount_paid'], 2)),
                    'person_name' => trim((string) ($item['person_name'] ?? '')),
                    'reference_number' => trim((string) ($item['reference_number'] ?? '')),
                    'comment' => $comment ? trim((string) $comment) : null,
                    'payment_status' => $item['payment_status'] ?? 'paid',
                    'request_id' => $item['request_id'] ?? null,
                ];
            });

            foreach ($paymentItems as $idx => $p) {
                if (($order->order_type ?? 'dine_in') !== 'grab' && in_array($p['method'], ['grabfood', 'grab'], true)) {
                    throw ValidationException::withMessages([
                        "payments.$idx.method" => 'GrabFood platform settlements are only valid for Grab orders.',
                    ]);
                }
                if ($p['method'] === 'online' && blank($p['reference_number'])) {
                    throw ValidationException::withMessages([
                        "payments.$idx.reference_number" => 'A reference number is required for online payments.',
                    ]);
                }
                if ($p['method'] === 'cash' && $p['payment_status'] === 'paid' && $p['amount_received'] < $p['amount_paid']) {
                    throw ValidationException::withMessages([
                        "payments.$idx.amount_received" => 'Cash received must cover this payment amount.',
                    ]);
                }
            }

            $recordedPayments = DB::transaction(function () use ($paymentItems, $order, $request) {
                $shift = CashierShift::lockActiveForUser($request->user()->id);
                $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
                $balance = $lockedOrder->remainingBalance();

                if (! in_array($lockedOrder->status, ['pending', 'partially_paid']) || $balance <= 0) {
                    throw ValidationException::withMessages([
                        'payments' => 'This order has no outstanding payable balance.',
                    ]);
                }

                $totalBatchPaid = round($paymentItems->sum('amount_paid'), 2);
                if ($totalBatchPaid > $balance) {
                    throw ValidationException::withMessages([
                        'payments' => 'Total payments (₱'.number_format($totalBatchPaid, 2).') cannot exceed the remaining balance (₱'.number_format($balance, 2).').',
                    ]);
                }

                $results = [];
                $portionsExist = $lockedOrder->paymentPortions()->exists();

                foreach ($paymentItems as $idx => $item) {
                    $amountPaid = $item['amount_paid'];
                    $personName = $item['person_name'];
                    $portion = null;

                    if ($portionsExist) {
                        if ($personName !== '') {
                            $portion = $lockedOrder->paymentPortions()
                                ->where('payer_name', $personName)
                                ->lockForUpdate()
                                ->first();
                        }

                        if (! $portion && $personName !== '') {
                            $portion = $lockedOrder->paymentPortions()->create([
                                'payer_name' => $personName,
                                'due_amount' => $amountPaid,
                                'amount_paid' => 0,
                                'status' => 'pending',
                            ]);
                        } elseif ($portion) {
                            $portionRemaining = round((float) $portion->due_amount - (float) $portion->amount_paid, 2);
                            if ($amountPaid > $portionRemaining) {
                                $portion->update([
                                    'due_amount' => round((float) $portion->amount_paid + $amountPaid, 2),
                                ]);
                            }
                        }
                    } elseif ($personName !== '') {
                        $portion = $lockedOrder->paymentPortions()->create([
                            'payer_name' => $personName,
                            'due_amount' => $amountPaid,
                            'amount_paid' => 0,
                            'status' => 'pending',
                        ]);
                    }

                    $amountReceived = $item['payment_status'] !== 'paid'
                        ? 0
                        : ($item['method'] === 'cash' ? $item['amount_received'] : $amountPaid);

                    $payment = Payment::create([
                        'order_id' => $lockedOrder->id,
                        'portion_id' => $portion?->id,
                        'shift_id' => $shift->id,
                        'method' => $item['method'],
                        'amount_received' => $amountReceived,
                        'amount_paid' => $amountPaid,
                        'change_amount' => max(0, $amountReceived - $amountPaid),
                        'status' => $item['payment_status'],
                        'reference_number' => $item['reference_number'] ?: null,
                        'customer_name' => $personName !== '' ? $personName : null,
                        'comment' => $item['comment'] ?: null,
                        'request_id' => $item['request_id'] ?: null,
                    ]);

                    if ($portion && $item['payment_status'] === 'paid') {
                        $portionPaid = (float) $lockedOrder->payments()
                            ->where('portion_id', $portion->id)
                            ->where('status', 'paid')
                            ->sum('amount_paid');
                        $portion->update([
                            'amount_paid' => $portionPaid,
                            'status' => $portionPaid >= (float) $portion->due_amount ? 'paid' : 'partially_paid',
                        ]);
                    }

                    AuditService::logFromUser($request->user(), 'order_payment_recorded', 'Orders', [
                        'order_number' => $lockedOrder->order_number,
                        'payment_id' => $payment->id,
                        'payer' => $personName ?: null,
                        'amount_paid' => $amountPaid,
                        'payment_method' => $item['method'],
                        'payment_status' => $item['payment_status'],
                    ], $payment);

                    $results[] = $payment;
                }

                $totalNowPaid = $lockedOrder->paidAmount();
                $newBalance = max(0, round((float) $lockedOrder->total - $totalNowPaid, 2));
                $lockedOrder->update([
                    'status' => $newBalance <= 0 ? 'completed' : 'partially_paid',
                    'payment_status' => $newBalance <= 0 ? 'paid' : 'partially_paid',
                ]);

                return [$results, $lockedOrder];
            }, 3);

            [$payments, $updatedOrder] = $recordedPayments;

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'payments' => collect($payments)->map(fn ($p) => [
                        'id' => $p->id,
                        'person_name' => $p->customer_name,
                        'amount_paid' => (float) $p->amount_paid,
                        'method' => $p->method,
                        'reference_number' => $p->reference_number,
                        'comment' => $p->comment,
                        'status' => $p->status,
                        'created_at' => $p->created_at?->copy()->timezone(config('app.business_timezone', 'Asia/Manila'))->format('M d, Y h:i A'),
                    ]),
                    'order' => [
                        'id' => $updatedOrder->id,
                        'order_number' => $updatedOrder->order_number,
                        'total' => (float) $updatedOrder->total,
                        'total_paid' => $updatedOrder->paidAmount(),
                        'remaining_balance' => $updatedOrder->remainingBalance(),
                        'status' => $updatedOrder->status,
                        'payment_status' => $updatedOrder->payment_status,
                    ],
                    'remaining_balance' => $updatedOrder->remainingBalance(),
                ]);
            }

            return redirect()->route('orders.show', $order)
                ->with('success', 'Payments recorded successfully.');
        }

        $validated = $request->validate([
            'amount_paid' => 'required|numeric|decimal:0,2|min:0.01',
            'amount_received' => 'required_if:payment_method,cash|numeric|decimal:0,2|min:0',
            'payment_method' => 'required|in:cash,online,grabfood,grab',
            'payment_status' => 'nullable|in:paid,pending,failed',
            'person_name' => 'nullable|string|max:150',
            'payment_comment' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:100',
            'request_id' => 'nullable|uuid',
            'split_payment' => 'nullable|boolean',
        ]);

        if ($request->boolean('split_payment') && blank($validated['person_name'] ?? null)) {
            throw ValidationException::withMessages([
                'person_name' => 'Enter the name of the person making this payment.',
            ]);
        }

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
            if (! empty($validated['request_id'])) {
                $existingPayment = $lockedOrder->payments()
                    ->where('request_id', $validated['request_id'])
                    ->first();
                if ($existingPayment) {
                    return $existingPayment;
                }
            }
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

            $personName = trim($validated['person_name'] ?? '');
            $portion = null;

            $portionsExist = $lockedOrder->paymentPortions()->exists();
            if ($portionsExist) {
                if ($personName !== '') {
                    $portion = $lockedOrder->paymentPortions()
                        ->where('payer_name', $personName)
                        ->lockForUpdate()
                        ->first();
                }

                if (! $portion && $personName !== '') {
                    $portion = $lockedOrder->paymentPortions()->create([
                        'payer_name' => $personName,
                        'due_amount' => $amountPaid,
                        'amount_paid' => 0,
                        'status' => 'pending',
                    ]);
                } elseif ($portion) {
                    $portionRemaining = round((float) $portion->due_amount - (float) $portion->amount_paid, 2);
                    if ($amountPaid > $portionRemaining) {
                        $portion->update([
                            'due_amount' => round((float) $portion->amount_paid + $amountPaid, 2),
                        ]);
                    }
                }
            } elseif ($personName !== '' && $request->boolean('split_payment')) {
                $portion = $lockedOrder->paymentPortions()->create([
                    'payer_name' => $personName,
                    'due_amount' => $amountPaid,
                    'amount_paid' => 0,
                    'status' => 'pending',
                ]);
            } else {
                $items = $lockedOrder->orderItems()->where('status', 'active')->get();
                $participants = $items->pluck('assigned_to')->filter()->unique();
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
                'portion_id' => $portion?->id,
                'shift_id' => $shift->id,
                'method' => $validated['payment_method'],
                'amount_received' => $amountReceived,
                'amount_paid' => $amountPaid,
                'change_amount' => max(0, $amountReceived - $amountPaid),
                'status' => $paymentStatus,
                'reference_number' => $validated['reference_number'] ?? null,
                'customer_name' => $personName !== '' ? $personName : null,
                'comment' => $validated['payment_comment'] ?? null,
                'request_id' => $validated['request_id'] ?? null,
            ]);

            $newBalance = $balance;
            if ($paymentStatus === 'paid') {
                if ($portion) {
                    $portionPaid = (float) $lockedOrder->payments()
                        ->where('portion_id', $portion->id)
                        ->where('status', 'paid')
                        ->sum('amount_paid');
                    $portion->update([
                        'amount_paid' => $portionPaid,
                        'status' => $portionPaid >= (float) $portion->due_amount ? 'paid' : 'partially_paid',
                    ]);
                }

                $totalNowPaid = $lockedOrder->paidAmount();
                $newBalance = max(0, round((float) $lockedOrder->total - $totalNowPaid, 2));
                $lockedOrder->update([
                    'status' => $newBalance <= 0 ? 'completed' : 'partially_paid',
                    'payment_status' => $newBalance <= 0 ? 'paid' : 'partially_paid',
                ]);
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
            $payment->load('shift.user');
            $refreshedOrder = $order->fresh(['payments.shift.user', 'paymentPortions']);
            $totalPaid = $refreshedOrder->paidAmount();

            return response()->json([
                'success' => true,
                'payment' => [
                    'id' => $payment->id,
                    'person_name' => $payment->customer_name,
                    'amount_paid' => (float) $payment->amount_paid,
                    'method' => $payment->method,
                    'reference_number' => $payment->reference_number,
                    'comment' => $payment->comment,
                    'status' => $payment->status,
                    'created_at' => $payment->created_at?->copy()->timezone(config('app.business_timezone', 'Asia/Manila'))->format('M d, Y h:i A'),
                    'cashier_name' => $payment->shift?->user?->name ?? $payment->shift?->cashier_name ?? $request->user()->name,
                ],
                'remaining_balance' => $refreshedOrder->remainingBalance(),
                'order' => [
                    'id' => $refreshedOrder->id,
                    'order_number' => $refreshedOrder->order_number,
                    'customer_name' => $refreshedOrder->customer_name,
                    'order_type' => $refreshedOrder->order_type,
                    'total' => (float) $refreshedOrder->total,
                    'total_paid' => $totalPaid,
                    'remaining_balance' => $refreshedOrder->remainingBalance(),
                    'status' => $refreshedOrder->status,
                    'payment_status' => $refreshedOrder->payment_status,
                    'payment_portions' => $refreshedOrder->paymentPortions->map(fn ($portion) => [
                        'payer_name' => $portion->payer_name,
                        'due_amount' => (float) $portion->due_amount,
                        'amount_paid' => (float) $portion->amount_paid,
                        'remaining_balance' => max(0, round((float) $portion->due_amount - (float) $portion->amount_paid, 2)),
                        'status' => $portion->status,
                    ])->values(),
                ],
            ]);
        }

        return redirect()->route('orders.show', $order)
            ->with('success', 'Payment recorded successfully.');
    }

    public function storePaymentPortions(Request $request, Order $order)
    {
        Gate::authorize('recordPayment', $order);

        if (! CashierShift::activeForUser($request->user()->id)) {
            throw ValidationException::withMessages([
                'shift' => 'Start a cashier shift before setting split payment portions.',
            ]);
        }

        $validated = $request->validate([
            'portions' => 'required|array|min:1|max:12',
            'portions.*.payer_name' => 'required|string|max:150',
            'portions.*.amount' => 'required|numeric|decimal:0,2|min:0.01',
        ]);

        $portions = collect($validated['portions'])->map(fn (array $portion) => [
            'payer_name' => trim($portion['payer_name']),
            'amount' => round((float) $portion['amount'], 2),
        ]);
        $normalizedNames = $portions->pluck('payer_name')->map(fn (string $name) => mb_strtolower($name));
        if ($normalizedNames->contains('') || $normalizedNames->unique()->count() !== $portions->count()) {
            throw ValidationException::withMessages([
                'portions' => 'Each split portion must have a unique payer name.',
            ]);
        }

        $savedPortions = DB::transaction(function () use ($order, $portions) {
            CashierShift::lockActiveForUser(request()->user()->id);
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            if (! in_array($lockedOrder->status, ['pending', 'partially_paid'], true)) {
                throw ValidationException::withMessages([
                    'order' => 'Only unpaid or partially paid orders can be split.',
                ]);
            }

            // Determine how much of the remaining balance is NOT yet covered by existing portions.
            $existingPortionsTotal = (float) $lockedOrder->paymentPortions()->sum('due_amount');
            $remainingBalance      = $lockedOrder->remainingBalance();
            $uncoveredBalance      = max(0, round($remainingBalance - $existingPortionsTotal + (float) $lockedOrder->paymentPortions()->sum('amount_paid'), 2));
            // Recalculate: uncovered = total - sum of all existing due_amounts (already-saved payers)
            // A simpler accurate version: uncovered = total - already saved due_amounts.
            $existingDueTotal = round((float) $lockedOrder->paymentPortions()->sum('due_amount'), 2);
            $uncoveredBalance = round(max(0, (float) $lockedOrder->total - $existingDueTotal), 2);

            if ($uncoveredBalance <= 0) {
                throw ValidationException::withMessages([
                    'portions' => 'All split portions for this order are already configured.',
                ]);
            }

            // Validate that the new portions only cover names not yet registered
            $existingPayerNames = $lockedOrder->paymentPortions()->pluck('payer_name')
                ->map(fn (string $n) => mb_strtolower(trim($n)))
                ->all();

            foreach ($portions as $portion) {
                $normalizedName = mb_strtolower(trim($portion['payer_name']));
                if (in_array($normalizedName, $existingPayerNames, true)) {
                    throw ValidationException::withMessages([
                        'portions' => "A split portion for '{$portion['payer_name']}' already exists for this order.",
                    ]);
                }
            }

            if (round((float) $portions->sum('amount'), 2) !== $uncoveredBalance) {
                throw ValidationException::withMessages([
                    'portions' => 'Split portions must add up to the uncovered remaining balance of ₱'.number_format($uncoveredBalance, 2).'.',
                ]);
            }

            foreach ($portions as $portion) {
                $lockedOrder->paymentPortions()->create([
                    'payer_name' => $portion['payer_name'],
                    'due_amount' => $portion['amount'],
                    'amount_paid' => 0,
                    'status' => 'pending',
                ]);
            }

            return $lockedOrder->paymentPortions()->get();
        }, 3);

        return response()->json([
            'success' => true,
            'payment_portions' => $savedPortions->map(fn ($portion) => [
                'payer_name' => $portion->payer_name,
                'due_amount' => (float) $portion->due_amount,
                'amount_paid' => (float) $portion->amount_paid,
                'remaining_balance' => (float) $portion->due_amount,
                'status' => $portion->status,
            ])->values(),
        ]);
    }

    public function paymentHistory(Order $order)
    {
        Gate::authorize('view', $order);

        $order->load(['payments.shift.user', 'paymentPortions']);
        $totalPaid = $order->paidAmount();

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'order_type' => $order->order_type,
                'total' => (float) $order->total,
                'total_paid' => $totalPaid,
                'remaining_balance' => $order->remainingBalance(),
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_portions' => $order->paymentPortions->map(fn ($portion) => [
                    'payer_name' => $portion->payer_name,
                    'due_amount' => (float) $portion->due_amount,
                    'amount_paid' => (float) $portion->amount_paid,
                    'remaining_balance' => max(0, round((float) $portion->due_amount - (float) $portion->amount_paid, 2)),
                    'status' => $portion->status,
                ])->values(),
            ],
            'payments' => $order->payments
                ->sortBy('created_at')
                ->values()
                ->map(fn (Payment $payment) => [
                    'id' => $payment->id,
                    'person_name' => $payment->customer_name ?: 'Order payment',
                    'amount_paid' => (float) $payment->amount_paid,
                    'method' => $payment->method,
                    'reference_number' => $payment->reference_number,
                    'comment' => $payment->comment,
                    'status' => $payment->status,
                    'created_at' => $payment->created_at?->copy()->timezone(config('app.business_timezone', 'Asia/Manila'))->format('M d, Y h:i A'),
                    'cashier_name' => $payment->shift?->user?->name ?? $payment->shift?->cashier_name ?? $order->cashier_name,
                ]),
        ]);
    }
}
