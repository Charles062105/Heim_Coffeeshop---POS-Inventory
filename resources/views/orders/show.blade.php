@extends('layouts.app')
@section('title', 'Order #' . $order->order_number)
@section('header', 'Order #' . $order->order_number)
@section('subheader', 'Settled ticket record • ' . $order->created_at->copy()->timezone(config('app.business_timezone', 'Asia/Manila'))->format('l, F d, Y \a\t h:i A'))

@section('header-badge')
    @include('components.status-badge', ['status' => $order->status])
@endsection

@section('header-actions')
    <div class="flex items-center gap-2 print:hidden">
        <button type="button" onclick="printThermalReceipt()" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-heim-800 bg-white border border-heim-200 hover:bg-heim-50 shadow-xs transition-all">
            <svg class="w-4 h-4 text-heim-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print Receipt
        </button>
        <a href="{{ route('orders.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 shadow-xs transition-all">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Orders
        </a>
    </div>
@endsection

@section('content')
<style>
    @media print {
        @page {
            size: 80mm auto;
            margin: 0;
        }
        html, body {
            width: 80mm !important;
            max-width: 80mm !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #ffffff !important;
            color: #000000 !important;
            overflow: visible !important;
            height: auto !important;
            display: block !important;
            font-family: 'Courier New', Courier, monospace !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        header, nav, aside, .sidebar-shell, #user-menu-button, .notif-badge,
        .header-actions, .brand-card, .print\:hidden, .print-hide {
            display: none !important;
        }
        main {
            padding: 0 !important;
            margin: 0 !important;
            overflow: visible !important;
            height: auto !important;
            display: block !important;
            background: transparent !important;
        }
        #thermal-receipt-print-area {
            display: block !important;
            width: 72mm !important;
            max-width: 72mm !important;
            margin: 0 auto !important;
            padding: 4mm 1mm !important;
            background: #ffffff !important;
            color: #000000 !important;
        }
        #thermal-receipt-print-area * {
            color: #000000 !important;
            box-shadow: none !important;
            text-shadow: none !important;
        }
    }
</style>

{{-- Dedicated thermal print container (strictly visible in print mode) --}}
<div id="thermal-receipt-print-area" class="hidden print:block">
    @include('orders.partials.receipt-content', ['order' => $order])
</div>

<div class="max-w-2xl mx-auto space-y-6 print:hidden">

    {{-- Main receipt card --}}
    <div class="brand-card rounded-2xl shadow-sm overflow-hidden">
        <div class="bg-heim-800 px-6 py-5 text-white flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-heim-300">Heim Coffee POS Receipt</p>
                <h1 class="text-2xl font-black font-mono mt-0.5 tracking-tight">{{ $order->order_number }}</h1>
                <div class="flex flex-wrap items-center gap-3 mt-1.5 text-xs text-heim-200">
                    <span>Cashier: <strong class="text-white">{{ $order->cashier_name }}</strong></span>
                    <span>•</span>
                    <span>{{ $order->created_at->copy()->timezone(config('app.business_timezone', 'Asia/Manila'))->format('M d, Y h:i A') }}</span>
                </div>
            </div>
            <div class="text-right">
                <span class="text-xs text-heim-300 uppercase font-semibold">Total Amount</span>
                <p class="text-2xl font-black text-white font-mono">₱{{ number_format($order->total, 2) }}</p>
            </div>
        </div>

        {{-- Items breakdown --}}
        <div class="p-6">
            <h3 class="brand-label mb-3 text-gray-500">Items Ordered</h3>
            <div class="space-y-2.5 mb-6">
                @foreach($order->orderItems as $item)
                <div class="flex items-start justify-between text-sm bg-gray-50/70 border border-gray-100 rounded-xl px-4 py-3 {{ $item->isVoided() ? 'opacity-60 bg-rose-50/30' : '' }}">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <p class="font-bold text-gray-900 {{ $item->isVoided() ? 'line-through text-gray-500' : '' }}">{{ $item->product->name ?? 'Deleted Product' }}</p>
                            @if($item->isVoided())
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 uppercase">
                                    Voided
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-heim-700 font-medium">{{ $item->size->size_name ?? 'Regular' }}</p>
                        @foreach($item->addons as $addon)
                            <p class="text-xs text-gray-400 mt-0.5">+ {{ $addon->addon->name ?? '' }} (₱{{ number_format($addon->price, 2) }})</p>
                        @endforeach
                        @if($item->comment)
                            <p class="text-xs text-amber-800 italic mt-1 font-medium bg-amber-50/70 border border-amber-200/60 rounded-md px-2 py-0.5 inline-block">
                                * Instruction: {{ $item->comment }}
                            </p>
                        @endif
                        @if($item->assigned_to)
                            <p class="text-xs text-indigo-700 mt-1 font-medium">Assigned to: {{ $item->assigned_to }}</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-gray-900 {{ $item->isVoided() ? 'line-through text-gray-400' : '' }}">₱{{ number_format($item->subtotal, 2) }}</p>
                        <p class="text-xs text-gray-400">×{{ $item->quantity }} @ ₱{{ number_format($item->unit_price, 2) }}</p>
                        @if(!$item->isVoided() && !in_array($order->status, ['voided', 'refunded', 'cancelled']))
                            <details class="relative mt-1">
                                <summary class="cursor-pointer list-none text-[11px] font-bold text-gray-500 hover:text-gray-700">More ⋮</summary>
                                <div class="absolute right-0 z-20 mt-1 rounded-lg border border-gray-200 bg-white p-1 shadow-lg">
                                    <button type="button" onclick="triggerVoidItem({{ $item->id }}, '{{ addslashes($item->product->name ?? 'Item') }}')" class="whitespace-nowrap rounded-md px-3 py-2 text-[11px] font-bold text-rose-700 hover:bg-rose-50">
                                        Void Item
                                    </button>
                                </div>
                            </details>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Totals summary --}}
            <div class="border-t border-gray-100 pt-4 space-y-2 text-sm">
                <div class="flex justify-between text-gray-500 font-medium">
                    <span>Subtotal</span>
                    <span class="text-gray-900 font-semibold font-mono">₱{{ number_format($order->subtotal, 2) }}</span>
                </div>
                @if($order->discount > 0)
                <div class="flex justify-between text-rose-600 font-medium">
                    <span>
                        Discount Applied
                        @if($order->discount_label)
                            <span class="text-xs font-normal text-gray-500">({{ $order->discount_label }})</span>
                        @endif
                    </span>
                    <span class="font-bold font-mono">−₱{{ number_format($order->discount, 2) }}</span>
                </div>
                @if($order->discount_id_number)
                <div class="flex justify-between text-xs text-gray-500">
                    <span>ID / SC / PWD No.</span>
                    <span class="font-bold text-gray-700 font-mono">{{ $order->discount_id_number }}</span>
                </div>
                @endif
                @endif

                {{-- Applicable Tax Information --}}
                <div class="py-2.5 my-2 border-y border-dashed border-gray-200 text-xs text-gray-500 space-y-1.5">
                    <div class="flex justify-between">
                        <span>VATable Sales</span>
                        <span class="font-mono">₱{{ number_format($order->vatable_sales ?? 0, 2) }}</span>
                    </div>
                    @if(($order->vat_exempt_sales ?? 0) > 0)
                    <div class="flex justify-between text-emerald-700 font-medium">
                        <span>VAT-Exempt Sales</span>
                        <span class="font-mono">₱{{ number_format($order->vat_exempt_sales, 2) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between font-medium text-gray-700">
                        <span>{{ $order->tax_name ?? 'VAT' }} ({{ number_format($order->tax_rate ?? 12, 2) }}%)</span>
                        <span class="font-mono">₱{{ number_format($order->tax_amount ?? 0, 2) }}</span>
                    </div>
                </div>

                <div class="flex justify-between font-black text-lg text-gray-900 pt-1">
                    <span>Grand Total</span>
                    <span class="text-heim-800 font-mono">₱{{ number_format($order->total, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Payment info --}}
        @if($order->payments->isNotEmpty())
        <div class="border-t border-gray-100 px-6 py-5 bg-heim-50/50">
            @if($splitPayments->isNotEmpty())
            <div class="mb-5">
                <h3 class="brand-label mb-3 text-heim-800">Split Payment Summary</h3>
                <div class="overflow-x-auto rounded-xl border border-heim-100 bg-white">
                    <table class="w-full text-sm">
                        <thead class="bg-heim-50 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-3 py-2 text-left">Person</th>
                                <th class="px-3 py-2 text-right">Items</th>
                                <th class="px-3 py-2 text-right">Assigned Total</th>
                                <th class="px-3 py-2 text-right">Paid</th>
                                <th class="px-3 py-2 text-right">Remaining</th>
                                <th class="px-3 py-2 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($splitPayments as $splitPayment)
                            <tr>
                                <td class="px-3 py-2 font-semibold text-gray-900">{{ $splitPayment['person'] }}</td>
                                <td class="px-3 py-2 text-right text-gray-600">{{ $splitPayment['items'] }}</td>
                                <td class="px-3 py-2 text-right font-mono">₱{{ number_format($splitPayment['due'], 2) }}</td>
                                <td class="px-3 py-2 text-right font-mono text-emerald-700">₱{{ number_format($splitPayment['paid'], 2) }}</td>
                                <td class="px-3 py-2 text-right font-mono {{ $splitPayment['remaining'] > 0 ? 'text-amber-700' : 'text-gray-500' }}">₱{{ number_format($splitPayment['remaining'], 2) }}</td>
                                <td class="px-3 py-2 text-center">
                                    @include('components.status-badge', ['status' => $splitPayment['status']])
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <div class="flex items-center justify-between mb-3">
                <h3 class="brand-label text-heim-800">Payment Breakdown</h3>
                <a href="{{ route('orders.receipt', $order) }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-bold text-heim-700 hover:text-heim-900 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print Slip
                </a>
            </div>
            <div class="mb-4 space-y-2">
                @foreach($order->payments as $payment)
                <div class="rounded-xl border border-heim-100 bg-white p-3">
                    <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                        <span class="font-bold text-gray-900">{{ $payment->customer_name ?: 'Order payment' }} · {{ ucfirst($payment->method) }}</span>
                        <span class="font-extrabold text-heim-700">₱{{ number_format($payment->amount_paid, 2) }}</span>
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-x-3 text-xs text-gray-500">
                        @include('components.status-badge', ['status' => $payment->status])
                        @if($payment->reference_number)<span>Ref: {{ $payment->reference_number }}</span>@endif
                        @if($payment->comment)<span>{{ $payment->comment }}</span>@endif
                        <span>{{ $payment->created_at->copy()->timezone(config('app.business_timezone', 'Asia/Manila'))->format('M d, Y h:i A') }}</span>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mb-4 flex justify-between rounded-xl border border-heim-100 bg-white px-3 py-2 text-sm">
                <span class="font-semibold text-gray-600">Paid / Remaining</span>
                <span class="font-bold text-gray-900">₱{{ number_format($order->paidAmount(), 2) }} / ₱{{ number_format($order->remainingBalance(), 2) }}</span>
            </div>
            @if($order->payment)
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Method</p>
                    <p class="font-bold capitalize text-gray-900">
                        {{ match(strtolower($order->payment->method)) {
                            'cash'   => 'Cash',
                            default  => 'Online Payment'
                        } }}
                    </p>
                </div>
                @if($order->payment->reference_number)
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Reference #</p>
                    <p class="font-bold font-mono text-heim-800 uppercase tracking-wide bg-white px-2 py-0.5 rounded border border-heim-200 inline-block text-xs">
                        {{ $order->payment->reference_number }}
                    </p>
                </div>
                @endif
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Tendered</p>
                    <p class="font-bold text-gray-900">₱{{ number_format($order->payment->amount_received, 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Change Due</p>
                    <p class="font-extrabold text-heim-700">₱{{ number_format($order->payment->change_amount, 2) }}</p>
                </div>
            </div>
            @endif
        </div>
        @endif

        @if(in_array($order->status, ['pending', 'partially_paid']) && $order->remainingBalance() > 0)
        <div class="border-t border-gray-100 px-6 py-5">
            <h3 class="brand-label mb-3 text-heim-800">Record Split / Partial Payment</h3>
            <form method="POST" action="{{ route('orders.payments.store', $order) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @csrf
                @php($assignedPeople = $order->orderItems->pluck('assigned_to')->filter()->unique()->values())
                @if($assignedPeople->isNotEmpty())
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Paying Person</label>
                    <select name="person_name" required class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                        <option value="">Select person</option>
                        @foreach($assignedPeople as $person)<option value="{{ $person }}">{{ $person }}</option>@endforeach
                    </select>
                </div>
                @else
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Paying Person (optional)</label>
                    <input name="person_name" maxlength="150" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                </div>
                @endif
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Amount to Apply (₱)</label>
                    <input name="amount_paid" type="number" min="0.01" max="{{ number_format($order->remainingBalance(), 2, '.', '') }}" step="0.01" required class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Payment Method</label>
                    <select name="payment_method" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                        <option value="cash">Cash</option><option value="online">Online</option><option value="grabfood">GrabFood</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Payment Status</label>
                    <select name="payment_status" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                        <option value="paid">Paid</option><option value="pending">Pending</option><option value="failed">Failed</option>
                    </select>
                    <p class="mt-1 text-[11px] text-gray-400">Pending or failed attempts require an online payment method.</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Cash Received (₱, if cash)</label>
                    <input name="amount_received" type="number" min="0" step="0.01" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1">Reference Number (online)</label>
                    <input name="reference_number" maxlength="100" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-600 mb-1">Payment Comment (purpose, source, or platform)</label>
                    <input name="payment_comment" maxlength="255" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <button class="brand-button">Record Payment</button>
                </div>
            </form>
        </div>
        @endif

        {{-- Refund info --}}
        @if($order->refund)
        <div class="border-t border-blue-100 px-6 py-5 bg-blue-50/60">
            <h3 class="brand-label mb-2 text-blue-900">Refund Records</h3>
            <div class="text-sm space-y-3">
                @foreach($order->refunds as $refund)
                <div class="border-b border-blue-100 pb-2 last:border-0 last:pb-0">
                    <div class="flex justify-between"><span class="text-blue-800/70">Amount · {{ ucfirst($refund->method) }}</span><span class="font-bold text-blue-800">₱{{ number_format($refund->amount, 2) }} · {{ ucfirst($refund->status) }}</span></div>
                    <div class="flex justify-between"><span class="text-blue-800/70">Authorized By</span><span class="font-semibold text-blue-900">{{ $refund->authorized_by }} ({{ $refund->authorized_role }})</span></div>
                    <div class="flex justify-between"><span class="text-blue-800/70">Reason</span><span class="text-gray-700">{{ $refund->reason }}</span></div>
                    <div class="flex justify-between"><span class="text-blue-800/70">Timestamp</span><span class="text-gray-600">{{ $refund->refunded_at ? $refund->refunded_at->copy()->timezone(config('app.business_timezone', 'Asia/Manila'))->format('M d, Y h:i A') : 'Processing' }}</span></div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Refund / Cancel / Void actions (requires manager/owner authorization via modal) --}}
    @if(!in_array($order->status, ['refunded', 'voided', 'cancelled']))
    <div class="brand-card rounded-2xl shadow-sm p-6">
        <h3 class="font-bold text-gray-900 mb-2 text-base">Order Actions</h3>
        <p class="text-xs text-gray-400 mb-4">Cashiers can request these actions. Enter an active Manager or Owner email and password to authorize refunds, cancellations, and voids.</p>

        <div class="flex flex-wrap items-center gap-3">
            @if($order->status === 'completed')
                @if($order->payments->isNotEmpty()
                    && $order->payments->contains(fn ($payment) => $payment->method === 'cash' && $payment->status === 'paid')
                    && $order->payments->every(fn ($payment) => $payment->method === 'cash'))
                <button onclick="triggerRefund()" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-xl shadow-sm transition-colors">
                    Process Refund
                </button>
                @else
                <div class="flex flex-wrap items-center gap-2">
                    <button disabled title="Only cash orders can be refunded" class="px-5 py-2.5 bg-gray-100 border border-gray-200 text-gray-400 text-sm font-semibold rounded-xl cursor-not-allowed">
                        Refund Unavailable
                    </button>
                    <span class="text-xs text-amber-800 bg-amber-50 border border-amber-200 px-3 py-1.5 rounded-xl font-medium">
                        Only orders paid entirely in cash are eligible for refund.
                    </span>
                </div>
                @endif
                <button onclick="triggerCancel()" class="px-5 py-2.5 border border-amber-300 text-amber-700 hover:bg-amber-50 text-sm font-bold rounded-xl transition-colors">
                    Cancel Order
                </button>
            @endif

            <details class="relative">
                <summary class="cursor-pointer list-none rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-700 hover:bg-gray-50">More ⋮</summary>
                <div class="absolute bottom-full right-0 z-20 mb-1 rounded-xl border border-gray-200 bg-white p-1.5 shadow-lg">
                    <button type="button" onclick="triggerVoidOrder()" class="whitespace-nowrap rounded-lg px-3 py-2 text-left text-sm font-bold text-rose-700 hover:bg-rose-50">
                        Void Full Order
                    </button>
                </div>
            </details>
        </div>
    </div>

    {{-- Hidden refund form --}}
    <form id="refund-form" method="POST" action="{{ route('refunds.refund', $order) }}" class="hidden">
        @csrf
        <input type="hidden" name="authorizer_email" id="rf-email">
        <input type="hidden" name="authorizer_password" id="rf-password">
        <input type="hidden" name="reason" id="rf-reason">
        <input type="hidden" name="restore_stock" value="1">
    </form>

    <form id="cancel-form" method="POST" action="{{ route('refunds.cancel', $order) }}" class="hidden">
        @csrf
        <input type="hidden" name="authorizer_email" id="cf-email">
        <input type="hidden" name="authorizer_password" id="cf-password">
        <input type="hidden" name="reason" id="cf-reason">
    </form>

    {{-- Hidden void order form --}}
    <form id="void-order-form" method="POST" action="{{ route('orders.void', $order) }}" class="hidden">
        @csrf
        <input type="hidden" name="authorizer_email" id="vf-email">
        <input type="hidden" name="authorizer_password" id="vf-password">
        <input type="hidden" name="reason" id="vf-reason">
    </form>

    {{-- Hidden void item form template --}}
    <form id="void-item-form" method="POST" action="" class="hidden">
        @csrf
        <input type="hidden" name="authorizer_email" id="vif-email">
        <input type="hidden" name="authorizer_password" id="vif-password">
        <input type="hidden" name="reason" id="vif-reason">
    </form>
    @endif
</div>

@push('scripts')
<script>
function triggerRefund() {
    openAuthModal(function(data) {
        document.getElementById('rf-email').value = data.authorizer_email;
        document.getElementById('rf-password').value = data.authorizer_password;
        document.getElementById('rf-reason').value = data.reason;
        document.getElementById('refund-form').submit();
    });
}
function triggerCancel() {
    openAuthModal(function(data) {
        document.getElementById('cf-email').value = data.authorizer_email;
        document.getElementById('cf-password').value = data.authorizer_password;
        document.getElementById('cf-reason').value = data.reason;
        document.getElementById('cancel-form').submit();
    });
}
function triggerVoidOrder() {
    openAuthModal(function(data) {
        document.getElementById('vf-email').value = data.authorizer_email;
        document.getElementById('vf-password').value = data.authorizer_password;
        document.getElementById('vf-reason').value = data.reason;
        document.getElementById('void-order-form').submit();
    });
}
function triggerVoidItem(itemId, itemName) {
    openAuthModal(function(data) {
        const form = document.getElementById('void-item-form');
        form.action = `/orders/{{ $order->id }}/items/${itemId}/void`;
        document.getElementById('vif-email').value = data.authorizer_email;
        document.getElementById('vif-password').value = data.authorizer_password;
        document.getElementById('vif-reason').value = data.reason;
        form.submit();
    });
}

function printThermalReceipt() {
    let printFrame = document.getElementById('thermal-receipt-frame');
    if (!printFrame) {
        printFrame = document.createElement('iframe');
        printFrame.id = 'thermal-receipt-frame';
        printFrame.style.position = 'fixed';
        printFrame.style.right = '0';
        printFrame.style.bottom = '0';
        printFrame.style.width = '0';
        printFrame.style.height = '0';
        printFrame.style.border = '0';
        printFrame.style.visibility = 'hidden';
        printFrame.style.pointerEvents = 'none';
        document.body.appendChild(printFrame);
    }
    
    printFrame.onload = function () {
        try {
            printFrame.contentWindow.focus();
            printFrame.contentWindow.print();
        } catch (err) {
            window.open("{{ route('orders.receipt', $order) }}?autoprint=1", '_blank');
        }
    };

    printFrame.src = "{{ route('orders.receipt', $order) }}";
}
</script>
@endpush
@endsection
