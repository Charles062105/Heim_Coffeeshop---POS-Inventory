@extends('layouts.app')
@section('title', 'Dashboard')
@section('header', 'Dashboard')
@section('subheader', 'Real-time coffee shop performance, active sales metrics, and stock alerts')

@section('header-actions')
    <a href="{{ route('pos.index') }}" class="brand-button gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        Open POS Terminal
    </a>
@endsection

@section('content')
@php 
    $u = auth()->user(); 
    $userRole = $u?->role ?? 'guest';
@endphp

{{-- ── TOP KPI CARDS ────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5 mb-6">
    
    @if($userRole === 'cashier')
        {{-- My Sales Today --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all">
            <div class="flex items-center justify-between gap-3 mb-3">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">My Payments Today</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="flex items-baseline justify-between gap-2">
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">₱{{ number_format($cashierTodaySales, 2) }}</p>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">
                    {{ $cashierTodayOrders }} {{ Str::plural('order', $cashierTodayOrders) }}
                </span>
            </div>
            <p class="text-xs text-gray-400 mt-2">Tender received today; refunds are reported separately</p>
        </div>

        {{-- My Orders Today --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all">
            <div class="flex items-center justify-between gap-3 mb-3">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">My Shift Orders</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">{{ $cashierTodayOrders }}</p>
            <p class="text-xs text-gray-400 mt-2">Customer tickets with payments today</p>
        </div>

        {{-- All-Time Sales Handled --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all">
            <div class="flex items-center justify-between gap-3 mb-3">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">All-Time Sales</span>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">₱{{ number_format($cashierAllTimeSales, 2) }}</p>
            <p class="text-xs text-gray-400 mt-2">Total revenue handled by {{ $u->name }}</p>
        </div>

        {{-- POS Quick Entry Card --}}
        <div class="bg-gradient-to-br from-heim-700 via-heim-800 to-heim-900 rounded-2xl p-5 text-white flex flex-col justify-between shadow-sm">
            <div>
                <span class="text-xs font-bold text-heim-200 uppercase tracking-wider">Point of Sale</span>
                <p class="font-bold text-lg text-white mt-1">Ready to serve?</p>
            </div>
            <a href="{{ route('pos.index') }}" class="inline-flex items-center justify-center gap-2 mt-4 bg-white text-heim-800 font-bold text-sm px-4 py-2.5 rounded-xl hover:bg-heim-50 transition-colors shadow-xs">
                <span>Open Register</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>
    @else
        {{-- Today's Sales --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all">
            <div class="flex items-center justify-between gap-3 mb-3">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Payments Received Today</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="flex items-baseline justify-between gap-2">
                <p class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">₱{{ number_format($todaySales, 2) }}</p>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">
                    {{ $todayOrders }} {{ Str::plural('order', $todayOrders) }}
                </span>
            </div>
            <p class="text-xs text-gray-400 mt-2">{{ $todayOrders }} {{ Str::plural('order', $todayOrders) }} with tender received today; refunds shown separately</p>
        </div>

        {{-- Total payments received --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all">
            <div class="flex items-center justify-between gap-3 mb-3">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Payments Received</span>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">₱{{ number_format($totalRevenue, 2) }}</p>
            <p class="text-xs text-gray-400 mt-2">All-time tender; refunds reported separately</p>
        </div>

        {{-- Low Stock --}}
        <a href="{{ route('inventory.index') }}?stock_status=low_stock" class="block bg-white rounded-2xl p-5 shadow-sm border {{ $lowStockCount > 0 ? 'border-amber-200 bg-amber-50/40 hover:bg-amber-50/70' : 'border-gray-100' }} hover:shadow-md transition-all group">
            <div class="flex items-center justify-between gap-3 mb-3">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Low Stock</span>
                <div class="w-10 h-10 rounded-xl {{ $lowStockCount > 0 ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-400' }} flex items-center justify-center shadow-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
            </div>
            <div class="flex items-baseline justify-between gap-2">
                <p class="text-2xl sm:text-3xl font-bold {{ $lowStockCount > 0 ? 'text-amber-700' : 'text-gray-900' }}">{{ $lowStockCount }}</p>
                @if($lowStockCount > 0)
                <span class="text-xs font-semibold text-amber-600 group-hover:underline">Needs restock →</span>
                @endif
            </div>
            <p class="text-xs text-gray-400 mt-2">Below minimum threshold</p>
        </a>

        {{-- Out of Stock --}}
        <a href="{{ route('inventory.index') }}?stock_status=out_of_stock" class="block bg-white rounded-2xl p-5 shadow-sm border {{ $outOfStockCount > 0 ? 'border-rose-200 bg-rose-50/40 hover:bg-rose-50/70' : 'border-gray-100' }} hover:shadow-md transition-all group">
            <div class="flex items-center justify-between gap-3 mb-3">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Out of Stock</span>
                <div class="w-10 h-10 rounded-xl {{ $outOfStockCount > 0 ? 'bg-rose-100 text-rose-700' : 'bg-gray-100 text-gray-400' }} flex items-center justify-center shadow-xs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
            </div>
            <div class="flex items-baseline justify-between gap-2">
                <p class="text-2xl sm:text-3xl font-bold {{ $outOfStockCount > 0 ? 'text-rose-700' : 'text-gray-900' }}">{{ $outOfStockCount }}</p>
                @if($outOfStockCount > 0)
                <span class="text-xs font-semibold text-rose-600 group-hover:underline">Critical →</span>
                @endif
            </div>
            <p class="text-xs text-gray-400 mt-2">Zero ingredients left</p>
        </a>
    @endif
</div>

{{-- ── QUICK ACTIONS STRIP (Item #3) ────────────────────────────────────────── --}}
<div class="mb-6">
    <div class="flex items-center justify-between mb-3 px-1">
        <div class="flex items-center gap-2">
            <div class="w-2 h-2 rounded-full bg-heim-600"></div>
            <h2 class="text-xs font-extrabold text-gray-500 uppercase tracking-wider">Quick Actions</h2>
        </div>
        <span class="text-xs text-gray-400 font-medium">Daily operations shortcuts</span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        {{-- 🛒 New Order --}}
        <a href="{{ route('pos.index') }}" 
           class="group flex items-center gap-3.5 p-3.5 rounded-2xl bg-gradient-to-br from-heim-700 via-heim-800 to-heim-900 text-white shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
            <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center shrink-0 text-white group-hover:scale-105 transition-transform">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-bold text-sm text-white flex items-center justify-between">
                    <span>🛒 New Order</span>
                    <span class="text-white/60 group-hover:text-white transition-colors">→</span>
                </p>
                <p class="text-xs text-heim-200 mt-0.5 truncate">Open register terminal</p>
            </div>
        </a>

        {{-- 📋 Orders --}}
        <a href="{{ route('orders.index') }}" 
           class="group flex items-center gap-3.5 p-3.5 rounded-2xl bg-white border border-gray-200/90 shadow-2xs hover:border-heim-300 hover:shadow-sm hover:-translate-y-0.5 transition-all">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-bold text-sm text-gray-900 flex items-center justify-between group-hover:text-heim-800 transition-colors">
                    <span>📋 Orders</span>
                    <span class="text-gray-300 group-hover:text-heim-600 transition-colors">→</span>
                </p>
                <p class="text-xs text-gray-400 mt-0.5 truncate">Review receipts & transactions</p>
            </div>
        </a>

        {{-- ＋ Stock-In --}}
        @if($u && $u->canManageInventory())
        <a href="{{ route('stock-in.index') }}" 
           class="group flex items-center gap-3.5 p-3.5 rounded-2xl bg-white border border-gray-200/90 shadow-2xs hover:border-heim-300 hover:shadow-sm hover:-translate-y-0.5 transition-all">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-bold text-sm text-gray-900 flex items-center justify-between group-hover:text-heim-800 transition-colors">
                    <span>＋ Stock-In</span>
                    <span class="text-gray-300 group-hover:text-heim-600 transition-colors">→</span>
                </p>
                <p class="text-xs text-gray-400 mt-0.5 truncate">Replenish kitchen stock</p>
            </div>
        </a>
        @else
        <div class="flex items-center gap-3.5 p-3.5 rounded-2xl bg-gray-50 border border-gray-100 opacity-60">
            <div class="w-10 h-10 rounded-xl bg-gray-200 text-gray-500 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-bold text-sm text-gray-500">＋ Stock-In</p>
                <p class="text-xs text-gray-400 mt-0.5">Manager access only</p>
            </div>
        </div>
        @endif

        {{-- ◉ Stock Overview --}}
        @if($u && $u->canManageInventory())
        <a href="{{ route('inventory.index') }}" 
           class="group flex items-center gap-3.5 p-3.5 rounded-2xl bg-white border border-gray-200/90 shadow-2xs hover:border-heim-300 hover:shadow-sm hover:-translate-y-0.5 transition-all">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8 4s8 1.79 8 4"/></svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-bold text-sm text-gray-900 flex items-center justify-between group-hover:text-heim-800 transition-colors">
                    <span>◉ Stock Overview</span>
                    <span class="text-gray-300 group-hover:text-heim-600 transition-colors">→</span>
                </p>
                <p class="text-xs text-gray-400 mt-0.5 truncate">Live ingredient inventory</p>
            </div>
        </a>
        @else
        <div class="flex items-center gap-3.5 p-3.5 rounded-2xl bg-gray-50 border border-gray-100 opacity-60">
            <div class="w-10 h-10 rounded-xl bg-gray-200 text-gray-500 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8 4s8 1.79 8 4"/></svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-bold text-sm text-gray-500">◉ Stock Overview</p>
                <p class="text-xs text-gray-400 mt-0.5">Manager access only</p>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- ── MAIN WORKSPACE SECTIONS ─────────────────────────────────────────────── --}}
<div class="space-y-6">

    {{-- ══════════════════════════════════════════════════════════════════════════
         SECTION 1: FINANCIAL & SALES INTELLIGENCE (8 cols + 4 cols)
         Sales Overview Chart (Left) + Payment Breakdown Tender (Right)
       ══════════════════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- ── 📈 Sales Overview Chart (Col 8) ─────────────────────────────── --}}
        <div class="lg:col-span-8 bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-gray-100 flex flex-col justify-between"
             x-data="{
                 activeTooltip: null,
                 tooltipData: { label: '', date: '', total: '0.00', count: 0, x: 0, y: 0 }
             }">
            
            {{-- Header & Scope Badge --}}
            <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-gray-100">
                <div>
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-heim-50 text-heim-700 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                        </div>
                        <h2 class="font-bold text-gray-900 text-base">Sales Overview</h2>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $userRole === 'cashier' ? 'Your tender received by payment date' : 'Tender received by payment date; refunds reported separately' }}</p>
                </div>
                
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-heim-50 text-heim-800 border border-heim-200/60">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>This Week</span>
                    </span>
                    @if($userRole !== 'cashier')
                        <a href="{{ route('reports.sales') }}" class="text-xs font-semibold text-heim-700 hover:text-heim-800 hover:underline">
                            Full Report →
                        </a>
                    @endif
                </div>
            </div>

            {{-- Summary Stats Pills --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 my-4">
                <div class="p-3 rounded-xl bg-gray-50/80 border border-gray-100">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">7-Day Total</span>
                    <span class="text-base sm:text-lg font-black text-gray-900 block mt-0.5">₱{{ number_format($weekTotal, 2) }}</span>
                </div>
                <div class="p-3 rounded-xl bg-gray-50/80 border border-gray-100">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Orders with Payments</span>
                    <span class="text-base sm:text-lg font-black text-gray-900 block mt-0.5">{{ $weekOrders }}</span>
                </div>
                <div class="p-3 rounded-xl bg-gray-50/80 border border-gray-100">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Daily Average</span>
                    <span class="text-base sm:text-lg font-black text-gray-900 block mt-0.5">₱{{ number_format($weekAverage, 2) }}</span>
                </div>
                <div class="p-3 rounded-xl bg-gray-50/80 border border-gray-100">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Peak Day</span>
                    <span class="text-base sm:text-lg font-black text-heim-700 block mt-0.5 truncate">
                        {{ $peakDay && $peakDay['total'] > 0 ? $peakDay['label'] . ' (₱' . number_format($peakDay['total'], 0) . ')' : 'None' }}
                    </span>
                </div>
            </div>

            {{-- SVG Chart Container --}}
            @php
                $maxVal = max(100, (float) $salesTrend->max('total'));
                $chartMax = $maxVal > 0 ? ceil(($maxVal * 1.25) / 100) * 100 : 1000;
                if ($chartMax < 500) $chartMax = 500;

                $points = [];
                $count = count($salesTrend);
                foreach ($salesTrend as $idx => $day) {
                    $x = 75 + ($idx * (550 / max(1, $count - 1)));
                    $ratio = $chartMax > 0 ? min(1, max(0, $day['total'] / $chartMax)) : 0;
                    $y = 170 - ($ratio * 135);
                    $points[] = [
                        'x'     => round($x, 1),
                        'y'     => round($y, 1),
                        'label' => $day['label'],
                        'date'  => $day['date'],
                        'total' => $day['total'],
                        'count' => $day['count'],
                    ];
                }

                $pathD = '';
                $areaD = '';
                if (!empty($points)) {
                    $pathD = "M {$points[0]['x']} {$points[0]['y']}";
                    for ($i = 0; $i < count($points) - 1; $i++) {
                        $p0 = $points[$i];
                        $p1 = $points[$i + 1];
                        $cx1 = round($p0['x'] + ($p1['x'] - $p0['x']) / 2, 1);
                        $cy1 = $p0['y'];
                        $cx2 = $cx1;
                        $cy2 = $p1['y'];
                        $pathD .= " C {$cx1} {$cy1}, {$cx2} {$cy2}, {$p1['x']} {$p1['y']}";
                    }
                    $areaD = $pathD . " L {$points[count($points)-1]['x']} 175 L {$points[0]['x']} 175 Z";
                }
            @endphp

            <div class="relative w-full aspect-[21/9] sm:aspect-[24/9] min-h-[190px] mt-2">
                <svg viewBox="0 0 700 220" class="w-full h-full overflow-visible" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="heimSalesGradient" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#155d49" stop-opacity="0.32" />
                            <stop offset="70%" stop-color="#3f947e" stop-opacity="0.08" />
                            <stop offset="100%" stop-color="#3f947e" stop-opacity="0.0" />
                        </linearGradient>
                    </defs>

                    {{-- Horizontal Grid Lines & Y-axis labels --}}
                    @for($g = 0; $g <= 3; $g++)
                        @php
                            $gridY = 170 - ($g * 45);
                            $gridVal = round(($chartMax / 3) * $g);
                        @endphp
                        <line x1="60" y1="{{ $gridY }}" x2="640" y2="{{ $gridY }}" stroke="#f1f5f9" stroke-width="1.5" stroke-dasharray="4 4" />
                        <text x="50" y="{{ $gridY + 4 }}" fill="#94a3b8" font-size="10" font-weight="600" text-anchor="end">
                            ₱{{ number_format($gridVal, 0) }}
                        </text>
                    @endfor

                    {{-- Area Fill --}}
                    @if(!empty($areaD))
                    <path d="{{ $areaD }}" fill="url(#heimSalesGradient)" />
                    @endif

                    {{-- Line Stroke --}}
                    @if(!empty($pathD))
                    <path d="{{ $pathD }}" fill="none" stroke="#155d49" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                    @endif

                    {{-- Data points & X-axis Labels --}}
                    @foreach($points as $idx => $pt)
                        {{-- X-axis day text --}}
                        <text x="{{ $pt['x'] }}" y="200" fill="#64748b" font-size="11" font-weight="700" text-anchor="middle">
                            {{ $pt['label'] }}
                        </text>
                        <text x="{{ $pt['x'] }}" y="214" fill="#94a3b8" font-size="9" font-weight="500" text-anchor="middle">
                            {{ $pt['date'] }}
                        </text>

                        {{-- Interactive Hover Column Target --}}
                        <rect x="{{ $pt['x'] - 28 }}" y="15" width="56" height="175" fill="transparent" class="cursor-pointer"
                              @mouseenter="activeTooltip = {{ $idx }}; tooltipData = { label: '{{ $pt['label'] }}', date: '{{ $pt['date'] }}', total: '{{ number_format($pt['total'], 2) }}', count: {{ $pt['count'] }}, x: {{ $pt['x'] }}, y: {{ $pt['y'] }} }"
                              @mouseleave="activeTooltip = null" />

                        {{-- Visible Circle Node --}}
                        <circle cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="5" fill="#ffffff" stroke="#155d49" stroke-width="2.5" class="transition-all duration-150" />
                        <circle cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="8" fill="#155d49" fill-opacity="0.15" class="pointer-events-none" />
                    @endforeach
                </svg>

                {{-- Interactive Floating HTML Tooltip --}}
                <div x-show="activeTooltip !== null"
                     x-cloak
                     class="absolute z-20 pointer-events-none transform -translate-x-1/2 -translate-y-full mb-3 px-3 py-2 rounded-xl bg-gray-900/95 text-white text-xs shadow-xl backdrop-blur-sm transition-all duration-75 border border-gray-700"
                     :style="`left: ${(tooltipData.x / 700) * 100}%; top: ${(tooltipData.y / 220) * 100}%;`">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-heim-300" x-text="`${tooltipData.label} · ${tooltipData.date}`"></span>
                    </div>
                    <div class="text-sm font-extrabold text-white mt-0.5" x-text="`₱${tooltipData.total}`"></div>
                    <div class="text-[10px] text-gray-400 mt-0.5" x-text="`${tooltipData.count} order(s) with payment`"></div>
                </div>
            </div>

            @if($weekTotal == 0)
            <div class="text-center py-2 text-xs text-gray-400">
                Waiting for payments this week to plot daily sales.
            </div>
            @endif
        </div>

        {{-- ── 💳 Payment Breakdown (Col 4) ─────────────────────────────────── --}}
        <div class="lg:col-span-4 bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-gray-100 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <div>
                            <h2 class="font-bold text-gray-900 text-sm uppercase tracking-wider">Payment Breakdown</h2>
                            <p class="text-[11px] text-gray-400">{{ $isTodayPaymentsEmpty ? 'All-time tender received; refunds reported separately' : "Tender received today; refunds reported separately" }}</p>
                        </div>
                    </div>
                    <span class="text-xs font-black text-heim-800 bg-heim-50 px-2.5 py-1 rounded-xl border border-heim-200/50">₱{{ number_format($paymentTotal, 2) }}</span>
                </div>

                <div class="space-y-4">
                    @php $pmTotal = max(0.01, $paymentTotal); @endphp
                    @forelse($paymentSummary as $pm)
                        @php 
                            $pct = round(($pm->total / $pmTotal) * 100);
                            $isCash = strtolower($pm->method) === 'cash';
                            $icon = $isCash ? '💵' : '📱';
                            $badgeColor = $isCash ? 'bg-emerald-500' : 'bg-blue-500';
                            $lightBg = $isCash ? 'bg-emerald-50 text-emerald-800 border-emerald-100' : 'bg-blue-50 text-blue-800 border-blue-100';
                            $label = $isCash ? 'Cash' : 'Online Payment';
                        @endphp
                        <div class="p-3 rounded-xl border {{ $lightBg }} transition-all">
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="font-bold text-gray-900 flex items-center gap-1.5 capitalize text-sm">
                                    <span>{{ $icon }}</span>
                                    <span>{{ $label }}</span>
                                    <span class="text-[11px] text-gray-400 font-normal">({{ $pm->count }} tx)</span>
                                </span>
                                <div class="text-right">
                                    <span class="font-extrabold text-gray-900 text-sm">₱{{ number_format($pm->total, 2) }}</span>
                                    <span class="text-[11px] font-bold text-gray-500 ml-1">({{ $pct }}%)</span>
                                </div>
                            </div>
                            <div class="w-full bg-white/80 rounded-full h-2 overflow-hidden border border-gray-200/50">
                                <div class="h-2 rounded-full {{ $badgeColor }} transition-all duration-500" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-xs text-gray-400">
                            No payment transactions recorded yet.
                        </div>
                    @endforelse
                </div>
            </div>

            @if($userRole !== 'cashier')
                <div class="pt-4 border-t border-gray-100 text-center">
                    <a href="{{ route('reports.sales') }}" class="text-xs font-bold text-heim-700 hover:text-heim-800 hover:underline">
                        Detailed Tender Breakdown & History →
                    </a>
                </div>
            @endif
        </div>

    </div>

    {{-- ══════════════════════════════════════════════════════════════════════════
         SECTION 2: LIVE STORE OPERATIONS & STAFF ACTIVITY (8 cols + 4 cols)
         Recent Orders Table (Left) + Chronological Staff Activity (Right)
       ══════════════════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- ── 📋 Recent Orders Table (Col 8) ───────────────────────────────── --}}
        <div class="{{ $userRole === 'cashier' ? 'lg:col-span-12' : 'lg:col-span-8' }} bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-heim-50 text-heim-700 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-900 text-base">Recent Orders</h2>
                        <p class="text-xs text-gray-400">{{ $userRole === 'cashier' ? 'Your latest customer tickets' : 'Latest settled customer tickets' }}</p>
                    </div>
                </div>
                @if($userRole !== 'cashier')
                    <a href="{{ route('orders.index') }}" class="text-xs font-semibold text-heim-700 hover:text-heim-800 hover:underline flex items-center gap-1">
                        <span>View all orders</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @endif
            </div>

            <div class="overflow-x-auto flex-1">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50/80 text-xs font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100">
                        <tr>
                            <th class="px-5 py-3 text-left">Order #</th>
                            <th class="px-5 py-3 text-left">Cashier</th>
                            <th class="px-5 py-3 text-right">Total</th>
                            <th class="px-5 py-3 text-center">Status</th>
                            <th class="px-5 py-3 text-right">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($recentOrders as $order)
                        <tr class="hover:bg-gray-50/80 transition-colors">
                            <td class="px-5 py-3.5 font-bold text-heim-700">
                                <a href="{{ route('orders.show', $order) }}" class="hover:underline flex items-center gap-1.5">
                                    <span>#{{ $order->order_number }}</span>
                                </a>
                            </td>
                            <td class="px-5 py-3.5 text-gray-800 text-xs font-semibold">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-full bg-heim-50 text-heim-800 text-[10px] font-bold inline-flex items-center justify-center">
                                        {{ strtoupper(substr($order->cashier_name ?: 'C', 0, 1)) }}
                                    </span>
                                    <span>{{ $order->cashier_name ?: 'Cashier' }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-right font-black text-gray-900">₱{{ number_format($order->total, 2) }}</td>
                            <td class="px-5 py-3.5 text-center">
                                @include('components.status-badge', ['status' => $order->status])
                            </td>
                            <td class="px-5 py-3.5 text-right text-gray-400 text-xs font-medium">{{ $order->created_at->copy()->timezone(config('app.business_timezone', 'Asia/Manila'))->format('h:i A') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-50 text-gray-400">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9h6m-6 4h6"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-700">No orders recorded yet today</p>
                                    <p class="mt-0.5 text-xs text-gray-400">Create a transaction in POS to start tracking sales.</p>
                                    <a href="{{ route('pos.index') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-heim-600 px-4 py-2 text-xs font-bold text-white shadow-xs transition hover:bg-heim-700">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                        <span>New Order</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── 📝 Recent Activity (Col 4) ───────────────────────────────────── --}}
        @if($userRole !== 'cashier')
        <div class="lg:col-span-4 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-900 text-xs uppercase tracking-wider">Recent Activity</h2>
                        <p class="text-[11px] text-gray-400">Live chronological audit trail</p>
                    </div>
                </div>
                <a href="{{ route('audit-logs.index') }}" class="text-xs font-semibold text-heim-700 hover:underline">
                    View logs →
                </a>
            </div>

            <div class="divide-y divide-gray-50 flex-1 overflow-y-auto max-h-[380px]">
                @forelse($recentLogs as $log)
                    <div class="px-5 py-3 flex items-start justify-between gap-3 text-xs hover:bg-gray-50/80 transition-colors">
                        <div class="flex items-start gap-2.5 min-w-0">
                            <div class="w-7 h-7 rounded-full bg-gray-100 text-gray-700 text-xs font-bold shrink-0 flex items-center justify-center mt-0.5">
                                {{ strtoupper(substr($log->actor_name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-gray-900 truncate">
                                    {{ $log->actor_name }}
                                    @if($log->actor_role)
                                        <span class="text-[10px] font-normal text-gray-400 capitalize">({{ $log->actor_role }})</span>
                                    @endif
                                </p>
                                <p class="text-gray-600 font-medium text-xs mt-0.5">{{ $log->action }}</p>
                            </div>
                        </div>
                        <span class="text-[11px] text-gray-400 font-medium shrink-0 whitespace-nowrap pt-0.5">
                            {{ $log->time_str }}
                        </span>
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-gray-400">
                        No activity recorded in the audit trail yet.
                    </div>
                @endforelse
            </div>

            <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/50 text-right">
                <a href="{{ route('audit-logs.index') }}" class="text-xs font-bold text-heim-700 hover:underline">
                    Full Audit History →
                </a>
            </div>
        </div>
        @endif

    </div>

    {{-- ══════════════════════════════════════════════════════════════════════════
         SECTION 3: INVENTORY HEALTH & DAILY CONSUMPTION (6 cols + 6 cols)
         Inventory Alerts (Left) + Recipe Deductions Today (Right)
       ══════════════════════════════════════════════════════════════════════════ --}}
    @if($userRole !== 'cashier')
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- ── 🚨 INVENTORY ALERTS (Col 6) ─────────────────────────────────── --}}
        <div class="lg:col-span-6 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 bg-amber-50/40">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-800 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-amber-950 text-xs uppercase tracking-wider">Inventory Alerts</h2>
                        <p class="text-[10px] text-amber-700">Reorder thresholds & stock health</p>
                    </div>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold {{ ($lowStockCount + $outOfStockCount) > 0 ? 'bg-amber-200 text-amber-900' : 'bg-emerald-100 text-emerald-800' }}">
                    {{ $lowStockCount + $outOfStockCount }}
                </span>
            </div>

            <div class="divide-y divide-gray-50 flex-1">
                @forelse($inventoryAlerts->take(5) as $item)
                    @php 
                        $isOut = $item->getStockStatus() === 'out_of_stock';
                        $currStock = $item->getCurrentStock();
                    @endphp
                    <div class="p-4 hover:bg-gray-50/70 transition-colors">
                        <div class="flex items-start justify-between gap-2 mb-1">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0 {{ $isOut ? 'bg-rose-500 ring-4 ring-rose-100 animate-pulse' : 'bg-amber-500 ring-4 ring-amber-100' }}"></span>
                                <span class="font-bold text-gray-900 text-sm truncate">{{ $item->name }}</span>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wide shrink-0 {{ $isOut ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                {{ $isOut ? 'Out of Stock' : 'Low Stock' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs mt-1.5 pl-4">
                            <span class="{{ $isOut ? 'text-rose-600 font-bold' : 'text-amber-800 font-bold' }}">
                                {{ number_format($currStock, 2) }} {{ $item->unit }} remaining
                            </span>
                            <span class="text-gray-400 text-[11px]">
                                Reorder level: {{ $item->minimum_stock }} {{ $item->unit }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center flex flex-col items-center justify-center h-full">
                        <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mb-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <p class="font-bold text-gray-800 text-sm">All stock levels healthy</p>
                        <p class="text-xs text-gray-400 mt-0.5">Zero ingredients below reorder threshold.</p>
                    </div>
                @endforelse
            </div>

            <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/50 flex items-center justify-between text-xs">
                <span class="text-gray-400">Total active catalog: {{ \App\Models\Ingredient::active()->count() }} items</span>
                <a href="{{ route('inventory.index') }}" class="font-bold text-heim-700 hover:text-heim-800 hover:underline">
                    View inventory →
                </a>
            </div>
        </div>

        {{-- ── ⚖️ DAILY CONSUMPTION (Col 6) ─────────────────────────────────── --}}
        <div class="lg:col-span-6 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-teal-50 text-teal-700 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-900 text-xs uppercase tracking-wider">Daily Consumption</h2>
                        <p class="text-[11px] text-gray-400">Today's POS ingredient usage, net of sales returns</p>
                    </div>
                </div>
                <a href="{{ route('consumption.index') }}" class="text-xs font-semibold text-heim-700 hover:underline">
                    View net report →
                </a>
            </div>

            <div class="divide-y divide-gray-50 flex-1">
                @forelse($todayConsumption as $consumed)
                    <div class="px-5 py-3.5 flex items-center justify-between text-xs hover:bg-gray-50/80 transition-colors">
                        <div class="flex items-center gap-3">
                            <span class="w-2.5 h-2.5 rounded-full bg-teal-500 ring-4 ring-teal-50"></span>
                            <div>
                                <p class="font-bold text-gray-900 text-sm">{{ $consumed->name }}</p>
                                <p class="text-[10px] text-gray-400">Stock on hand: {{ number_format($consumed->current_stock, 3) }} {{ $consumed->unit }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="font-black text-gray-900 text-sm">{{ number_format($consumed->consumed, 3) }} {{ $consumed->unit }}</span>
                            <span class="block text-[10px] font-semibold text-teal-600">Deducted today</span>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center flex flex-col items-center justify-center h-full">
                        <div class="w-10 h-10 rounded-full bg-gray-50 text-gray-400 flex items-center justify-center mb-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <p class="font-bold text-gray-700 text-sm">No recipe consumption recorded yet today</p>
                        <p class="text-xs text-gray-400 mt-0.5">Completed orders will automatically deduct recipe ingredients here.</p>
                        <a href="{{ route('consumption.index') }}" class="mt-3 text-xs font-bold text-heim-700 hover:underline">
                            Inspect net consumption report →
                        </a>
                    </div>
                @endforelse
            </div>

            <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/50 flex items-center justify-between text-xs">
                <span class="text-gray-400">Live POS usage, net of returns</span>
                <a href="{{ route('consumption.index') }}" class="font-bold text-heim-700 hover:underline">
                    View report →
                </a>
            </div>
        </div>
        @endif

    </div>

</div>
@endsection
