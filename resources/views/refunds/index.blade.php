@extends('layouts.app')
@section('title', 'Refunds')
@section('header', 'Refunds & Cancellations')
@section('subheader', 'Audit authorized refunds, voided transactions, and stock restoration logs')

@section('content')
<div class="space-y-6">

    {{-- History table --}}
    <div class="brand-card rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-gray-900 text-base">Refund History</h2>
                <p class="text-xs text-gray-400">Chronological ledger of approved refunds and cancellations</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
                {{ $refunds->total() }} records
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50/75 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3.5 text-left">Order #</th>
                        <th class="px-5 py-3.5 text-right">Amount</th>
                        <th class="px-5 py-3.5 text-center">Method / Status</th>
                        <th class="px-5 py-3.5 text-left">Reason / Context</th>
                        <th class="px-5 py-3.5 text-left">Authorized By</th>
                        <th class="px-5 py-3.5 text-center">Stock Restored</th>
                        <th class="px-5 py-3.5 text-right">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($refunds as $refund)
                    <tr class="hover:bg-heim-50/40 transition-colors">
                        <td class="px-5 py-4">
                            <a href="{{ route('orders.show', $refund->order) }}" class="font-mono font-bold text-heim-700 hover:underline">
                                {{ $refund->order?->order_number ?? '—' }}
                            </a>
                        </td>
                        <td class="px-5 py-4 text-right font-black text-rose-700">₱{{ number_format($refund->amount, 2) }}</td>
                        <td class="px-5 py-4 text-center text-xs">
                            <span class="font-semibold">{{ ucfirst($refund->method) }}</span>
                            <span class="block {{ $refund->status === 'processing' ? 'text-amber-700' : 'text-emerald-700' }}">{{ ucfirst($refund->status) }}</span>
                        </td>
                        <td class="px-5 py-4 text-gray-600 text-xs max-w-xs truncate" title="{{ $refund->reason }}">{{ $refund->reason ?? '—' }}</td>
                        <td class="px-5 py-4 text-xs">
                            <span class="font-bold text-gray-900">{{ $refund->authorized_by }}</span>
                            <span class="text-gray-400 capitalize ml-1">({{ $refund->authorized_role }})</span>
                        </td>
                        <td class="px-5 py-4 text-center">
                            @if($refund->stock_restored)
                                <span class="inline-flex items-center bg-heim-100 text-heim-700 text-xs px-2.5 py-0.5 rounded-full font-bold">Yes</span>
                            @else
                                <span class="text-gray-300 text-xs font-medium">No</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="text-xs font-medium text-gray-700">
                                {{ $refund->refunded_at ? \Carbon\Carbon::parse($refund->refunded_at)->format('M d, Y') : '—' }}
                            </div>
                            <div class="text-[11px] text-gray-400">
                                {{ $refund->refunded_at ? \Carbon\Carbon::parse($refund->refunded_at)->format('h:i A') : '' }}
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-300 mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                </div>
                                <p class="text-sm font-semibold text-gray-700">No refunds recorded</p>
                                <p class="text-xs text-gray-400">Authorized refunds and voided tickets will be cataloged here.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($refunds->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
            {{ $refunds->links() }}
        </div>
        @endif
    </div>

    {{-- Eligible cash orders queue --}}
    <div class="brand-card rounded-2xl shadow-sm p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
            <h2 class="font-bold text-gray-900 text-base">Eligible Cash Transactions</h2>
            <a href="{{ route('orders.index') }}?status=completed" class="text-xs text-heim-700 hover:text-heim-900 font-bold transition-colors">
                View All Completed Orders →
            </a>
        </div>
        <p class="text-xs text-gray-400 mb-4">Only settled <strong class="text-gray-700">Cash</strong> transactions are eligible for cash refund. Online payments cannot be returned in cash.</p>

        @if(isset($eligibleOrders) && $eligibleOrders->count() > 0)
        <div class="overflow-x-auto border border-gray-100 rounded-xl">
            <table class="w-full text-sm">
                <thead class="bg-gray-50/75 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3 text-left">Order #</th>
                        <th class="px-5 py-3 text-left">Cashier</th>
                        <th class="px-5 py-3 text-right">Total Amount</th>
                        <th class="px-5 py-3 text-center">Payment</th>
                        <th class="px-5 py-3 text-right">Timestamp</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($eligibleOrders->take(10) as $ord)
                    <tr class="hover:bg-heim-50/40 transition-colors">
                        <td class="px-5 py-3 font-mono font-bold text-heim-700">{{ $ord->order_number }}</td>
                        <td class="px-5 py-3 text-gray-600 text-xs font-medium">{{ $ord->cashier_name }}</td>
                        <td class="px-5 py-3 text-right font-black text-gray-900">₱{{ number_format($ord->total, 2) }}</td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-700">Cash</span>
                        </td>
                        <td class="px-5 py-3 text-right text-xs text-gray-400">{{ $ord->created_at->format('M d, Y h:i A') }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('orders.show', $ord) }}" class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-xl text-xs font-bold transition-colors">
                                Process →
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-xs text-gray-400 py-6 text-center border-2 border-dashed border-gray-200 rounded-xl">
            No eligible completed cash orders available for refund.
        </p>
        @endif
    </div>

</div>
@endsection
