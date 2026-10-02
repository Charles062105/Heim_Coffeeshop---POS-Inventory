@extends('layouts.app')
@section('title', 'Debt Management')
@section('header', 'Debt Management')
@section('subheader', 'Track Pay Later orders and collect outstanding balances')

@section('header-actions')
    <a href="{{ route('pos.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 shadow-xs transition-all">
        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        Back to POS
    </a>
@endsection

@section('content')
<div class="space-y-6">

    {{-- KPI Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="brand-card rounded-2xl p-5 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-gray-500 mb-1">Total Outstanding</p>
            <p class="text-2xl font-extrabold text-rose-600 font-mono">₱{{ number_format($stats['total_balance'], 2) }}</p>
        </div>
        <div class="brand-card rounded-2xl p-5 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-gray-500 mb-1">Pending</p>
            <p class="text-2xl font-extrabold text-amber-600 font-mono">{{ $stats['total_pending'] }}</p>
        </div>
        <div class="brand-card rounded-2xl p-5 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-gray-500 mb-1">Overdue</p>
            <p class="text-2xl font-extrabold text-rose-700 font-mono">{{ $stats['total_overdue'] }}</p>
        </div>
        <div class="brand-card rounded-2xl p-5 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-gray-500 mb-1">Fully Paid</p>
            <p class="text-2xl font-extrabold text-emerald-600 font-mono">{{ $stats['total_paid'] }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="brand-card rounded-2xl p-4 shadow-sm">
        <div class="flex flex-col sm:flex-row gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search customer name, phone, order #..."
                class="flex-1 text-sm px-3.5 py-2 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-heim-500">
            <select name="status" class="text-sm px-3.5 py-2 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-heim-500">
                <option value="">All Statuses</option>
                <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                <option value="partially_paid" @selected(request('status') === 'partially_paid')>Partially Paid</option>
                <option value="overdue" @selected(request('status') === 'overdue')>Overdue</option>
                <option value="paid" @selected(request('status') === 'paid')>Paid</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-heim-600 text-white text-sm font-semibold rounded-xl hover:bg-heim-700 transition-colors">
                Filter
            </button>
            @if(request()->hasAny(['search', 'status']))
            <a href="{{ route('debts.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 font-semibold rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors">
                Clear
            </a>
            @endif
        </div>
    </form>

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

    {{-- Debts Table --}}
    <div class="brand-card rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-gray-900 text-base">Charge Accounts</h2>
                <p class="text-xs text-gray-400">All Pay Later / Charge orders and their payment status</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
                {{ $debts->total() }} records
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50/75 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3.5 text-left">Customer</th>
                        <th class="px-5 py-3.5 text-left">Order #</th>
                        <th class="px-5 py-3.5 text-right">Original</th>
                        <th class="px-5 py-3.5 text-right">Paid</th>
                        <th class="px-5 py-3.5 text-right">Balance</th>
                        <th class="px-5 py-3.5 text-center">Due Date</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($debts as $debt)
                    <tr class="hover:bg-heim-50/40 transition-colors">
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-900">{{ $debt->customer_name }}</p>
                            @if($debt->customer_phone)
                                <p class="text-xs text-gray-400">{{ $debt->customer_phone }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            @if($debt->order)
                                <a href="{{ route('orders.show', $debt->order) }}" class="font-mono font-bold text-heim-700 hover:underline text-xs">
                                    {{ $debt->order->order_number }}
                                </a>
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right font-mono font-semibold text-gray-700">₱{{ number_format($debt->original_amount, 2) }}</td>
                        <td class="px-5 py-4 text-right font-mono text-emerald-700 font-semibold">₱{{ number_format($debt->amount_paid, 2) }}</td>
                        <td class="px-5 py-4 text-right font-mono font-extrabold {{ $debt->status === 'paid' ? 'text-gray-400' : 'text-rose-700' }}">
                            ₱{{ number_format($debt->balance, 2) }}
                        </td>
                        <td class="px-5 py-4 text-center">
                            @if($debt->due_date)
                                <span class="text-xs {{ $debt->due_date->isPast() && $debt->status !== 'paid' ? 'text-rose-600 font-bold' : 'text-gray-600' }}">
                                    {{ $debt->due_date->format('M d, Y') }}
                                </span>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $debt->statusColor() }}">
                                {{ $debt->statusLabel() }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('debts.show', $debt) }}"
                                class="inline-flex items-center px-3 py-1.5 bg-heim-50 text-heim-700 hover:bg-heim-100 rounded-xl text-xs font-bold transition-colors border border-heim-200">
                                Manage →
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-14 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-300 mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                </div>
                                <p class="text-sm font-semibold text-gray-700">No debts found</p>
                                <p class="text-xs text-gray-400 mt-1">Pay Later orders will be listed here.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($debts->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
            {{ $debts->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
