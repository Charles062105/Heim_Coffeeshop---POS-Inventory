@extends('layouts.app')
@section('title', 'Void Transactions')
@section('header', 'Void Transactions & History')
@section('subheader', 'Audit trail of manager- and owner-authorized order and item cancellations')

@section('content')
<div class="space-y-6">

    {{-- Filter Bar --}}
    <form method="GET" action="{{ route('voids.index') }}" class="brand-card rounded-2xl p-4 shadow-sm flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
        <div class="flex-1 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[12rem]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by Order #, Cashier, Authorizer..."
                    class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-heim-500 bg-gray-50/50 focus:bg-white transition-all">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <div class="w-36">
                <select name="void_type" class="w-full py-2 text-xs rounded-xl border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-heim-500">
                    <option value="">All Types</option>
                    <option value="order" {{ request('void_type') === 'order' ? 'selected' : '' }}>Full Order</option>
                    <option value="item" {{ request('void_type') === 'item' ? 'selected' : '' }}>Single Item</option>
                </select>
            </div>

            <div class="flex items-center gap-1.5">
                <input type="date" name="from" value="{{ request('from') }}" title="From Date"
                    class="py-1.5 px-2 text-xs rounded-xl border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-heim-500">
                <span class="text-xs text-gray-400">to</span>
                <input type="date" name="to" value="{{ request('to') }}" title="To Date"
                    class="py-1.5 px-2 text-xs rounded-xl border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-heim-500">
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="submit" class="brand-btn-filter text-xs">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter
            </button>
            @if(request()->hasAny(['search', 'void_type', 'from', 'to']))
            <a href="{{ route('voids.index') }}" class="brand-btn-reset text-xs">Reset</a>
            @endif
        </div>
    </form>

    {{-- History Table --}}
    <div class="brand-card rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-gray-900 text-base">Void Transaction Records</h2>
                <p class="text-xs text-gray-400">Manager- and owner-authorized transaction cancellations</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
                {{ $voidLogs->total() }} records
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50/75 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3.5 text-left">Order #</th>
                        <th class="px-5 py-3.5 text-left">Type / Target</th>
                        <th class="px-5 py-3.5 text-left">Reason</th>
                        <th class="px-5 py-3.5 text-left">Cashier</th>
                        <th class="px-5 py-3.5 text-left">Requested By</th>
                        <th class="px-5 py-3.5 text-left">Authorized By</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5 text-center">Stock Restored</th>
                        <th class="px-5 py-3.5 text-right">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($voidLogs as $void)
                    <tr class="hover:bg-heim-50/40 transition-colors">
                        <td class="px-5 py-4">
                            @if($void->order)
                                <a href="{{ route('orders.show', $void->order) }}" class="font-mono font-bold text-heim-700 hover:underline">
                                    {{ $void->order->order_number }}
                                </a>
                            @else
                                <span class="font-mono text-gray-400">#{{ $void->order_id }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            @if($void->void_type === 'order')
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                                    Full Order Void
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                    Item Void
                                </span>
                                @if($void->orderItem)
                                    <p class="text-xs font-semibold text-gray-800 mt-1">
                                        {{ $void->orderItem->product?->name ?? 'Product' }} ({{ $void->orderItem->size?->size_name ?? 'Size' }})
                                    </p>
                                    <p class="text-[11px] text-gray-400">Qty: {{ $void->orderItem->quantity }}</p>
                                @endif
                            @endif
                        </td>
                        <td class="px-5 py-4 text-xs text-gray-700 max-w-xs">
                            <span title="{{ $void->reason }}">{{ $void->reason }}</span>
                        </td>
                        <td class="px-5 py-4 text-xs font-medium text-gray-700">
                            {{ $void->cashier_name }}
                        </td>
                        <td class="px-5 py-4 text-xs">
                            <span class="font-semibold text-gray-800">{{ $void->requested_by ?? $void->cashier_name }}</span>
                            @if($void->requested_role)<span class="text-gray-400 capitalize">({{ $void->requested_role }})</span>@endif
                        </td>
                        <td class="px-5 py-4 text-xs">
                            <span class="font-bold text-gray-900">{{ $void->authorized_by }}</span>
                            <span class="text-gray-400 capitalize ml-1">({{ $void->authorized_role }})</span>
                        </td>
                        <td class="px-5 py-4 text-center">
                            <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-bold text-rose-800">Voided</span>
                        </td>
                        <td class="px-5 py-4 text-center">
                            @if($void->stock_restored)
                                <span class="inline-flex items-center bg-heim-100 text-heim-700 text-xs px-2.5 py-0.5 rounded-full font-bold">Yes</span>
                            @else
                                <span class="text-gray-300 text-xs font-medium">No</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="text-xs font-medium text-gray-700">
                                {{ $void->voided_at ? $void->voided_at->copy()->timezone(config('app.business_timezone', 'Asia/Manila'))->format('M d, Y') : '—' }}
                            </div>
                            <div class="text-[11px] text-gray-400">
                                {{ $void->voided_at ? $void->voided_at->copy()->timezone(config('app.business_timezone', 'Asia/Manila'))->format('h:i A') : '' }}
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-300 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <p class="text-sm font-semibold text-gray-700">No voided transactions recorded</p>
                                <p class="text-xs text-gray-400">All manager- and owner-authorized void operations will appear here.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($voidLogs->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
            {{ $voidLogs->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
