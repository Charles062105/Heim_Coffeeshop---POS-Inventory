@extends('layouts.app')
@section('title', 'Shift #'.$shift->id)
@section('header', 'Shift Summary')
@section('subheader', $shift->cashier_name.' · '.$shift->shift_date->format('M d, Y'))

@section('header-actions')
    <div class="flex gap-2 print:hidden">
        <a href="{{ route('reports.shifts') }}" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-700">Shift Reports</a>
        <button type="button" onclick="window.print()" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-700">Print Shift Slip</button>
    </div>
@endsection

@section('content')
<div class="mx-auto max-w-5xl space-y-5">
    <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach([
            ['Cashier', $shift->cashier_name],
            ['Shift time', $shift->localStartTime()->format('M d, h:i A').' – '.($shift->localEndTime()?->format('M d, h:i A') ?? 'Open')],
            ['Starting cash', '₱'.number_format((float) $shift->beginning_cash, 2)],
            ['Status', ucfirst($shift->status)],
        ] as [$label, $value])
            <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-gray-500">{{ $label }}</p>
                <p class="mt-1 font-bold text-gray-900">{{ $value }}</p>
            </div>
        @endforeach
    </section>

    <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <h2 class="mb-4 font-bold text-gray-900">Drawer reconciliation</h2>
        <div class="grid gap-x-8 gap-y-2 text-sm sm:grid-cols-2">
            <div class="flex justify-between"><span>Starting cash</span><strong>₱{{ number_format((float) $shift->beginning_cash, 2) }}</strong></div>
            <div class="flex justify-between"><span>Cash sales</span><strong>₱{{ number_format($summary['cash_sales'], 2) }}</strong></div>
            <div class="flex justify-between"><span>Cash refunds</span><strong class="text-rose-700">−₱{{ number_format($summary['cash_refunds'], 2) }}</strong></div>
            <div class="flex justify-between"><span>Cash voids</span><strong class="text-rose-700">−₱{{ number_format($summary['cash_voids'], 2) }}</strong></div>
            <div class="flex justify-between border-t border-gray-100 pt-2 font-bold"><span>Expected cash</span><strong>₱{{ number_format($expectedCash, 2) }}</strong></div>
            <div class="flex justify-between"><span>Actual cash</span><strong>{{ $shift->actual_cash === null ? 'Not counted' : '₱'.number_format((float) $shift->actual_cash, 2) }}</strong></div>
            <div class="flex justify-between font-bold"><span>Difference</span><strong class="{{ (float) $shift->difference < 0 ? 'text-rose-700' : ((float) $shift->difference > 0 ? 'text-emerald-700' : 'text-gray-900') }}">{{ $shift->difference === null ? '—' : (($shift->difference >= 0 ? '+' : '').'₱'.number_format((float) $shift->difference, 2).' · '.((float) $shift->difference < 0 ? 'Short' : ((float) $shift->difference > 0 ? 'Over' : 'Balanced'))) }}</strong></div>
        </div>
        @if($shift->notes)
            <p class="mt-4 rounded-xl bg-amber-50 p-3 text-sm text-amber-900"><strong>Cashier note:</strong> {{ $shift->notes }}</p>
        @endif
        @if($shift->denomination_count)
            <details class="mt-4 text-sm">
                <summary class="cursor-pointer font-semibold text-gray-700">Cash count by denomination</summary>
                <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach($shift->denomination_count as $denomination => $count)
                        <div class="rounded-lg bg-gray-50 px-3 py-2">₱{{ $denomination }} × {{ $count }}</div>
                    @endforeach
                </div>
            </details>
        @endif
    </section>

    <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <h2 class="mb-4 font-bold text-gray-900">Other activity (not drawer cash)</h2>
        <div class="grid gap-x-8 gap-y-2 text-sm sm:grid-cols-2">
            <div class="flex justify-between"><span>Online / e-wallet</span><strong>₱{{ number_format($summary['online_sales'], 2) }}</strong></div>
            <div class="flex justify-between"><span>Grab orders</span><strong>₱{{ number_format($summary['grab_sales'], 2) }}</strong></div>
            <div class="flex justify-between"><span>Grab settlements received</span><strong>₱{{ number_format($summary['grab_settlements'], 2) }}</strong></div>
            <div class="flex justify-between"><span>Dine-in sales</span><strong>₱{{ number_format($summary['dine_in_sales'], 2) }}</strong></div>
            <div class="flex justify-between"><span>Take-out sales</span><strong>₱{{ number_format($summary['take_out_sales'], 2) }}</strong></div>
            <div class="flex justify-between"><span>Voids</span><strong>{{ $summary['void_count'] }} · ₱{{ number_format($summary['void_amount'], 2) }}</strong></div>
        </div>
    </section>

    @if(auth()->user()->canAuthorize() && $shift->isOpen())
        <section class="rounded-2xl border border-amber-200 bg-amber-50/50 p-5">
            <h2 class="font-bold text-gray-900">Close this cashier’s open shift</h2>
            <p class="mt-1 text-xs text-gray-600">Manager closure is audited and still requires the drawer count. Expected cash: ₱{{ number_format($expectedCash, 2) }}.</p>
            <form method="POST" action="{{ route('reports.shifts.close', $shift) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                @csrf
                <label class="text-xs font-semibold text-gray-700">Actual cash counted
                    <input id="manager-actual-cash" required name="actual_cash" type="number" min="0" step="0.01" class="mt-1 w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm">
                </label>
                <label class="flex items-center gap-2 self-end pb-2 text-xs font-semibold text-gray-700">
                    <input id="manager-use-denominations" type="checkbox" onchange="toggleManagerDenominations()" class="rounded border-gray-300 text-heim-600">
                    Count by denomination instead
                </label>
                <div id="manager-denomination-panel" class="hidden rounded-xl border border-gray-200 bg-gray-50 p-3 sm:col-span-2">
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        @foreach([1000, 500, 200, 100, 50, 20, 10, 5, 1] as $denomination)
                            <label class="text-[11px] font-semibold text-gray-600">₱{{ $denomination }} notes/coins
                                <input disabled name="denomination_count[{{ $denomination }}]" type="number" min="0" step="1" value="0" class="mt-1 w-full rounded-lg border border-gray-200 px-2 py-1.5 text-right text-sm">
                            </label>
                        @endforeach
                        @foreach(['0.25', '0.10', '0.05'] as $denomination)
                            <label class="text-[11px] font-semibold text-gray-600">₱{{ $denomination }} coins
                                <input disabled name="denomination_count[{{ $denomination }}]" type="number" min="0" step="1" value="0" class="mt-1 w-full rounded-lg border border-gray-200 px-2 py-1.5 text-right text-sm">
                            </label>
                        @endforeach
                    </div>
                </div>
                <label class="text-xs font-semibold text-gray-700">Cash note (required for variance)
                    <input name="comment" maxlength="500" class="mt-1 w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm">
                </label>
                <label class="text-xs font-semibold text-gray-700 sm:col-span-2">Manager approval reason (required if open tickets remain)
                    <input minlength="10" name="override_reason" maxlength="500" class="mt-1 w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm">
                </label>
                <button class="brand-button sm:col-span-2">Close Shift as Manager</button>
            </form>
        </section>
    @endif

    @if(auth()->user()->canAuthorize() && $shift->isClosed())
        <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <h2 class="font-bold text-gray-900">{{ $shift->status === 'reviewed' ? 'Reviewed' : 'Manager review' }}</h2>
            @if($shift->reviewed_at)
                <p class="mt-1 text-xs text-gray-500">Reviewed by {{ $shift->reviewedBy?->name ?? 'Manager' }} on {{ $shift->reviewed_at->copy()->timezone(config('app.business_timezone'))->format('M d, Y h:i A') }}</p>
                @if($shift->review_note)<p class="mt-2 text-sm text-gray-700">{{ $shift->review_note }}</p>@endif
            @else
                <form method="POST" action="{{ route('reports.shifts.review', $shift) }}" class="mt-3 flex flex-col gap-2 sm:flex-row">
                    @csrf
                    <input name="review_note" maxlength="1000" placeholder="Review note (optional)" class="min-w-0 flex-1 rounded-xl border border-gray-200 px-3 py-2 text-sm">
                    <button class="brand-button">Mark Reviewed</button>
                </form>
            @endif
        </section>
    @endif

    @if(auth()->user()->canAuthorize() && $shift->isClosed())
        <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <h2 class="font-bold text-gray-900">Correction adjustment</h2>
            <p class="mt-1 text-xs text-gray-500">Records a signed correction without changing the closed shift’s original count or variance.</p>
            <form method="POST" action="{{ route('reports.shifts.adjustments.store', $shift) }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                @csrf
                <label class="text-xs font-semibold text-gray-700">Adjustment amount (positive adds cash; negative removes cash)
                    <input required name="amount" type="number" step="0.01" min="-99999999" max="99999999" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                </label>
                <label class="text-xs font-semibold text-gray-700">Reason
                    <input required minlength="10" maxlength="500" name="reason" class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                </label>
                <button class="brand-button sm:col-span-2">Add Audited Adjustment</button>
            </form>
            @if($adjustments->isNotEmpty())
                <div class="mt-4 space-y-2">
                    @foreach($adjustments as $adjustment)
                        <div class="flex flex-wrap justify-between gap-2 rounded-xl bg-gray-50 px-3 py-2 text-xs">
                            <span>{{ $adjustment->created_at->copy()->timezone(config('app.business_timezone'))->format('M d, Y h:i A') }} · {{ $adjustment->recordedBy?->name }} · {{ $adjustment->reason }}</span>
                            <strong class="{{ $adjustment->amount < 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ $adjustment->amount >= 0 ? '+' : '' }}₱{{ number_format((float) $adjustment->amount, 2) }}</strong>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <h2 class="mb-3 font-bold text-gray-900">Orders and tickets</h2>
        <div class="divide-y divide-gray-100">
            @forelse($orders as $order)
                <a href="{{ route('orders.show', $order) }}" class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm hover:text-heim-700">
                    <span class="font-semibold">{{ $order->order_number }} · {{ ucfirst(str_replace('_', ' ', $order->order_type)) }}</span>
                    <span>{{ ucfirst(str_replace('_', ' ', $order->status)) }} · ₱{{ number_format((float) $order->total, 2) }}</span>
                </a>
            @empty
                <p class="py-4 text-sm text-gray-500">No orders attached to this shift.</p>
            @endforelse
        </div>
    </section>

    <section class="grid gap-5 lg:grid-cols-2">
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <h2 class="mb-3 font-bold text-gray-900">Payments received on shift</h2>
            @forelse($payments as $payment)
                <div class="flex justify-between gap-3 border-t border-gray-100 py-2 text-xs">
                    <span>{{ $payment->order?->order_number }} · {{ ucfirst($payment->method) }}{{ $payment->customer_name ? ' · '.$payment->customer_name : '' }}</span>
                    <strong>₱{{ number_format((float) $payment->amount_paid, 2) }}</strong>
                </div>
            @empty
                <p class="text-sm text-gray-500">No order payments.</p>
            @endforelse
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <h2 class="mb-3 font-bold text-gray-900">Refunds and voids</h2>
            @forelse($refunds as $refund)
                <div class="flex justify-between gap-3 border-t border-gray-100 py-2 text-xs">
                    <span>{{ $refund->order?->order_number }} · {{ ucfirst($refund->method) }} · {{ $refund->reason }}</span>
                    <strong class="text-rose-700">−₱{{ number_format((float) $refund->amount, 2) }}</strong>
                </div>
            @empty
                <p class="text-sm text-gray-500">No refunds recorded on this shift.</p>
            @endforelse
            @foreach($voidLogs as $void)
                <div class="flex justify-between gap-3 border-t border-gray-100 py-2 text-xs">
                    <span>{{ $void->order?->order_number }} · {{ ucfirst($void->void_type) }} void · {{ $void->reason }}</span>
                    <strong>₱{{ number_format((float) $void->amount, 2) }}</strong>
                </div>
            @endforeach
        </div>
    </section>
</div>
<script>
    function toggleManagerDenominations() {
        const enabled = document.getElementById('manager-use-denominations').checked;
        document.getElementById('manager-actual-cash').disabled = enabled;
        document.getElementById('manager-actual-cash').required = !enabled;
        document.getElementById('manager-denomination-panel').classList.toggle('hidden', !enabled);
        document.querySelectorAll('#manager-denomination-panel input').forEach(input => {
            input.disabled = !enabled;
        });
    }
</script>
@endsection
