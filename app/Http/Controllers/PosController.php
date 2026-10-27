<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductSize;
use App\Models\TaxSetting;
use App\Models\User;
use App\Models\VoidLog;
use App\Services\AuditService;
use App\Services\InventoryService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PosController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $request->user()?->branch_id
            ?? Branch::where('is_active', true)->orderBy('id')->value('id');
        $categories = Category::where('status', 'active')
            ->with(['products' => function ($q) use ($branchId) {
                $q->where('status', 'active')
                    ->with(['sizes' => fn ($q2) => $q2
                        ->where('status', 'active')
                        ->with([
                            'recipe.recipeIngredients.ingredient.inventory' => fn ($inventory) => $inventory->where('branch_id', $branchId),
                        ])
                        ->orderBy('price')]);
            }])
            ->get();

        $addons = ProductAddon::where('status', 'active')
            ->with([
                'addonIngredients.ingredient.inventory' => fn ($inventory) => $inventory->where('branch_id', $branchId),
                'addonIngredients.replacesIngredient.inventory' => fn ($inventory) => $inventory->where('branch_id', $branchId),
            ])
            ->get();
        $taxSetting = TaxSetting::current();
        $activeShift = $request->user() ? CashierShift::activeForUser($request->user()->id) : null;

        return view('pos.index', compact('categories', 'addons', 'taxSetting', 'activeShift'));
    }

    public function openSplitOrders(Request $request)
    {
        $orders = Order::query()
            ->whereIn('status', ['pending', 'partially_paid'])
            ->with(['payments', 'paymentPortions'])
            ->latest()
            ->limit(100)
            ->get()
            ->filter(fn (Order $order) => Gate::allows('view', $order))
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'total' => (float) $order->total,
                'total_paid' => $order->paidAmount(),
                'remaining_balance' => $order->remainingBalance(),
                'payment_portions' => $order->paymentPortions->map(fn ($portion) => [
                    'payer_name' => $portion->payer_name,
                    'due_amount' => (float) $portion->due_amount,
                    'amount_paid' => (float) $portion->amount_paid,
                    'remaining_balance' => max(0, round((float) $portion->due_amount - (float) $portion->amount_paid, 2)),
                    'status' => $portion->status,
                ])->values(),
            ])
            ->values();

        return response()->json(['orders' => $orders]);
    }

    public function products(Request $request)
    {
        $categoryId = $request->get('category_id');
        $products = Product::where('status', 'active')
            ->whereHas('category', fn ($query) => $query->where('status', 'active'))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->with(['sizes' => fn ($q) => $q->where('status', 'active')->orderBy('price'), 'category'])
            ->get();

        return response()->json($products);
    }

    public function sizes(Product $product)
    {
        if ($product->status !== 'active' || $product->category?->status !== 'active') {
            return response()->json([]);
        }

        $sizes = $product->sizes()->where('status', 'active')->orderBy('price')->get();

        return response()->json($sizes);
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_type' => 'nullable|string|in:dine_in,take_out,grab',
            'grab_order_code' => 'required_if:order_type,grab|nullable|string|max:100',
            'rider_code' => 'required_if:order_type,grab|nullable|string|max:100',
            'customer_name' => 'required_if:split_payment,1|nullable|string|max:150',
            'split_payment' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.product_size_id' => 'required|exists:product_sizes,id',
            'items.*.quantity' => 'required|integer|min:1|max:999',
            'items.*.comment' => 'nullable|string|max:255',
            'items.*.assigned_to' => 'nullable|string|max:150',
            'items.*.addon_ids' => 'nullable|array',
            'items.*.addon_ids.*' => [
                Rule::exists('product_addons', 'id')->where('status', 'active'),
            ],
            'discount' => 'nullable|numeric|decimal:0,2|min:0',
            'discount_type' => 'nullable|string|in:none,senior,pwd,custom',
            'discount_label' => 'nullable|string|max:100',
            'discount_id_number' => 'required_if:discount_type,senior,pwd|nullable|string|max:100',
            'authorizer_email' => 'nullable|email',
            'authorizer_password' => 'nullable|string',
            'payment_method' => 'required|in:cash,online,grabfood,grab',
            'amount_received' => 'required_unless:payment_method,grabfood,grab|nullable|numeric|decimal:0,2|min:0',
            'amount_paid' => 'nullable|numeric|decimal:0,2|min:0',
            'person_name' => 'nullable|string|max:150',
            'payment_request_id' => 'nullable|uuid',
            'save_split_order' => 'nullable|boolean',
            'payment_comment' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:100',
            'checkout_request_id' => 'nullable|uuid',
            'payments' => 'nullable|array|min:1',
            'payments.*.method' => 'required|in:cash,online,grabfood,grab',
            'payments.*.amount_paid' => 'required|numeric|decimal:0,2|min:0.01',
            'payments.*.amount_received' => 'nullable|numeric|decimal:0,2|min:0',
            'payments.*.person_name' => 'nullable|string|max:150',
            'payments.*.reference_number' => 'nullable|string|max:100',
            'payments.*.comment' => 'nullable|string|max:255',
            'split_portions' => 'nullable|array|min:1',
            'split_portions.*.payer_name' => 'required|string|max:150',
            'split_portions.*.amount' => 'required|numeric|decimal:0,2|min:0.01',
            'held_order_id' => 'nullable|integer|min:1',
        ]);

        foreach ($request->input('items', []) as $index => $item) {
            $addonIds = $item['addon_ids'] ?? [];
            if (count($addonIds) !== count(array_unique($addonIds))) {
                throw ValidationException::withMessages([
                    "items.$index.addon_ids" => 'An add-on can only be selected once per order item.',
                ]);
            }
        }

        $request->merge(['cashier_name' => $request->user()->name]);
        $orderType = strtolower(trim($request->order_type ?? 'dine_in'));
        $isGrab = $orderType === 'grab';
        $isGrabPayment = in_array($request->payment_method, ['grabfood', 'grab']);
        $hasGrabSettlement = $isGrabPayment || collect($request->input('payments', []))
            ->contains(fn ($payment) => in_array($payment['method'] ?? null, ['grabfood', 'grab'], true));
        if (! $isGrab && $hasGrabSettlement) {
            throw ValidationException::withMessages([
                'payment_method' => 'GrabFood platform settlements are only valid for Grab orders.',
            ]);
        }
        if ($isGrab && (blank(trim((string) $request->grab_order_code)) || blank(trim((string) $request->rider_code)))) {
            throw ValidationException::withMessages([
                'grab_order_code' => 'Grab orders require both a Grab order code and rider code.',
            ]);
        }
        $activeShift = $request->user()
            ? CashierShift::activeForUser($request->user()->id)
            : null;
        if (! $activeShift) {
            throw ValidationException::withMessages([
                'shift' => 'Start a cashier shift before taking payments or creating orders.',
            ]);
        }
        if ($request->boolean('split_payment') && ! $request->filled('person_name') && (float) $request->input('amount_paid', 0) > 0) {
            throw ValidationException::withMessages([
                'person_name' => 'Enter the name of the person making this payment.',
            ]);
        }

        // Online payments must provide a reference number
        if (! $request->has('payments') && $request->payment_method === 'online' && empty(trim($request->reference_number ?? ''))) {
            throw ValidationException::withMessages([
                'reference_number' => 'A reference number is required for online payments.',
            ]);
        }

        $user = $request->user();
        $createdOrder = null;
        $checkoutRequestId = $request->input('checkout_request_id') ?: (string) Str::uuid();
        $existingOrder = Order::query()
            ->where('checkout_request_id', $checkoutRequestId)
            ->first();
        if ($existingOrder) {
            if (! $existingOrder->shift()->where('user_id', $user->id)->exists()) {
                throw ValidationException::withMessages([
                    'checkout_request_id' => 'This checkout request belongs to a different cashier.',
                ]);
            }
            $createdOrder = $existingOrder;
        }

        $heldOrderToFinalize = null;
        DB::transaction(function () use ($request, $orderType, $isGrab, $isGrabPayment, $checkoutRequestId, &$createdOrder, &$heldOrderToFinalize) {
            if ($createdOrder) {
                return;
            }

            $shiftId = CashierShift::lockActiveForUser($request->user()->id)->id;
            $existingOrder = Order::query()
                ->where('checkout_request_id', $checkoutRequestId)
                ->first();
            if ($existingOrder) {
                if (! $existingOrder->shift()->where('user_id', $request->user()->id)->exists()) {
                    throw ValidationException::withMessages([
                        'checkout_request_id' => 'This checkout request belongs to a different cashier.',
                    ]);
                }
                $createdOrder = $existingOrder;

                return;
            }

            $grabOrderCode = $isGrab ? strtoupper(trim((string) $request->grab_order_code)) : null;
            $grabRiderCode = $isGrab ? strtoupper(trim((string) $request->rider_code)) : null;

            // Finalize a held order in place so its order number and history remain stable.
            if ($request->filled('held_order_id')) {
                $heldOrderToFinalize = Order::query()->lockForUpdate()->find($request->held_order_id);
                if (! $heldOrderToFinalize || $heldOrderToFinalize->status !== 'held') {
                    throw ValidationException::withMessages([
                        'held_order_id' => 'The saved ticket is no longer available.',
                    ]);
                }
                Gate::authorize('view', $heldOrderToFinalize);
            }

            // ── Build order ──────────────────────────────────────────────
            $orderNumber = Order::generateOrderNumber($isGrab ? 'GB-' : 'ORD-');

            $subtotal = 0;
            $itemsData = [];

            foreach ($request->items as $item) {
                $size = ProductSize::whereKey($item['product_size_id'])
                    ->where('status', 'active')
                    ->whereHas('product', fn ($query) => $query
                        ->where('status', 'active')
                        ->whereHas('category', fn ($category) => $category->where('status', 'active')))
                    ->with('product')
                    ->firstOrFail();
                $qty = (int) $item['quantity'];

                // Use Grab price when order is a Grab order
                $unitPrice = $isGrab ? $size->getGrabPrice() : (float) $size->price;
                $lineTotal = $unitPrice * $qty;

                // Add-ons
                $addonTotal = 0;
                $addonIds = $item['addon_ids'] ?? [];
                if (! empty($addonIds)) {
                    $addonTotal = ProductAddon::whereIn('id', $addonIds)->sum('price') * $qty;
                }

                $subtotal += $lineTotal + $addonTotal;

                $itemsData[] = [
                    'size' => $size,
                    'qty' => $qty,
                    'unitPrice' => $unitPrice + (empty($addonIds) ? 0 : ProductAddon::whereIn('id', $addonIds)->sum('price')),
                    'subtotal' => $lineTotal + $addonTotal,
                    'comment' => ! empty($item['comment']) ? trim($item['comment']) : null,
                    'assignedTo' => ! empty($item['assigned_to']) ? trim($item['assigned_to']) : null,
                    'addonIds' => $addonIds,
                ];
            }

            $discountType = strtolower(trim($request->discount_type ?? 'none'));
            $rawDiscount = (float) ($request->discount ?? 0);

            if ($rawDiscount > $subtotal) {
                throw ValidationException::withMessages([
                    'discount' => 'Discount cannot exceed the order subtotal.',
                ]);
            }

            // Authorization check: Senior Citizen & PWD discounts are statutory and accessible to cashiers.
            // Custom discounts require manager/owner privileges or manager credentials.
            $isStatutoryDiscount = in_array($discountType, ['senior', 'pwd']);
            $discountAuthorizedBy = $request->user();
            if (($rawDiscount > 0 || $isStatutoryDiscount) && ! $isStatutoryDiscount) {
                $hasAuthPrivilege = $request->user()?->canAuthorize() ?? false;
                if (! $hasAuthPrivilege) {
                    $authorized = false;
                    if ($request->filled('authorizer_email') && $request->filled('authorizer_password')) {
                        $authMgr = User::where('email', $request->authorizer_email)->where('status', 'active')->first();
                        if ($authMgr && Hash::check($request->authorizer_password, $authMgr->password) && $authMgr->canAuthorize()) {
                            $authorized = true;
                            $discountAuthorizedBy = $authMgr;
                        }
                    }
                    if (! $authorized) {
                        throw ValidationException::withMessages([
                            'discount' => 'Only managers and owners can apply discounts.',
                        ]);
                    }
                }
            }

            // Compute tax, discount and totals using configured tax settings
            $taxSetting = TaxSetting::current();
            $computed = $taxSetting->computeOrder($subtotal, $discountType, $rawDiscount);
            if ($rawDiscount > 0 && $computed['total'] <= 0) {
                throw ValidationException::withMessages([
                    'discount' => 'Discount must leave a positive order total.',
                ]);
            }
            $discountIdNumber = in_array($computed['discount_type'], ['senior', 'pwd'], true)
                ? trim((string) $request->input('discount_id_number'))
                : null;

            $discount = $computed['discount'];
            $total = $computed['total'];

            $stagedPayments = collect($request->input('payments', []));
            $hasStagedPayments = $stagedPayments->isNotEmpty();
            $savingUnpaidSplitOrder = $request->boolean('split_payment')
                && $request->boolean('save_split_order')
                && ! $hasStagedPayments
                && (float) $request->input('amount_paid', 0) === 0.0;
            $amountPaid = round($hasStagedPayments
                ? $stagedPayments->sum(fn ($payment) => (float) $payment['amount_paid'])
                : (float) ($request->amount_paid ?? $total), 2);
            $amountPaid = round($amountPaid, 2);
            if ((! $savingUnpaidSplitOrder && $amountPaid <= 0) || $amountPaid > $total) {
                throw ValidationException::withMessages([
                    'amount_paid' => 'Payment amount must be greater than zero and cannot exceed the order total.',
                ]);
            }
            $allocatedTotal = 0;
            foreach ($itemsData as $index => &$itemData) {
                $itemData['payableTotal'] = $index === array_key_last($itemsData)
                    ? round($total - $allocatedTotal, 2)
                    : round($total * $itemData['subtotal'] / max($subtotal, 0.01), 2);
                $allocatedTotal += $itemData['payableTotal'];
            }
            unset($itemData);

            $participants = collect($itemsData)->pluck('assignedTo')->filter()->unique()->values();
            $splitPortions = collect($request->input('split_portions', []))
                ->map(fn (array $portion) => [
                    'payer_name' => trim($portion['payer_name']),
                    'amount' => round((float) $portion['amount'], 2),
                ]);
            if ($participants->isNotEmpty()) {
                $itemPortions = collect($itemsData)
                    ->filter(fn (array $item) => filled($item['assignedTo']))
                    ->groupBy('assignedTo')
                    ->map(fn ($items, $payer) => [
                        'payer_name' => $payer,
                        'amount' => round((float) $items->sum('payableTotal'), 2),
                    ])
                    ->values();
                if ($splitPortions->isNotEmpty()) {
                    $submittedByPayer = $splitPortions->keyBy('payer_name');
                    if ($submittedByPayer->count() !== $splitPortions->count()
                        || $itemPortions->count() !== $submittedByPayer->count()) {
                        throw ValidationException::withMessages([
                            'split_portions' => 'Each item-based split payer must have exactly one portion.',
                        ]);
                    }
                    foreach ($itemPortions as $itemPortion) {
                        if (! $submittedByPayer->has($itemPortion['payer_name'])
                            || $submittedByPayer->get($itemPortion['payer_name'])['amount'] !== $itemPortion['amount']) {
                            throw ValidationException::withMessages([
                                'split_portions' => 'Item-based split portions must match the assigned item totals.',
                            ]);
                        }
                    }
                }
                $splitPortions = $itemPortions;
            }
            if ($splitPortions->isNotEmpty()) {
                $uniquePayerNames = $splitPortions->pluck('payer_name')
                    ->map(fn (string $name) => mb_strtolower($name))
                    ->unique()
                    ->count();
                $portionsSum = round((float) $splitPortions->sum('amount'), 2);
                if ($uniquePayerNames !== $splitPortions->count()
                    || ($savingUnpaidSplitOrder && $portionsSum !== round($total, 2))
                    || (! $savingUnpaidSplitOrder && $portionsSum > round($total, 2))) {
                    throw ValidationException::withMessages([
                        'split_portions' => 'Split portions must have unique payer names and cannot exceed the full order total.',
                    ]);
                }
            }
            if ($request->boolean('split_payment') && $request->boolean('save_split_order') && $splitPortions->isEmpty()) {
                throw ValidationException::withMessages([
                    'split_portions' => 'Add split portions that cover the complete order total.',
                ]);
            }
            if (! $savingUnpaidSplitOrder && ! $hasStagedPayments && $participants->isNotEmpty() && $amountPaid < $total && ! $request->filled('person_name')) {
                throw ValidationException::withMessages([
                    'person_name' => 'Select the person making this split payment.',
                ]);
            }
            if (! $hasStagedPayments && $request->filled('person_name') && $participants->isNotEmpty() && ! $participants->contains(trim($request->person_name))) {
                throw ValidationException::withMessages([
                    'person_name' => 'The payer must be assigned to at least one item in this order.',
                ]);
            }
            if (! $hasStagedPayments && $request->filled('person_name') && $participants->isNotEmpty()) {
                $personDue = collect($itemsData)
                    ->where('assignedTo', trim($request->person_name))
                    ->sum('payableTotal');
                if ($amountPaid > $personDue) {
                    throw ValidationException::withMessages([
                        'amount_paid' => 'Payment exceeds this person’s assigned-product total.',
                    ]);
                }
            }
            if (! $hasStagedPayments && $splitPortions->isNotEmpty() && $request->filled('person_name')) {
                $portion = $splitPortions->firstWhere('payer_name', trim($request->person_name));
                if ($portion && $amountPaid > $portion['amount']) {
                    throw ValidationException::withMessages([
                        'amount_paid' => 'Payment exceeds this payer’s split portion.',
                    ]);
                }
            }

            if ($hasStagedPayments) {
                $paymentsByPerson = [];
                foreach ($stagedPayments as $index => $payment) {
                    $method = $payment['method'];
                    $paymentAmount = round((float) $payment['amount_paid'], 2);
                    $personName = trim($payment['person_name'] ?? '');
                    $received = round((float) ($payment['amount_received'] ?? 0), 2);

                    if ($method === 'cash' && $received < $paymentAmount) {
                        throw ValidationException::withMessages([
                            "payments.$index.amount_received" => 'Cash received must be at least the payment amount.',
                        ]);
                    }
                    if ($method === 'online' && empty(trim($payment['reference_number'] ?? ''))) {
                        throw ValidationException::withMessages([
                            "payments.$index.reference_number" => 'A reference number is required for online payments.',
                        ]);
                    }
                    if ($participants->isNotEmpty()) {
                        $singleFullOrderPayment = $stagedPayments->count() === 1 && $amountPaid >= $total;
                        if ($personName === '' && ! $singleFullOrderPayment) {
                            throw ValidationException::withMessages([
                                "payments.$index.person_name" => 'Choose a person assigned to products in this order for each split payment.',
                            ]);
                        }
                        if ($personName !== '') {
                            if (! $participants->contains($personName)) {
                                throw ValidationException::withMessages([
                                    "payments.$index.person_name" => 'The payer must be assigned to products in this order.',
                                ]);
                            }
                            if ($splitPortions->isEmpty()) {
                                $paymentsByPerson[$personName] = round(($paymentsByPerson[$personName] ?? 0) + $paymentAmount, 2);
                            }
                        }
                    }
                    if ($splitPortions->isNotEmpty()) {
                        if ($personName === '' || ! $splitPortions->contains('payer_name', $personName)) {
                            throw ValidationException::withMessages([
                                "payments.$index.person_name" => 'Choose a payer with an allocated split portion.',
                            ]);
                        }
                        $paymentsByPerson[$personName] = round(($paymentsByPerson[$personName] ?? 0) + $paymentAmount, 2);
                    }
                }

                foreach ($paymentsByPerson as $personName => $personPaid) {
                    $personDue = $splitPortions->isNotEmpty()
                        ? (float) $splitPortions->firstWhere('payer_name', $personName)['amount']
                        : collect($itemsData)->where('assignedTo', $personName)->sum('payableTotal');
                    if ($personPaid > $personDue) {
                        throw ValidationException::withMessages([
                            'payments' => "Payments for $personName exceed their allocated split portion.",
                        ]);
                    }
                }
            }

            // Validate cash tender against this payment, not the full order balance.
            if (! $hasStagedPayments && $request->payment_method === 'cash' && (float) ($request->amount_received ?? 0) < $amountPaid) {
                throw ValidationException::withMessages([
                    'amount_received' => 'Cash amount received (₱'.number_format($request->amount_received, 2).') is less than the payment amount (₱'.number_format($amountPaid, 2).').',
                ]);
            }

            $createdOrder = null;
            $orderAttributes = [
                'checkout_request_id' => $checkoutRequestId,
                'order_type' => $orderType,
                'grab_order_code' => $grabOrderCode,
                'rider_code' => $grabRiderCode,
                'customer_name' => $request->customer_name ?? null,
                'shift_id' => $shiftId,
                'cashier_name' => $request->cashier_name ?: ($request->user()?->name ?? 'Cashier'),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'discount_type' => $computed['discount_type'],
                'discount_label' => $computed['discount_label'],
                'discount_id_number' => $discountIdNumber,
                'total' => $total,
                'tax_name' => $computed['tax_name'],
                'tax_rate' => $computed['tax_rate'],
                'tax_amount' => $computed['tax_amount'],
                'vatable_sales' => $computed['vatable_sales'],
                'vat_exempt_sales' => $computed['vat_exempt_sales'],
                'zero_rated_sales' => $computed['zero_rated_sales'],
                'status' => $amountPaid >= $total ? 'completed' : ($amountPaid > 0 ? 'partially_paid' : 'pending'),
                'payment_status' => $amountPaid >= $total ? 'paid' : ($amountPaid > 0 ? 'partially_paid' : 'unpaid'),
                'notes' => $request->notes,
            ];

            if ($heldOrderToFinalize) {
                $orderNumber = $heldOrderToFinalize->order_number;
                $heldOrderToFinalize->update($orderAttributes);
                $heldOrderToFinalize->orderItems()->delete();
                $createdOrder = $heldOrderToFinalize->fresh();
            } else {
                $createdOrder = null;
                for ($attempt = 0; $attempt < 10; $attempt++) {
                    $orderNumber = Order::generateOrderNumber($isGrab ? 'GB-' : 'ORD-');

                    try {
                        $createdOrder = Order::create(['order_number' => $orderNumber] + $orderAttributes);
                        break;
                    } catch (QueryException $exception) {
                        if ($exception->getCode() !== '23000') {
                            throw $exception;
                        }

                        $existingOrder = Order::query()
                            ->where('checkout_request_id', $checkoutRequestId)
                            ->first();
                        if ($existingOrder) {
                            $createdOrder = $existingOrder;

                            return;
                        }
                    }
                }
            }

            if (! $createdOrder) {
                throw new \RuntimeException('Unable to create an order after multiple unique-number collisions.');
            }

            if ($discount > 0) {
                AuditService::logFromUser($discountAuthorizedBy, 'discount_applied', 'POS', [
                    'order_number' => $createdOrder->order_number,
                    'discount_type' => $computed['discount_type'],
                    'discount_label' => $computed['discount_label'],
                    'discount_amount' => $discount,
                    'discount_id_number' => $discountIdNumber,
                    'applied_at' => now()->toIso8601String(),
                ], $createdOrder);
            }

            // ── Order items ──────────────────────────────────────────────
            foreach ($itemsData as $d) {
                $orderItem = OrderItem::create([
                    'order_id' => $createdOrder->id,
                    'product_id' => $d['size']->product_id,
                    'product_size_id' => $d['size']->id,
                    'quantity' => $d['qty'],
                    'unit_price' => $d['unitPrice'],
                    'subtotal' => $d['subtotal'],
                    'comment' => $d['comment'],
                    'assigned_to' => $d['assignedTo'],
                    'payable_total' => $d['payableTotal'],
                    'status' => 'active',
                ]);

                // Add-ons
                foreach ($d['addonIds'] as $addonId) {
                    $addon = ProductAddon::find($addonId);
                    if ($addon) {
                        $orderItem->addons()->create([
                            'product_addon_id' => $addonId,
                            'price' => $addon->price,
                        ]);
                    }
                }
            }

            $portionIdsByPayer = collect();
            $savePortions = $splitPortions->isNotEmpty()
                && ($request->boolean('split_payment') || ($participants->isNotEmpty() && $amountPaid < $total));
            if ($savePortions) {
                foreach ($splitPortions as $portion) {
                    $savedPortion = $createdOrder->paymentPortions()->create([
                        'payer_name' => $portion['payer_name'],
                        'due_amount' => $portion['amount'],
                        'amount_paid' => 0,
                        'status' => 'pending',
                    ]);
                    $portionIdsByPayer->put($portion['payer_name'], $savedPortion->id);
                }
            } elseif ($request->boolean('split_payment') && $request->filled('person_name') && $amountPaid > 0) {
                $savedPortion = $createdOrder->paymentPortions()->create([
                    'payer_name' => trim((string) $request->input('person_name')),
                    'due_amount' => $amountPaid,
                    'amount_paid' => 0,
                    'status' => 'pending',
                ]);
                $portionIdsByPayer->put(trim((string) $request->input('person_name')), $savedPortion->id);
            }

            // ── Payment ──────────────────────────────────────────────────
            $user = $request->user();
            $performedBy = $user?->name ?? $request->cashier_name ?? 'Cashier';
            $performedRole = $user?->role ?? 'cashier';

            if ($savingUnpaidSplitOrder) {
                AuditService::logFromUser($user, 'split_order_saved', 'POS', [
                    'order_number' => $createdOrder->order_number,
                    'cashier_name' => $request->cashier_name,
                    'total' => $total,
                    'items' => count($itemsData),
                ], $createdOrder);
            } elseif ($hasStagedPayments) {
                foreach ($stagedPayments as $payment) {
                    $method = $payment['method'];
                    $paymentAmount = round((float) $payment['amount_paid'], 2);
                    $amountReceived = $method === 'cash'
                        ? round((float) $payment['amount_received'], 2)
                        : $paymentAmount;
                    $referenceNumber = trim($payment['reference_number'] ?? '');

                    Payment::create([
                        'order_id' => $createdOrder->id,
                        'portion_id' => $portionIdsByPayer->get(trim($payment['person_name'] ?? '')),
                        'shift_id' => $shiftId,
                        'method' => in_array($method, ['grab', 'grabfood']) ? 'grabfood' : $method,
                        'amount_received' => $amountReceived,
                        'amount_paid' => $paymentAmount,
                        'change_amount' => $method === 'cash' ? max(0, $amountReceived - $paymentAmount) : 0,
                        'reference_number' => $referenceNumber !== ''
                            ? strtoupper($referenceNumber)
                            : ($isGrab && in_array($method, ['grab', 'grabfood']) ? $grabOrderCode : null),
                        'status' => 'paid',
                        'customer_name' => trim($payment['person_name'] ?? '') ?: null,
                        'comment' => trim($payment['comment'] ?? '') ?: null,
                    ]);
                }

                AuditService::logFromUser($user, 'completed_order', 'POS', [
                    'order_number' => $orderNumber,
                    'cashier_name' => $request->cashier_name,
                    'total' => $total,
                    'items' => count($itemsData),
                    'payments' => $stagedPayments->count(),
                    'payment_total' => $amountPaid,
                ], $createdOrder);
            } else {
                $paymentMethod = $isGrabPayment ? 'grabfood' : $request->payment_method;
                $amountReceived = ($request->payment_method === 'cash') ? (float) ($request->amount_received ?? $total) : $total;
                $amountReceived = ($request->payment_method === 'cash') ? $amountReceived : $amountPaid;
                $change = ($request->payment_method === 'cash') ? max(0, $amountReceived - $amountPaid) : 0;

                $posPayment = Payment::create([
                    'order_id' => $createdOrder->id,
                    'portion_id' => $portionIdsByPayer->get(trim((string) $request->input('person_name'))),
                    'shift_id' => $shiftId,
                    'method' => $paymentMethod,
                    'amount_received' => $amountReceived,
                    'amount_paid' => $amountPaid,
                    'change_amount' => $change,
                    'reference_number' => $isGrab ? ($grabOrderCode ?? $request->reference_number) : ($request->payment_method !== 'cash' ? strtoupper(trim($request->reference_number ?? '')) : null),
                    'status' => 'paid',
                    'customer_name' => $request->input('person_name'),
                    'comment' => $request->input('payment_comment'),
                    'request_id' => $request->input('payment_request_id'),
                ]);

                if ($request->boolean('split_payment')) {
                    AuditService::logFromUser($user, 'order_payment_recorded', 'Orders', [
                        'order_number' => $createdOrder->order_number,
                        'payment_id' => $posPayment->id,
                        'payer' => trim((string) $request->person_name),
                        'amount_paid' => $amountPaid,
                        'remaining_balance' => $createdOrder->remainingBalance(),
                        'payment_method' => $paymentMethod,
                        'payment_status' => 'paid',
                    ], $posPayment);
                }
                AuditService::logFromUser($user, $request->boolean('split_payment') ? 'split_order_created_with_payment' : 'completed_order', 'POS', [
                    'order_number' => $orderNumber,
                    'cashier_name' => $request->cashier_name,
                    'total' => $total,
                    'items' => count($itemsData),
                    'payment' => $request->payment_method,
                ], $createdOrder);
            }

            foreach ($createdOrder->paymentPortions as $portion) {
                $portionPaid = (float) $createdOrder->payments()
                    ->where('portion_id', $portion->id)
                    ->where('status', 'paid')
                    ->sum('amount_paid');
                $portion->update([
                    'amount_paid' => $portionPaid,
                    'status' => $portionPaid <= 0 ? 'pending' : ($portionPaid >= (float) $portion->due_amount ? 'paid' : 'partially_paid'),
                ]);
            }

            // ── Inventory deduction (for all order types) ─────────────────
            $createdOrder->load('orderItems');
            InventoryService::deductFromSale($createdOrder, $performedBy, $performedRole);
        }, 3);

        if (! $createdOrder) {
            throw new \RuntimeException('Order could not be created.');
        }

        $successMessage = $createdOrder->isPayable() && $createdOrder->remainingBalance() > 0
            ? 'Payment recorded. Balance due: ₱'.number_format($createdOrder->remainingBalance(), 2).'.'
            : 'Order completed successfully!';

        if ($request->wantsJson()) {
            $createdOrder->refresh();

            return response()->json([
                'success' => true,
                'order' => [
                    'id' => $createdOrder->id,
                    'order_number' => $createdOrder->order_number,
                    'customer_name' => $createdOrder->customer_name,
                    'order_type' => $createdOrder->order_type,
                    'total' => (float) $createdOrder->total,
                    'total_paid' => $createdOrder->paidAmount(),
                    'remaining_balance' => $createdOrder->remainingBalance(),
                    'status' => $createdOrder->status,
                    'payment_status' => $createdOrder->payment_status,
                    'payment_portions' => $createdOrder->paymentPortions()->get()->map(fn ($portion) => [
                        'payer_name' => $portion->payer_name,
                        'due_amount' => (float) $portion->due_amount,
                        'amount_paid' => (float) $portion->amount_paid,
                        'remaining_balance' => max(0, round((float) $portion->due_amount - (float) $portion->amount_paid, 2)),
                        'status' => $portion->status,
                    ])->values(),
                ],
            ], 201);
        }

        return redirect()->route('pos.success', ['order' => $createdOrder])
            ->with('success', $successMessage);
    }

    public function hold(Request $request)
    {
        $activeShift = $request->user()
            ? CashierShift::activeForUser($request->user()->id)
            : null;
        if (! $activeShift) {
            return response()->json(['message' => 'Start a cashier shift before saving an order.'], 422);
        }

        $request->validate([
            'checkout_request_id' => 'nullable|uuid',
            'order_type' => 'nullable|string|in:dine_in,take_out,grab',
            'grab_order_code' => 'required_if:order_type,grab|nullable|string|max:100',
            'rider_code' => 'required_if:order_type,grab|nullable|string|max:100',
            'customer_name' => 'nullable|string|max:150',
            'items' => 'required|array|min:1',
            'items.*.product_size_id' => 'required|exists:product_sizes,id',
            'items.*.quantity' => 'required|integer|min:1|max:999',
            'items.*.comment' => 'nullable|string|max:255',
            'items.*.assigned_to' => 'nullable|string|max:150',
            'items.*.addon_ids' => 'nullable|array',
            'items.*.addon_ids.*' => [
                Rule::exists('product_addons', 'id')->where('status', 'active'),
            ],
            'discount' => 'nullable|numeric|decimal:0,2|min:0',
            'discount_type' => 'nullable|string|in:none,senior,pwd,custom',
            'discount_label' => 'nullable|string|max:100',
            'discount_id_number' => 'required_if:discount_type,senior,pwd|nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'held_order_id' => 'nullable|exists:orders,id',
        ]);

        foreach ($request->input('items', []) as $index => $item) {
            $addonIds = $item['addon_ids'] ?? [];
            if (count($addonIds) !== count(array_unique($addonIds))) {
                throw ValidationException::withMessages([
                    "items.$index.addon_ids" => 'An add-on can only be selected once per order item.',
                ]);
            }
        }

        $request->merge(['cashier_name' => $request->user()->name]);
        $orderType = strtolower(trim($request->input('order_type', 'dine_in')));
        $isGrab = $orderType === 'grab';
        $checkoutRequestId = $request->input('checkout_request_id') ?: (string) Str::uuid();
        if ($isGrab && (blank(trim((string) $request->grab_order_code)) || blank(trim((string) $request->rider_code)))) {
            throw ValidationException::withMessages([
                'grab_order_code' => 'Grab orders require both a Grab order code and rider code.',
            ]);
        }

        $createdOrder = null;

        DB::transaction(function () use ($request, $orderType, $isGrab, $checkoutRequestId, &$createdOrder) {
            $activeShift = CashierShift::lockActiveForUser($request->user()->id);
            $existingOrder = Order::query()
                ->where('checkout_request_id', $checkoutRequestId)
                ->first();
            if ($existingOrder) {
                if (! $existingOrder->shift()->where('user_id', $request->user()->id)->exists()) {
                    throw ValidationException::withMessages([
                        'checkout_request_id' => 'This save request belongs to a different cashier.',
                    ]);
                }
                $createdOrder = $existingOrder;

                return;
            }

            $heldOrderToReplace = null;
            if ($request->filled('held_order_id')) {
                $heldOrderToReplace = Order::query()->lockForUpdate()->find($request->held_order_id);
                if (! $heldOrderToReplace
                    || $heldOrderToReplace->status !== 'held') {
                    throw ValidationException::withMessages([
                        'held_order_id' => 'The saved ticket is no longer available.',
                    ]);
                }
                Gate::authorize('view', $heldOrderToReplace);
            }

            $orderNumber = $heldOrderToReplace?->order_number
                ?? Order::generateOrderNumber($isGrab ? 'GB-' : 'ORD-');
            $subtotal = 0;
            $itemsData = [];

            foreach ($request->items as $item) {
                $size = ProductSize::whereKey($item['product_size_id'])
                    ->where('status', 'active')
                    ->whereHas('product', fn ($query) => $query
                        ->where('status', 'active')
                        ->whereHas('category', fn ($category) => $category->where('status', 'active')))
                    ->with('product')
                    ->firstOrFail();
                $qty = (int) $item['quantity'];
                $unitPrice = $isGrab ? $size->getGrabPrice() : (float) $size->price;
                $lineTotal = $unitPrice * $qty;

                $addonTotal = 0;
                $addonIds = $item['addon_ids'] ?? [];
                if (! empty($addonIds)) {
                    $addonTotal = ProductAddon::whereIn('id', $addonIds)->sum('price') * $qty;
                }

                $subtotal += $lineTotal + $addonTotal;

                $itemsData[] = [
                    'size' => $size,
                    'qty' => $qty,
                    'unitPrice' => $unitPrice + (empty($addonIds) ? 0 : ProductAddon::whereIn('id', $addonIds)->sum('price')),
                    'subtotal' => $lineTotal + $addonTotal,
                    'comment' => ! empty($item['comment']) ? trim($item['comment']) : null,
                    'assignedTo' => ! empty($item['assigned_to']) ? trim($item['assigned_to']) : null,
                    'addonIds' => $addonIds,
                ];
            }

            $discountType = strtolower(trim($request->discount_type ?? 'none'));
            $rawDiscount = $discountType === 'custom' ? (float) ($request->discount ?? 0) : 0.0;
            if ($rawDiscount > $subtotal) {
                throw ValidationException::withMessages([
                    'discount' => 'Discount cannot exceed the order subtotal.',
                ]);
            }
            $isStatutoryDiscount = in_array($discountType, ['senior', 'pwd'], true);
            if ($rawDiscount > 0 && ! $isStatutoryDiscount && ! $request->user()->canAuthorize()) {
                throw ValidationException::withMessages([
                    'discount' => 'Only managers and owners can apply discounts.',
                ]);
            }
            $taxSetting = TaxSetting::current();
            $computed = $taxSetting->computeOrder($subtotal, $discountType, $rawDiscount);
            if ($rawDiscount > 0 && $computed['total'] <= 0) {
                throw ValidationException::withMessages([
                    'discount' => 'Discount must leave a positive order total.',
                ]);
            }
            $discountIdNumber = in_array($computed['discount_type'], ['senior', 'pwd'], true)
                ? trim((string) $request->input('discount_id_number'))
                : null;

            $createdOrder = null;
            for ($attempt = 0; $attempt < 10; $attempt++) {
                $orderNumber = Order::generateOrderNumber($isGrab ? 'GB-' : 'ORD-');

                try {
                    $heldAttributes = [
                        'checkout_request_id' => $checkoutRequestId,
                        'order_type' => $orderType,
                        'grab_order_code' => $isGrab ? $request->grab_order_code : null,
                        'rider_code' => $isGrab ? $request->rider_code : null,
                        'customer_name' => $request->customer_name,
                        'shift_id' => $activeShift->id,
                        'cashier_name' => $request->cashier_name ?: ($request->user()?->name ?? 'Cashier'),
                        'subtotal' => $subtotal,
                        'discount' => $computed['discount'],
                        'discount_type' => $computed['discount_type'],
                        'discount_label' => $computed['discount_label'],
                        'discount_id_number' => $discountIdNumber,
                        'total' => $computed['total'],
                        'tax_name' => $computed['tax_name'],
                        'tax_rate' => $computed['tax_rate'],
                        'tax_amount' => $computed['tax_amount'],
                        'vatable_sales' => $computed['vatable_sales'],
                        'vat_exempt_sales' => $computed['vat_exempt_sales'],
                        'zero_rated_sales' => $computed['zero_rated_sales'],
                        'status' => 'held',
                        'payment_status' => 'unpaid',
                        'held_at' => now(),
                        'is_pinned' => false,
                        'notes' => $request->notes,
                    ];
                    if ($heldOrderToReplace) {
                        $heldOrderToReplace->update($heldAttributes);
                        $heldOrderToReplace->orderItems()->delete();
                        $createdOrder = $heldOrderToReplace->fresh();
                    } else {
                        $createdOrder = Order::create([
                            'order_number' => $orderNumber,
                            ...$heldAttributes,
                        ]);
                    }
                    break;
                } catch (QueryException $exception) {
                    if ($exception->getCode() !== '23000') {
                        throw $exception;
                    }

                    $existingOrder = Order::query()
                        ->where('checkout_request_id', $checkoutRequestId)
                        ->first();
                    if ($existingOrder) {
                        $createdOrder = $existingOrder;
                        break;
                    }
                }
            }

            if (! $createdOrder) {
                throw new \RuntimeException('Unable to create a held order after multiple unique-number collisions.');
            }

            foreach ($itemsData as $d) {
                $orderItem = OrderItem::create([
                    'order_id' => $createdOrder->id,
                    'product_id' => $d['size']->product_id,
                    'product_size_id' => $d['size']->id,
                    'quantity' => $d['qty'],
                    'unit_price' => $d['unitPrice'],
                    'subtotal' => $d['subtotal'],
                    'comment' => $d['comment'],
                    'assigned_to' => $d['assignedTo'],
                    'status' => 'active',
                ]);

                foreach ($d['addonIds'] as $addonId) {
                    $addon = ProductAddon::find($addonId);
                    if ($addon) {
                        $orderItem->addons()->create([
                            'product_addon_id' => $addonId,
                            'price' => $addon->price,
                        ]);
                    }
                }
            }

            if ($createdOrder->wasRecentlyCreated || $heldOrderToReplace) {
                AuditService::logFromUser($request->user(), 'held_order', 'POS', [
                    'order_number' => $createdOrder->order_number,
                    'cashier_name' => $createdOrder->cashier_name,
                    'total' => $createdOrder->total,
                    'items' => count($itemsData),
                ], $createdOrder);
            }
        }, 3);

        return response()->json([
            'success' => true,
            'message' => "Order #{$createdOrder->order_number} saved successfully.",
            'order' => $createdOrder->load(['orderItems.product', 'orderItems.size', 'orderItems.addons.addon']),
        ]);
    }

    public function heldOrders()
    {
        $orders = Order::query()
            ->where(function ($query) {
                $query->where('status', 'held')
                    ->orWhereIn('status', ['pending', 'partially_paid']);
            })
            ->with([
                'orderItems.product',
                'orderItems.size',
                'orderItems.addons.addon',
                'paymentPortions',
            ])
            ->latest()
            ->get()
            ->filter(fn (Order $order) => Gate::allows('view', $order)
                && ($order->status === 'held' || $order->remainingBalance() > 0))
            ->map(fn (Order $order) => array_merge($order->toArray(), [
                'saved_order_type' => $order->status === 'held' ? 'held' : 'payable',
                'total_paid' => $order->paidAmount(),
                'remaining_balance' => $order->remainingBalance(),
            ]))
            ->values();

        return response()->json($orders);
    }

    public function togglePin(Order $order)
    {
        Gate::authorize('view', $order);

        if ($order->status !== 'held') {
            return response()->json(['error' => 'Only saved orders can be pinned.'], 422);
        }

        $order->update(['is_pinned' => ! $order->is_pinned]);

        return response()->json([
            'success' => true,
            'is_pinned' => $order->is_pinned,
            'message' => $order->is_pinned ? 'Order pinned to top.' : 'Order unpinned.',
        ]);
    }

    public function resumeHeld(Order $order)
    {
        Gate::authorize('view', $order);

        $activeShift = request()->user()
            ? CashierShift::activeForUser(request()->user()->id)
            : null;
        if (! $activeShift) {
            return response()->json(['message' => 'Start a cashier shift before resuming an order.'], 422);
        }

        if ($order->status !== 'held') {
            return response()->json(['error' => 'Only saved orders can be opened.'], 422);
        }

        $payload = DB::transaction(function () use ($order) {
            CashierShift::lockActiveForUser(request()->user()->id);
            $lockedOrder = Order::query()->lockForUpdate()->find($order->id);
            if (! $lockedOrder || $lockedOrder->status !== 'held') {
                throw ValidationException::withMessages(['order' => 'Only saved orders can be opened.']);
            }

            $lockedOrder->load(['orderItems.product', 'orderItems.size', 'orderItems.addons.addon']);

            $cartItems = $lockedOrder->orderItems->map(function ($item) use ($lockedOrder) {
                $addons = $item->addons->map(fn ($orderItemAddon) => [
                    'id' => $orderItemAddon->product_addon_id,
                    'name' => $orderItemAddon->addon?->name ?? 'Addon',
                    'price' => (float) ($orderItemAddon->addon?->price ?? $orderItemAddon->price),
                ])->values();
                $regularPrice = (float) ($item->size?->price ?? $item->unit_price);
                $grabPrice = (float) ($item->size?->getGrabPrice() ?? $regularPrice);
                $basePrice = $lockedOrder->order_type === 'grab' ? $grabPrice : $regularPrice;

                return [
                    'key' => "held-{$item->id}",
                    'product_id' => $item->product_id,
                    'product_size_id' => $item->product_size_id,
                    'name' => $item->product?->name ?? 'Unknown',
                    'size' => $item->size?->size_name ?? 'Regular',
                    'price' => $basePrice,
                    'regular_price' => $regularPrice,
                    'grab_price' => $grabPrice,
                    'unit_price' => $basePrice + $addons->sum('price'),
                    'qty' => $item->quantity,
                    'comment' => $item->comment,
                    'assigned_to' => $item->assigned_to,
                    'addons' => $addons->all(),
                ];
            });

            $payload = [
                'held_order_id' => $lockedOrder->id,
                'order_number' => $lockedOrder->order_number,
                'order_type' => $lockedOrder->order_type ?? 'dine_in',
                'grab_order_code' => $lockedOrder->grab_order_code,
                'rider_code' => $lockedOrder->rider_code,
                'customer_name' => $lockedOrder->customer_name,
                'cashier_name' => $lockedOrder->cashier_name,
                'discount_type' => $lockedOrder->discount_type,
                'discount' => (float) $lockedOrder->discount,
                'discount_id_number' => $lockedOrder->discount_id_number,
                'notes' => $lockedOrder->notes,
                'cart' => $cartItems,
            ];

            return $payload;
        }, 3);

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }

    public function discardHeld(Request $request, Order $order)
    {
        Gate::authorize('view', $order);

        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);
        $reason = trim($validated['reason']);
        if (mb_strlen($reason) < 5) {
            throw ValidationException::withMessages([
                'reason' => 'The reason must contain at least 5 non-whitespace characters.',
            ]);
        }

        $requester = $request->user();
        $voidLog = DB::transaction(function () use ($requester, $order, $reason) {
            $shift = CashierShift::lockActiveForUser($requester->id);
            $lockedOrder = Order::query()->lockForUpdate()->find($order->id);
            if (! $lockedOrder) {
                throw ValidationException::withMessages(['order' => 'Only saved or unpaid orders can be voided.']);
            }

            if ($lockedOrder->paidAmount() > 0 || $lockedOrder->payments()->where('status', 'paid')->exists()) {
                throw ValidationException::withMessages([
                    'order' => 'Orders with recorded payments cannot be voided. Please issue a refund or cancellation instead.',
                ]);
            }

            if (! in_array($lockedOrder->status, ['held', 'pending'], true)) {
                throw ValidationException::withMessages(['order' => 'Only saved or unpaid orders can be voided.']);
            }

            $orderItemIds = $lockedOrder->orderItems()->pluck('id');
            $inventoryDeducted = $lockedOrder->status === 'pending' && InventoryTransaction::query()
                ->where('type', 'sales_consumption')
                ->where(function ($query) use ($lockedOrder, $orderItemIds) {
                    $query->where(function ($orderQuery) use ($lockedOrder) {
                        $orderQuery->where('reference_type', Order::class)
                            ->where('reference_id', $lockedOrder->id);
                    })->orWhere(function ($itemQuery) use ($orderItemIds) {
                        $itemQuery->where('reference_type', OrderItem::class)
                            ->whereIn('reference_id', $orderItemIds);
                    });
                })
                ->exists();

            if ($inventoryDeducted) {
                $lockedOrder->load(['orderItems.product', 'orderItems.size', 'orderItems.addons.addon']);
                InventoryService::restoreFromVoid($lockedOrder, $requester->name, $requester->role);
            }

            $lockedOrder->update([
                'status' => 'voided',
                'payment_status' => 'voided',
            ]);
            $lockedOrder->orderItems()->where('status', 'active')->update(['status' => 'voided']);
            $lockedOrder->paymentPortions()->update(['status' => 'voided']);

            return VoidLog::create([
                'order_id' => $lockedOrder->id,
                'shift_id' => $shift->id,
                'amount' => (float) $lockedOrder->total,
                'void_type' => 'order',
                'reason' => $reason,
                'cashier_name' => $lockedOrder->cashier_name,
                'requested_by_user_id' => $requester->id,
                'requested_by' => $requester->name,
                'requested_role' => $requester->role,
                'authorized_user_id' => null,
                'authorized_by' => null,
                'authorized_role' => null,
                'stock_restored' => $inventoryDeducted,
                'voided_at' => now(),
            ]);
        }, 3);

        AuditService::logFromUser(
            $requester,
            'voided_saved_ticket',
            'POS',
            [
                'order_number' => $voidLog->order?->order_number,
                'reason' => $reason,
                'stock_restored' => $voidLog->stock_restored,
                'authorization_required' => false,
            ],
            $voidLog
        );

        return response()->json([
            'success' => true,
            'message' => "Saved ticket #{$voidLog->order?->order_number} voided.",
        ]);
    }

    public function success(Order $order)
    {
        Gate::authorize('view', $order);
        $order->load(['orderItems.product', 'orderItems.size', 'orderItems.addons.addon', 'payments']);

        return view('pos.success', compact('order'));
    }
}
