<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Debt;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductSize;
use App\Models\TaxSetting;
use App\Models\User;
use App\Services\AuditService;
use App\Services\InventoryService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PosController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::where('status', 'active')
            ->with(['products' => function ($q) {
                $q->where('status', 'active')
                    ->with(['sizes' => fn ($q2) => $q2
                        ->where('status', 'active')
                        ->with(['recipe.recipeIngredients.ingredient'])
                        ->orderBy('price')]);
            }])
            ->get();

        $addons = ProductAddon::where('status', 'active')->get();
        $taxSetting = TaxSetting::current();
        $activeShift = $request->user() ? CashierShift::activeForUser($request->user()->id) : null;

        return view('pos.index', compact('categories', 'addons', 'taxSetting', 'activeShift'));
    }

    public function products(Request $request)
    {
        $categoryId = $request->get('category_id');
        $products = Product::where('status', 'active')
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->with(['sizes' => fn ($q) => $q->where('status', 'active')->orderBy('price'), 'category'])
            ->get();

        return response()->json($products);
    }

    public function sizes(Product $product)
    {
        $sizes = $product->sizes()->where('status', 'active')->orderBy('price')->get();

        return response()->json($sizes);
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_type' => 'nullable|string|in:dine_in,take_out,grab',
            'grab_order_code' => 'required_if:order_type,grab|nullable|string|max:100',
            'rider_code' => 'required_if:order_type,grab|nullable|string|max:100',
            'customer_name' => 'nullable|string|max:150',
            'cashier_name' => 'required|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.product_size_id' => 'required|exists:product_sizes,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.comment' => 'nullable|string|max:255',
            'items.*.assigned_to' => 'nullable|string|max:150',
            'items.*.addon_ids' => 'nullable|array',
            'items.*.addon_ids.*' => [
                Rule::exists('product_addons', 'id')->where('status', 'active'),
            ],
            'discount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|string|in:none,senior,pwd,custom',
            'discount_label' => 'nullable|string|max:100',
            'discount_id_number' => 'required_if:discount_type,senior,pwd|nullable|string|max:100',
            'authorizer_email' => 'nullable|email',
            'authorizer_password' => 'nullable|string',
            'payment_method' => 'required|in:cash,online,pay_later,grabfood,grab',
            'amount_received' => 'required_unless:payment_method,pay_later,grabfood,grab|nullable|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0.01',
            'person_name' => 'nullable|string|max:150',
            'payment_comment' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:100',
            'payments' => 'nullable|array|min:1',
            'payments.*.method' => 'required|in:cash,online,grabfood,grab',
            'payments.*.amount_paid' => 'required|numeric|min:0.01',
            'payments.*.amount_received' => 'nullable|numeric|min:0',
            'payments.*.person_name' => 'nullable|string|max:150',
            'payments.*.reference_number' => 'nullable|string|max:100',
            'payments.*.comment' => 'nullable|string|max:255',
            'held_order_id' => 'nullable|exists:orders,id',
            // Pay Later fields
            'debt_customer_name' => 'required_if:payment_method,pay_later|nullable|string|max:150',
            'debt_customer_phone' => 'nullable|string|max:30',
            'debt_due_date' => 'nullable|date|after_or_equal:today',
            'debt_notes' => 'nullable|string|max:500',
        ]);

        foreach ($request->input('items', []) as $index => $item) {
            $addonIds = $item['addon_ids'] ?? [];
            if (count($addonIds) !== count(array_unique($addonIds))) {
                throw ValidationException::withMessages([
                    "items.$index.addon_ids" => 'An add-on can only be selected once per order item.',
                ]);
            }
        }

        $orderType = strtolower(trim($request->order_type ?? 'dine_in'));
        $isGrab = $orderType === 'grab';
        $isGrabPayment = in_array($request->payment_method, ['grabfood', 'grab']);
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

        // Online payments must provide a reference number
        if (! $request->has('payments') && $request->payment_method === 'online' && empty(trim($request->reference_number ?? ''))) {
            throw ValidationException::withMessages([
                'reference_number' => 'A reference number is required for online payments.',
            ]);
        }

        $user = $request->user();
        $createdOrder = null;

        DB::transaction(function () use ($request, $orderType, $isGrab, $isGrabPayment, &$createdOrder) {
            $shiftId = CashierShift::lockActiveForUser($request->user()->id)->id;
            $grabOrderCode = $isGrab ? strtoupper(trim((string) $request->grab_order_code)) : null;
            $grabRiderCode = $isGrab ? strtoupper(trim((string) $request->rider_code)) : null;

            // If checking out an order that was previously held, remove the held order
            if ($request->filled('held_order_id')) {
                Order::where('id', $request->held_order_id)->where('status', 'held')->delete();
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
            if (($rawDiscount > 0 || $isStatutoryDiscount) && ! $isStatutoryDiscount) {
                $hasAuthPrivilege = $request->user()?->canAuthorize() ?? false;
                if (! $hasAuthPrivilege) {
                    $authorized = false;
                    if ($request->filled('authorizer_email') && $request->filled('authorizer_password')) {
                        $authMgr = User::where('email', $request->authorizer_email)->where('status', 'active')->first();
                        if ($authMgr && Hash::check($request->authorizer_password, $authMgr->password) && $authMgr->canAuthorize()) {
                            $authorized = true;
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

            $discount = $computed['discount'];
            $total = $computed['total'];

            $stagedPayments = collect($request->input('payments', []));
            $hasStagedPayments = $stagedPayments->isNotEmpty();
            $isPayLater = ! $hasStagedPayments && $request->payment_method === 'pay_later';
            $amountPaid = $isPayLater ? 0 : round($hasStagedPayments
                ? $stagedPayments->sum(fn ($payment) => (float) $payment['amount_paid'])
                : (float) ($request->amount_paid ?? $total), 2);
            $amountPaid = round($amountPaid, 2);
            if (! $isPayLater && ($amountPaid <= 0 || $amountPaid > $total)) {
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
            if (! $hasStagedPayments && $participants->isNotEmpty() && $amountPaid < $total && ! $request->filled('person_name')) {
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
                            $paymentsByPerson[$personName] = round(($paymentsByPerson[$personName] ?? 0) + $paymentAmount, 2);
                        }
                    }
                }

                foreach ($paymentsByPerson as $personName => $personPaid) {
                    $personDue = collect($itemsData)
                        ->where('assignedTo', $personName)
                        ->sum('payableTotal');
                    if ($personPaid > $personDue) {
                        throw ValidationException::withMessages([
                            'payments' => "Payments for $personName exceed their assigned-product total.",
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
            for ($attempt = 0; $attempt < 10; $attempt++) {
                $orderNumber = Order::generateOrderNumber($isGrab ? 'GB-' : 'ORD-');

                try {
                    $createdOrder = Order::create([
                        'order_number' => $orderNumber,
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
                        'discount_id_number' => $request->input('discount_id_number'),
                        'total' => $total,
                        'tax_name' => $computed['tax_name'],
                        'tax_rate' => $computed['tax_rate'],
                        'tax_amount' => $computed['tax_amount'],
                        'vatable_sales' => $computed['vatable_sales'],
                        'vat_exempt_sales' => $computed['vat_exempt_sales'],
                        'zero_rated_sales' => $computed['zero_rated_sales'],
                        'status' => $isPayLater ? 'pay_later' : ($amountPaid >= $total ? 'completed' : 'partially_paid'),
                        'notes' => $request->notes,
                    ]);
                    break;
                } catch (QueryException $exception) {
                    if ($exception->getCode() !== '23000') {
                        throw $exception;
                    }
                }
            }

            if (! $createdOrder) {
                throw new \RuntimeException('Unable to create an order after multiple unique-number collisions.');
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

            // ── Payment or Debt ───────────────────────────────────────────
            $user = $request->user();
            $performedBy = $user?->name ?? $request->cashier_name ?? 'Cashier';
            $performedRole = $user?->role ?? 'cashier';

            if ($isPayLater) {
                Debt::create([
                    'order_id' => $createdOrder->id,
                    'customer_name' => $request->debt_customer_name,
                    'customer_phone' => $request->debt_customer_phone ?? null,
                    'original_amount' => $total,
                    'amount_paid' => 0,
                    'balance' => $total,
                    'due_date' => $request->debt_due_date ?? null,
                    'status' => 'pending',
                    'notes' => $request->debt_notes ?? null,
                    'created_by' => $performedBy,
                ]);

                AuditService::logFromUser($user, 'pay_later_order', 'POS', [
                    'order_number' => $orderNumber,
                    'cashier_name' => $request->cashier_name,
                    'total' => $total,
                    'items' => count($itemsData),
                    'customer_name' => $request->debt_customer_name,
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

                Payment::create([
                    'order_id' => $createdOrder->id,
                    'shift_id' => $shiftId,
                    'method' => $paymentMethod,
                    'amount_received' => $amountReceived,
                    'amount_paid' => $amountPaid,
                    'change_amount' => $change,
                    'reference_number' => $isGrab ? ($grabOrderCode ?? $request->reference_number) : ($request->payment_method !== 'cash' ? strtoupper(trim($request->reference_number ?? '')) : null),
                    'status' => 'paid',
                    'customer_name' => $request->input('person_name'),
                    'comment' => $request->input('payment_comment'),
                ]);

                AuditService::logFromUser($user, 'completed_order', 'POS', [
                    'order_number' => $orderNumber,
                    'cashier_name' => $request->cashier_name,
                    'total' => $total,
                    'items' => count($itemsData),
                    'payment' => $request->payment_method,
                ], $createdOrder);
            }

            // ── Inventory deduction (for all order types) ─────────────────
            $createdOrder->load('orderItems');
            InventoryService::deductFromSale($createdOrder, $performedBy, $performedRole);
        });

        if (! $createdOrder) {
            throw new \RuntimeException('Order could not be created.');
        }

        return redirect()->route('pos.success', ['order' => $createdOrder])
            ->with('success', 'Order completed successfully!');
    }

    public function hold(Request $request)
    {
        $activeShift = $request->user()
            ? CashierShift::activeForUser($request->user()->id)
            : null;
        if (! $activeShift) {
            return response()->json(['message' => 'Start a cashier shift before holding an order.'], 422);
        }

        $request->validate([
            'order_type' => 'nullable|string|in:dine_in,take_out,grab',
            'grab_order_code' => 'required_if:order_type,grab|nullable|string|max:100',
            'rider_code' => 'required_if:order_type,grab|nullable|string|max:100',
            'customer_name' => 'nullable|string|max:150',
            'cashier_name' => 'required|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.product_size_id' => 'required|exists:product_sizes,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.comment' => 'nullable|string|max:255',
            'items.*.assigned_to' => 'nullable|string|max:150',
            'items.*.addon_ids' => 'nullable|array',
            'items.*.addon_ids.*' => [
                Rule::exists('product_addons', 'id')->where('status', 'active'),
            ],
            'discount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|string|in:none,senior,pwd,custom',
            'discount_label' => 'nullable|string|max:100',
            'discount_id_number' => 'required_if:discount_type,senior,pwd|nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        foreach ($request->input('items', []) as $index => $item) {
            $addonIds = $item['addon_ids'] ?? [];
            if (count($addonIds) !== count(array_unique($addonIds))) {
                throw ValidationException::withMessages([
                    "items.$index.addon_ids" => 'An add-on can only be selected once per order item.',
                ]);
            }
        }

        $orderType = strtolower(trim($request->input('order_type', 'dine_in')));
        $isGrab = $orderType === 'grab';
        if ($isGrab && (blank(trim((string) $request->grab_order_code)) || blank(trim((string) $request->rider_code)))) {
            throw ValidationException::withMessages([
                'grab_order_code' => 'Grab orders require both a Grab order code and rider code.',
            ]);
        }

        $createdOrder = null;

        DB::transaction(function () use ($request, $orderType, $isGrab, &$createdOrder) {
            $activeShift = CashierShift::lockActiveForUser($request->user()->id);

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
            $rawDiscount = (float) ($request->discount ?? 0);
            $taxSetting = TaxSetting::current();
            $computed = $taxSetting->computeOrder($subtotal, $discountType, $rawDiscount);

            $createdOrder = null;
            for ($attempt = 0; $attempt < 10; $attempt++) {
                $orderNumber = Order::generateOrderNumber($isGrab ? 'GB-' : 'ORD-');

                try {
                    $createdOrder = Order::create([
                        'order_number' => $orderNumber,
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
                        'discount_id_number' => $request->input('discount_id_number'),
                        'total' => $computed['total'],
                        'tax_name' => $computed['tax_name'],
                        'tax_rate' => $computed['tax_rate'],
                        'tax_amount' => $computed['tax_amount'],
                        'vatable_sales' => $computed['vatable_sales'],
                        'vat_exempt_sales' => $computed['vat_exempt_sales'],
                        'zero_rated_sales' => $computed['zero_rated_sales'],
                        'status' => 'held',
                        'held_at' => now(),
                        'is_pinned' => false,
                        'notes' => $request->notes,
                    ]);
                    break;
                } catch (QueryException $exception) {
                    if ($exception->getCode() !== '23000') {
                        throw $exception;
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

            AuditService::logFromUser($request->user(), 'held_order', 'POS', [
                'order_number' => $orderNumber,
                'cashier_name' => $createdOrder->cashier_name,
                'total' => $createdOrder->total,
                'items' => count($itemsData),
            ], $createdOrder);
        });

        return response()->json([
            'success' => true,
            'message' => "Order #{$createdOrder->order_number} held successfully.",
            'order' => $createdOrder->load(['orderItems.product', 'orderItems.size', 'orderItems.addons.addon']),
        ]);
    }

    public function heldOrders()
    {
        $orders = Order::where('status', 'held')
            ->with(['orderItems.product', 'orderItems.size', 'orderItems.addons.addon'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('held_at')
            ->get();

        return response()->json($orders);
    }

    public function togglePin(Order $order)
    {
        if ($order->status !== 'held') {
            return response()->json(['error' => 'Only held orders can be pinned.'], 422);
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
        $activeShift = request()->user()
            ? CashierShift::activeForUser(request()->user()->id)
            : null;
        if (! $activeShift) {
            return response()->json(['message' => 'Start a cashier shift before resuming an order.'], 422);
        }

        if ($order->status !== 'held') {
            return response()->json(['error' => 'Only held orders can be resumed.'], 422);
        }

        $payload = DB::transaction(function () use ($order) {
            CashierShift::lockActiveForUser(request()->user()->id);
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->status !== 'held') {
                throw ValidationException::withMessages(['order' => 'Only held orders can be resumed.']);
            }

            $lockedOrder->load(['orderItems.product', 'orderItems.size', 'orderItems.addons.addon']);

            $cartItems = $lockedOrder->orderItems->map(function ($item) use ($lockedOrder) {
                $addonIds = $item->addons->pluck('product_addon_id')->toArray();
                $regularPrice = (float) ($item->size?->price ?? $item->unit_price);
                $grabPrice = (float) ($item->size?->getGrabPrice() ?? $regularPrice);

                return [
                    'key' => "{$item->product_id}-{$item->product_size_id}-".implode('-', $addonIds),
                    'product_id' => $item->product_id,
                    'product_size_id' => $item->product_size_id,
                    'name' => $item->product?->name ?? 'Unknown',
                    'size' => $item->size?->size_name ?? 'Regular',
                    'price' => $lockedOrder->order_type === 'grab' ? $grabPrice : $regularPrice,
                    'regular_price' => $regularPrice,
                    'grab_price' => $grabPrice,
                    'unit_price' => (float) $item->unit_price,
                    'qty' => $item->quantity,
                    'comment' => $item->comment,
                    'assigned_to' => $item->assigned_to,
                    'addons' => $item->addons->map(fn ($a) => [
                        'id' => $a->product_addon_id,
                        'name' => $a->addon?->name ?? 'Addon',
                        'price' => (float) $a->price,
                    ])->toArray(),
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
            $lockedOrder->delete();

            return $payload;
        });

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }

    public function discardHeld(Order $order)
    {
        $orderNumber = DB::transaction(function () use ($order) {
            CashierShift::lockActiveForUser(request()->user()->id);
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->status !== 'held') {
                throw ValidationException::withMessages(['order' => 'Only held orders can be discarded.']);
            }

            $number = $lockedOrder->order_number;
            $lockedOrder->delete();

            return $number;
        });

        AuditService::logFromUser(request()->user(), 'discarded_held_order', 'POS', [
            'order_number' => $orderNumber,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Held order #{$orderNumber} discarded.",
        ]);
    }

    public function success(Order $order)
    {
        $order->load(['orderItems.product', 'orderItems.size', 'orderItems.addons.addon', 'payments']);

        return view('pos.success', compact('order'));
    }
}
