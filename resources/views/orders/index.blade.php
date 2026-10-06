@extends('layouts.app')
@section('title', 'Orders')
@section('header', 'Customer Orders')
@section('subheader', 'Trace completed POS orders, payment settlement, and customer ticket breakdowns')

@section('header-actions')
    <a href="{{ route('pos.index') }}" class="brand-button gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
        New POS Order
    </a>
@endsection

@section('content')
{{-- Filter toolbar --}}
<div class="brand-card rounded-2xl p-4 mb-6 shadow-sm">
    <form method="GET" class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
        <div class="flex flex-1 flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Search Orders</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Order # or cashier name..."
                        class="w-full pl-9 pr-3.5 py-2 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-heim-500 transition-all">
                </div>
            </div>

            <div class="w-40">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Order Status</label>
                <select name="status" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 focus:bg-white transition-all">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Order Date</label>
                <input type="date" name="date" value="{{ request('date') }}" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 focus:bg-white transition-all">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="brand-btn-filter">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filter
                </button>
                @if(request('search') || request('status') || request('date'))
                <a href="{{ route('orders.index') }}" class="brand-btn-reset">
                    Clear
                </a>
                @endif
            </div>
        </div>

        <div class="text-xs font-semibold text-gray-500 self-end sm:self-center shrink-0">
            Total: <span class="text-gray-900 font-bold">{{ $orders->total() }}</span> orders
        </div>
    </form>
</div>

{{-- Orders Table --}}
<div class="brand-card rounded-2xl shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <div>
            <h2 class="font-bold text-gray-900 text-base">Sales Ledger</h2>
            <p class="text-xs text-gray-400">Order receipts and payment verification records</p>
        </div>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
            Page {{ $orders->currentPage() }} of {{ $orders->lastPage() }}
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50/75 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3.5 text-left">Order #</th>
                    <th class="px-5 py-3.5 text-left">Cashier</th>
                    <th class="px-5 py-3.5 text-center">Items</th>
                    <th class="px-5 py-3.5 text-right">Total Amount</th>
                    <th class="px-5 py-3.5 text-center">Payment</th>
                    <th class="px-5 py-3.5 text-center">Status</th>
                    <th class="px-5 py-3.5 text-right">Timestamp</th>
                    <th class="px-5 py-3.5 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($orders as $order)
                <tr class="hover:bg-heim-50/40 transition-colors">
                    <td class="px-5 py-4 font-mono font-bold text-heim-700">
                        <a href="{{ route('orders.show', $order) }}" class="hover:underline">
                            {{ $order->order_number }}
                        </a>
                    </td>
                    <td class="px-5 py-4 text-gray-700 font-medium">{{ $order->cashier_name }}</td>
                    <td class="px-5 py-4 text-center text-gray-600 font-semibold">{{ $order->orderItems->count() }}</td>
                    <td class="px-5 py-4 text-right font-black text-gray-900">₱{{ number_format($order->total, 2) }}</td>
                    <td class="px-5 py-4 text-center">
                        @if($order->payments->isNotEmpty())
                            <div class="flex flex-wrap items-center justify-center gap-1">
                            @foreach($order->payments->pluck('method')->unique() as $method)
                                @include('components.status-badge', ['status' => $method])
                            @endforeach
                            </div>
                            <div class="text-[10px] text-gray-500 mt-1">
                                {{ $order->payments->count() }} payment{{ $order->payments->count() === 1 ? '' : 's' }}
                                · ₱{{ number_format($order->paidAmount(), 2) }}
                            </div>
                            @if($order->payments->last()?->reference_number)
                                <div class="text-[10px] font-mono text-heim-800 font-semibold mt-1 tracking-wide uppercase" title="Payment Reference Number">
                                    Ref: {{ $order->payments->last()->reference_number }}
                                </div>
                            @endif
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-center">
                        @include('components.status-badge', ['status' => $order->status])
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="text-xs font-medium text-gray-700">{{ $order->created_at->copy()->timezone(config('app.business_timezone', 'Asia/Manila'))->format('M d, Y') }}</div>
                        <div class="text-[11px] text-gray-400">{{ $order->created_at->copy()->timezone(config('app.business_timezone', 'Asia/Manila'))->format('h:i A') }}</div>
                    </td>
                    <td class="px-5 py-4 text-right whitespace-nowrap">
                        <div class="inline-flex items-center gap-2">
                            <a href="{{ route('orders.receipt', $order) }}" target="_blank" title="Print Receipt" class="p-1.5 rounded-lg text-gray-400 hover:text-heim-700 hover:bg-heim-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            </a>
                            <a href="{{ route('orders.show', $order) }}" class="inline-flex items-center gap-1 text-xs text-heim-700 hover:text-heim-900 font-bold transition-colors">
                                View →
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <div class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-300 mb-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-700">No orders found</p>
                            <p class="text-xs text-gray-400">Transactions processed at the POS terminal will show up here.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($orders->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
        {{ $orders->links() }}
    </div>
    @endif
</div>
@endsection
