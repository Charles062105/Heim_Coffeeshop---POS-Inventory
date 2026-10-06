@extends('layouts.app')
@section('title', 'Order #' . $order->order_number)
@section('header', 'Order Complete')
@section('subheader', 'Receipt #' . $order->order_number . ' successfully processed and logged')

@section('header-actions')
    <div class="flex items-center gap-2 print:hidden">
        <button onclick="printThermalReceipt()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-heim-800 bg-white border border-heim-200 hover:bg-heim-50 shadow-xs transition-all">
            <svg class="w-4 h-4 text-heim-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print Receipt
        </button>
        <a href="{{ route('pos.index') }}" class="brand-button gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            New Sale
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
        /* Hide entire web UI, top navbar, sidebar, action buttons, cards, and web wrappers */
        header, nav, aside, .sidebar-shell, #user-menu-button, .notif-badge,
        .header-actions, .brand-card, #receipt-printable-card, .print\:hidden,
        .print-hide {
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

{{-- Screen View (hidden during print) --}}
<div class="max-w-lg mx-auto print:hidden">
    <div id="receipt-printable-card" class="brand-card rounded-2xl shadow-sm overflow-hidden">

        {{-- Success header --}}
        <div class="bg-gradient-to-br from-heim-700 to-heim-900 p-8 text-center text-white">
            <div class="w-16 h-16 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-4 border border-white/20">
                <svg class="w-8 h-8 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-2xl font-black tracking-tight">Order Complete!</h2>
            <p class="text-heim-200 mt-1 font-mono text-sm">{{ $order->order_number }}</p>
            @if($order->order_type === 'grab')
                <div class="mt-2 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/20 border border-emerald-400/40 text-emerald-200 text-xs font-bold uppercase tracking-wider">
                    <span>🛵 Grab Delivery</span>
                </div>
            @endif
        </div>

        {{-- Order items --}}
        <div class="p-6">
            @if($order->order_type === 'grab')
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3.5 mb-4 text-xs space-y-1.5">
                <div class="flex justify-between items-center">
                    <span class="font-bold uppercase tracking-wider text-emerald-800">Grab Order Code</span>
                    <span class="font-black font-mono text-emerald-900 bg-white px-2 py-0.5 rounded border border-emerald-200">{{ $order->grab_order_code ?? '—' }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="font-bold uppercase tracking-wider text-emerald-800">Rider Code</span>
                    <span class="font-black font-mono text-emerald-900 bg-white px-2 py-0.5 rounded border border-emerald-200">{{ $order->rider_code ?? '—' }}</span>
                </div>
                @if($order->customer_name)
                <div class="flex justify-between items-center">
                    <span class="font-medium text-emerald-700">Customer Name</span>
                    <span class="font-bold text-emerald-950">{{ $order->customer_name }}</span>
                </div>
                @endif
            </div>
            @endif

            <h3 class="brand-label mb-3 text-gray-500">Order Summary</h3>
            <div class="space-y-2 mb-4">
                @foreach($order->orderItems as $item)
                <div class="bg-gray-50/70 border border-gray-100 rounded-xl px-4 py-2.5">
                    <div class="flex items-center justify-between text-sm">
                        <div>
                            <span class="font-bold text-gray-900">{{ $item->product->name }}</span>
                            <span class="text-xs text-heim-700 font-semibold ml-1">({{ $item->size->size_name ?? 'Regular' }})</span>
                            <span class="text-gray-400 text-xs ml-1">×{{ $item->quantity }}</span>
                        </div>
                        <span class="font-bold text-gray-900">₱{{ number_format($item->subtotal, 2) }}</span>
                    </div>
                    @if($item->addons && $item->addons->count())
                        <div class="text-[11px] text-gray-500 mt-1 pl-1 border-l-2 border-heim-200">
                            @foreach($item->addons as $addon)
                                <span>+ {{ $addon->addon->name ?? 'Add-on' }} (+₱{{ number_format($addon->price, 2) }})</span>@if(!$loop->last), @endif
                            @endforeach
                        </div>
                    @endif
                    @if($item->comment)
                        <div class="text-[11px] text-amber-800 italic mt-1 font-medium bg-amber-50/60 rounded px-2 py-0.5 inline-block">
                            * {{ $item->comment }}
                        </div>
                    @endif
                </div>
                @endforeach
            </div>

            {{-- Totals --}}
            <div class="border-t border-gray-100 pt-3 space-y-1.5 text-sm">
                <div class="flex justify-between text-gray-500 font-medium">
                    <span>Subtotal</span>
                    <span class="text-gray-800 font-semibold font-mono">₱{{ number_format($order->subtotal, 2) }}</span>
                </div>
                @if($order->discount > 0)
                <div class="flex justify-between text-rose-600 font-medium">
                    <span>
                        Discount
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

                {{-- Tax breakdown --}}
                <div class="py-2 border-y border-dashed border-gray-200 text-xs text-gray-500 space-y-1">
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
                    <span>Total Amount</span>
                    <span class="text-heim-800 font-mono">₱{{ number_format($order->total, 2) }}</span>
                </div>
            </div>

            {{-- Payment info --}}
            @if($order->payments->isNotEmpty())
            <div class="bg-heim-50/70 border border-heim-100 rounded-xl p-4 mt-4 text-sm space-y-2">
                @foreach($order->payments as $payment)
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold uppercase tracking-wider text-heim-800">Payment Method</span>
                    <span class="font-bold capitalize text-gray-900">
                        {{ match(strtolower($payment->method)) {
                            'cash'             => 'Cash',
                            'grabfood', 'grab' => 'GrabFood',
                            default            => 'Online Payment'
                        } }}
                    </span>
                </div>

                {{-- Online Payment Reference Number --}}
                @if($payment->reference_number)
                <div class="flex justify-between items-center border-t border-heim-100 pt-1.5">
                    <span class="text-xs font-bold uppercase tracking-wider text-heim-800">Reference No.</span>
                    <span class="font-bold font-mono text-heim-900 bg-white px-2 py-0.5 rounded-lg border border-heim-200">
                        {{ $payment->reference_number }}
                    </span>
                </div>
                @endif

                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold uppercase tracking-wider text-heim-800">Amount Received</span>
                    <span class="font-bold text-gray-900">₱{{ number_format($payment->amount_paid, 2) }} / ₱{{ number_format($payment->amount_received, 2) }}</span>
                </div>
                @if($payment->change_amount > 0)
                <div class="flex justify-between items-center font-extrabold text-heim-700 border-t border-heim-100 pt-1.5">
                    <span class="text-xs uppercase tracking-wider">Change Given</span>
                    <span>₱{{ number_format($payment->change_amount, 2) }}</span>
                </div>
                @endif
                @if($payment->comment)
                <div class="flex justify-between items-center"><span>Payment Comment</span><span>{{ $payment->comment }}</span></div>
                @endif
                @endforeach
                <div class="flex justify-between items-center border-t border-heim-100 pt-2 font-bold">
                    <span>Remaining Balance</span><span>₱{{ number_format($order->remainingBalance(), 2) }}</span>
                </div>
            </div>
            @endif

            <div class="flex items-center justify-between text-xs text-gray-400 mt-4 pt-3 border-t border-gray-100">
                <span>Cashier: <strong class="text-gray-600">{{ $order->cashier_name }}</strong></span>
                <span>{{ $order->created_at->copy()->timezone(config('app.business_timezone', 'Asia/Manila'))->format('M d, Y h:i A') }}</span>
            </div>
        </div>

        {{-- Actions --}}
        <div class="border-t border-gray-100 p-5 flex flex-col sm:flex-row gap-3 bg-gray-50/50 print:hidden">
            <button type="button" onclick="printThermalReceipt()" class="flex-1 brand-button text-center flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print Receipt
            </button>
            <a href="{{ route('pos.index') }}" class="flex-1 border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 font-semibold py-2.5 rounded-xl text-sm text-center shadow-xs transition-colors flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Sale
            </a>
            <a href="{{ route('orders.show', $order) }}" class="flex-1 border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 font-semibold py-2.5 rounded-xl text-sm text-center shadow-xs transition-colors flex items-center justify-center gap-1.5">
                View Details
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
function printThermalReceipt() {
    let printFrame = document.getElementById('thermal-receipt-iframe');
    if (!printFrame) {
        printFrame = document.createElement('iframe');
        printFrame.id = 'thermal-receipt-iframe';
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
            console.warn('Iframe print error, falling back to window.open:', err);
            window.open("{{ route('orders.receipt', $order) }}?autoprint=1", '_blank');
        }
    };

    printFrame.src = "{{ route('orders.receipt', $order) }}";
}
</script>
@endpush
@endsection
