@extends('layouts.app')
@section('title', 'Debt — ' . $debt->customer_name)
@section('header', 'Charge Account')
@section('subheader', 'Customer debt details and payment history')

@section('header-actions')
    <a href="{{ route('debts.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 shadow-xs transition-all">
        ← Back to Debt List
    </a>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
        ✓ {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">
        ✗ {{ session('error') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Debt Summary --}}
        <div class="lg:col-span-1 space-y-4">

            {{-- Customer Info Card --}}
            <div class="brand-card rounded-2xl shadow-sm p-6">
                <h2 class="font-bold text-gray-900 text-base mb-4 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-heim-100 text-heim-700 flex items-center justify-center text-sm">👤</span>
                    Customer Details
                </h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Customer Name</p>
                        <p class="font-bold text-gray-900 text-lg">{{ $debt->customer_name }}</p>
                    </div>
                    @if($debt->customer_phone)
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Phone</p>
                        <p class="font-semibold text-gray-700">{{ $debt->customer_phone }}</p>
                    </div>
                    @endif
                    @if($debt->order)
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Order #</p>
                        <a href="{{ route('orders.show', $debt->order) }}" class="font-mono font-bold text-heim-700 hover:underline text-sm">
                            {{ $debt->order->order_number }}
                        </a>
                    </div>
                    @endif
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Created</p>
                        <p class="text-sm text-gray-700">{{ $debt->created_at->format('M d, Y h:i A') }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Created By</p>
                        <p class="text-sm text-gray-700">{{ $debt->created_by }}</p>
                    </div>
                    @if($debt->due_date)
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Due Date</p>
                        <p class="text-sm font-semibold {{ $debt->due_date->isPast() && !$debt->isPaid() ? 'text-rose-600' : 'text-gray-700' }}">
                            {{ $debt->due_date->format('M d, Y') }}
                            @if($debt->due_date->isPast() && !$debt->isPaid())
                                <span class="ml-1 text-[10px] font-bold px-1.5 py-0.5 rounded bg-rose-100 text-rose-700">OVERDUE</span>
                            @endif
                        </p>
                    </div>
                    @endif
                    @if($debt->notes)
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Notes</p>
                        <p class="text-xs text-gray-600 mt-1 leading-relaxed">{{ $debt->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Balance Summary --}}
            <div class="brand-card rounded-2xl shadow-sm p-6">
                <h2 class="font-bold text-gray-900 text-base mb-4">Balance Summary</h2>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Original Amount</span>
                        <span class="font-mono font-semibold text-gray-900">₱{{ number_format($debt->original_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Total Paid</span>
                        <span class="font-mono font-semibold text-emerald-700">₱{{ number_format($debt->amount_paid, 2) }}</span>
                    </div>
                    <div class="border-t border-gray-100 pt-3 flex justify-between items-center">
                        <span class="text-sm font-bold text-gray-700">Balance Due</span>
                        <span class="font-mono font-extrabold text-xl {{ $debt->status === 'paid' ? 'text-gray-400' : 'text-rose-600' }}">
                            ₱{{ number_format($debt->balance, 2) }}
                        </span>
                    </div>
                    <div class="text-center pt-1">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $debt->statusColor() }}">
                            {{ $debt->statusLabel() }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Order Items (if linked) --}}
            @if($debt->order && $debt->order->orderItems->count() > 0)
            <div class="brand-card rounded-2xl shadow-sm p-6">
                <h2 class="font-bold text-gray-900 text-base mb-3">Order Items</h2>
                <div class="space-y-2">
                    @foreach($debt->order->orderItems as $item)
                    <div class="flex justify-between items-start text-sm">
                        <div>
                            <span class="font-medium text-gray-800">{{ $item->product?->name ?? '—' }}</span>
                            <span class="text-xs text-gray-400 ml-1">{{ $item->size?->size_name }}</span>
                            @if($item->comment)
                                <p class="text-[11px] text-gray-400 italic">{{ $item->comment }}</p>
                            @endif
                        </div>
                        <div class="text-right ml-4">
                            <span class="font-mono text-gray-700">x{{ $item->quantity }}</span>
                            <span class="font-mono font-semibold text-gray-900 ml-2">₱{{ number_format($item->subtotal, 2) }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- Right: Record Payment + Payment History --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Record Payment Form --}}
            @if($debt->status !== 'paid')
            <div class="brand-card rounded-2xl shadow-sm p-6">
                <h2 class="font-bold text-gray-900 text-base mb-5 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm">💳</span>
                    Record Payment
                </h2>
                <form action="{{ route('debts.payment', $debt) }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5">Amount <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-gray-400 text-sm font-semibold">₱</span>
                                <input type="number" name="amount" step="0.01" min="0.01" max="{{ $debt->balance }}"
                                    value="{{ old('amount') }}" placeholder="0.00" required
                                    class="w-full pl-7 pr-3 py-2.5 text-sm rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-heim-500">
                            </div>
                            @error('amount')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-[11px] text-gray-400 mt-1">Balance: ₱{{ number_format($debt->balance, 2) }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5">Payment Date <span class="text-rose-500">*</span></label>
                            <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required
                                class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-heim-500">
                            @error('payment_date')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5">Payment Method <span class="text-rose-500">*</span></label>
                            <select name="payment_method" required
                                class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-heim-500">
                                <option value="cash" @selected(old('payment_method') === 'cash')>Cash</option>
                                <option value="online" @selected(old('payment_method') === 'online')>Online / GCash</option>
                                <option value="other" @selected(old('payment_method') === 'other')>Other</option>
                            </select>
                            @error('payment_method')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5">Reference # (optional)</label>
                            <input type="text" name="reference_number" value="{{ old('reference_number') }}" placeholder="GCash ref, receipt #..."
                                class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-heim-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1.5">Notes (optional)</label>
                        <textarea name="notes" rows="2" placeholder="Payment notes..."
                            class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-heim-500 resize-none">{{ old('notes') }}</textarea>
                    </div>

                    {{-- Quick pay full balance --}}
                    <div class="flex items-center gap-3">
                        <button type="button"
                            onclick="document.querySelector('[name=amount]').value = '{{ number_format($debt->balance, 2, '.', '') }}'"
                            class="text-xs font-bold px-3 py-1.5 rounded-lg bg-heim-50 text-heim-700 border border-heim-200 hover:bg-heim-100 transition-colors">
                            Pay Full Balance (₱{{ number_format($debt->balance, 2) }})
                        </button>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="px-6 py-2.5 bg-emerald-600 text-white text-sm font-bold rounded-xl hover:bg-emerald-700 transition-colors shadow-sm">
                            Record Payment
                        </button>
                    </div>
                </form>
            </div>
            @else
            <div class="brand-card rounded-2xl shadow-sm p-6 text-center">
                <div class="w-14 h-14 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl mx-auto mb-3">✓</div>
                <p class="font-bold text-gray-900 text-lg">Debt Fully Paid</p>
                <p class="text-sm text-gray-500 mt-1">All payments have been collected for this account.</p>
            </div>
            @endif

            {{-- Payment History --}}
            <div class="brand-card rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-gray-900 text-base">Payment History</h2>
                        <p class="text-xs text-gray-400">All recorded payments for this account</p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
                        {{ $debt->payments->count() }} payment{{ $debt->payments->count() === 1 ? '' : 's' }}
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50/75 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3 text-left">Date</th>
                                <th class="px-5 py-3 text-right">Amount</th>
                                <th class="px-5 py-3 text-center">Method</th>
                                <th class="px-5 py-3 text-left">Reference #</th>
                                <th class="px-5 py-3 text-left">Recorded By</th>
                                <th class="px-5 py-3 text-left">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($debt->payments->sortByDesc('payment_date') as $payment)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-5 py-3.5 text-xs text-gray-600 font-medium">
                                    {{ $payment->payment_date->format('M d, Y') }}
                                </td>
                                <td class="px-5 py-3.5 text-right font-mono font-extrabold text-emerald-700">
                                    ₱{{ number_format($payment->amount, 2) }}
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold
                                        {{ $payment->payment_method === 'cash' ? 'bg-gray-100 text-gray-700' : 'bg-blue-100 text-blue-800' }}">
                                        {{ ucfirst($payment->payment_method) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-xs text-gray-600 font-mono">
                                    {{ $payment->reference_number ?? '—' }}
                                </td>
                                <td class="px-5 py-3.5 text-xs text-gray-600">{{ $payment->recorded_by }}</td>
                                <td class="px-5 py-3.5 text-xs text-gray-500 max-w-xs truncate">{{ $payment->notes ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-xs text-gray-400">
                                    No payments recorded yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
