{{-- Pure Thermal Receipt Content (Standard 80mm / 72mm printable width) --}}
<div class="thermal-receipt-body text-black font-mono text-[11px] leading-[1.3] text-left">
    
    {{-- Shop Header --}}
    <div class="text-center pb-2">
        <div class="text-base font-black tracking-wider uppercase">HEIM COFFEE</div>
        <div class="text-[10px] tracking-wide uppercase mt-0.5">Specialty Coffee & Beverages</div>
        <div class="text-[9px] text-gray-700 mt-0.5">Session Road, Baguio City, Philippines</div>
        <div class="text-[9px] font-bold tracking-widest uppercase mt-1">OFFICIAL SALES RECEIPT</div>
    </div>

    {{-- Dashed line --}}
    <div class="border-b border-dashed border-black my-2"></div>

    {{-- Transaction Metadata --}}
    <div class="space-y-0.5 text-[10px]">
        <div class="flex justify-between">
            <span class="font-bold">Receipt #:</span>
            <span class="font-bold font-mono">{{ $order->order_number }}</span>
        </div>
        <div class="flex justify-between">
            <span>Date & Time:</span>
            <span>{{ $order->created_at->copy()->timezone(config('app.business_timezone', 'Asia/Manila'))->format('Y-m-d h:i A') }}</span>
        </div>
        <div class="flex justify-between">
            <span>Cashier:</span>
            <span class="font-medium">{{ $order->cashier_name }}</span>
        </div>
        <div class="flex justify-between">
            <span>Order Type:</span>
            <span class="font-bold uppercase">
                @if($order->order_type === 'grab')
                    🛵 GRAB DELIVERY
                @else
                    {{ str_replace('_', ' ', strtoupper($order->order_type ?? 'Dine In')) }}
                @endif
            </span>
        </div>

        {{-- Grab Specific Fields --}}
        @if($order->order_type === 'grab')
            @if($order->grab_order_code)
            <div class="flex justify-between font-bold">
                <span>Grab Order Code:</span>
                <span class="font-mono">{{ $order->grab_order_code }}</span>
            </div>
            @endif
            @if($order->rider_code)
            <div class="flex justify-between font-bold">
                <span>Rider Code:</span>
                <span class="font-mono">{{ $order->rider_code }}</span>
            </div>
            @endif
        @endif

        {{-- Customer Name --}}
        @if($order->customer_name)
        <div class="flex justify-between">
            <span>Customer:</span>
            <span class="font-medium">{{ $order->customer_name }}</span>
        </div>
        @endif
    </div>

    {{-- Dashed line --}}
    <div class="border-b border-dashed border-black my-2"></div>

    {{-- Column Headers --}}
    <div class="flex justify-between text-[10px] font-bold uppercase pb-1 mb-1 border-b border-black">
        <span class="w-[58%]">Item</span>
        <span class="w-[18%] text-center">Qty</span>
        <span class="w-[24%] text-right">Total</span>
    </div>

    {{-- Items List --}}
    <div class="space-y-2 text-[11px]">
        @foreach($order->orderItems as $item)
        <div>
            <div class="flex items-start justify-between">
                <div class="w-[58%] pr-1">
                    <div class="font-bold leading-tight {{ $item->isVoided() ? 'line-through' : '' }}">
                        {{ $item->product->name ?? 'Product' }}
                    </div>
                    <div class="text-[10px] text-gray-800">
                        {{ $item->size->size_name ?? 'Regular' }}
                    </div>
                </div>
                <div class="w-[18%] text-center font-mono text-[10px]">
                    {{ $item->quantity }}
                </div>
                <div class="w-[24%] text-right font-bold font-mono {{ $item->isVoided() ? 'line-through' : '' }}">
                    ₱{{ number_format($item->subtotal, 2) }}
                </div>
            </div>

            {{-- Unit price small note --}}
            <div class="text-[9px] text-gray-700 pl-1">
                @ ₱{{ number_format($item->unit_price, 2) }} each
            </div>

            {{-- Add-ons list --}}
            @if($item->addons && $item->addons->count())
                <div class="pl-2 text-[10px] text-gray-800 space-y-0.5 mt-0.5">
                    @foreach($item->addons as $addon)
                    <div class="flex justify-between">
                        <span>+ {{ $addon->addon->name ?? 'Add-on' }}</span>
                        <span class="font-mono">+₱{{ number_format($addon->price, 2) }}</span>
                    </div>
                    @endforeach
                </div>
            @endif

            {{-- Item comment / preparation notes --}}
            @if($item->comment)
                <div class="text-[10px] italic pl-2 text-gray-900 mt-0.5">
                    * Note: {{ $item->comment }}
                </div>
            @endif

            {{-- Voided Badge --}}
            @if($item->isVoided())
                <div class="text-[9px] font-bold uppercase pl-2 text-black">
                    *** VOIDED ITEM ***
                </div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Dashed line --}}
    <div class="border-b border-dashed border-black my-2"></div>

    {{-- Totals --}}
    <div class="space-y-1 text-[11px]">
        <div class="flex justify-between">
            <span>Subtotal</span>
            <span class="font-mono">₱{{ number_format($order->subtotal, 2) }}</span>
        </div>

        @if($order->discount > 0)
        <div class="flex justify-between font-bold">
            <span>
                Discount
                @if($order->discount_label)
                    <span class="text-[9px] font-normal">({{ $order->discount_label }})</span>
                @endif
            </span>
            <span class="font-mono">−₱{{ number_format($order->discount, 2) }}</span>
        </div>
        @if($order->discount_id_number)
        <div class="flex justify-between text-[9px]">
            <span>ID / SC / PWD No.:</span>
            <span class="font-mono font-bold">{{ $order->discount_id_number }}</span>
        </div>
        @endif
        @endif

        {{-- Grand Total --}}
        <div class="flex justify-between items-baseline text-sm font-black pt-1 mt-1 border-t border-dashed border-black">
            <span>TOTAL AMOUNT</span>
            <span class="font-mono text-base">₱{{ number_format($order->total, 2) }}</span>
        </div>
    </div>

    {{-- Dashed line --}}
    <div class="border-b border-dashed border-black my-2"></div>

    {{-- Tax Breakdown (BIR compliant) --}}
    <div class="space-y-0.5 text-[9px]">
        <div class="flex justify-between">
            <span>VATable Sales:</span>
            <span class="font-mono">₱{{ number_format($order->vatable_sales ?? 0, 2) }}</span>
        </div>
        @if(($order->vat_exempt_sales ?? 0) > 0)
        <div class="flex justify-between">
            <span>VAT-Exempt Sales:</span>
            <span class="font-mono">₱{{ number_format($order->vat_exempt_sales, 2) }}</span>
        </div>
        @endif
        @if(($order->zero_rated_sales ?? 0) > 0)
        <div class="flex justify-between">
            <span>Zero-Rated Sales:</span>
            <span class="font-mono">₱{{ number_format($order->zero_rated_sales, 2) }}</span>
        </div>
        @endif
        <div class="flex justify-between font-bold">
            <span>{{ $order->tax_name ?? '12% VAT' }}:</span>
            <span class="font-mono">₱{{ number_format($order->tax_amount ?? 0, 2) }}</span>
        </div>
    </div>

    {{-- Dashed line --}}
    <div class="border-b border-dashed border-black my-2"></div>

    {{-- Payment Information --}}
    @if($order->payments->isNotEmpty())
    <div class="space-y-0.5 text-[10px]">
        @foreach($order->payments as $payment)
        <div class="border-b border-dashed border-gray-300 pb-1 mb-1">
        <div class="flex justify-between">
            <span>Payment{{ $payment->customer_name ? ' — '.$payment->customer_name : '' }}:</span>
            <span class="font-bold uppercase">
                {{ match(strtolower($payment->method)) {
                    'cash'             => 'Cash',
                    'grabfood', 'grab' => 'GrabFood',
                    default            => 'Online Payment'
                } }}
            </span>
        </div>
        <div class="flex justify-between">
            <span>Payment Status:</span>
            <span class="font-bold uppercase">{{ str_replace('_', ' ', $payment->status) }}</span>
        </div>

        @if($payment->reference_number)
        <div class="flex justify-between">
            <span>Reference No.:</span>
            <span class="font-mono font-bold">{{ $payment->reference_number }}</span>
        </div>
        @endif

        <div class="flex justify-between">
            <span>Amount Paid / Tendered:</span>
            <span class="font-mono">₱{{ number_format($payment->amount_paid, 2) }} / ₱{{ number_format($payment->amount_received, 2) }}</span>
        </div>

        @if($payment->change_amount > 0)
        <div class="flex justify-between font-bold">
            <span>Change Due:</span>
            <span class="font-mono">₱{{ number_format($payment->change_amount, 2) }}</span>
        </div>
        @endif
        @if($payment->comment)
        <div class="flex justify-between"><span>Comment:</span><span>{{ $payment->comment }}</span></div>
        @endif
        </div>
        @endforeach
        @if($order->isPayable() || $order->isCompleted())
            <div class="flex justify-between font-bold">
                <span>Remaining Balance:</span>
                <span class="font-mono">₱{{ number_format($order->remainingBalance(), 2) }}</span>
            </div>
        @else
            <div class="flex justify-between font-bold">
                <span>Order Status:</span>
                <span>{{ ucfirst(str_replace('_', ' ', $order->status)) }} · No balance due</span>
            </div>
        @endif
    </div>
    @endif

    {{-- Double divider for footer --}}
    <div class="border-b-2 border-dashed border-black my-3"></div>

    {{-- Receipt Footer --}}
    <div class="text-center text-[10px] space-y-1">
        <div class="font-bold uppercase tracking-wider">Thank you for visiting Heim Coffee!</div>
        <div class="text-[9px]">Please keep this receipt for your records.</div>
        <div class="text-[9px] font-mono tracking-widest uppercase pt-1">
            *** {{ $order->isPayable() && $order->remainingBalance() > 0 ? 'PARTIALLY PAID • BALANCE DUE' : ($order->status === 'completed' ? 'PAID • COMPLETED' : strtoupper($order->status)) }} ***
        </div>
    </div>
</div>
