@extends('layouts.app')
@section('title', 'Owner Decision Dashboard')
@section('header', 'Owner Decision Dashboard & Product Performance')
@section('subheader', 'Evidence-based product analytics, branch comparisons, AI recommendations, and documented action tracking')

@section('header-actions')
    <div class="flex items-center gap-2">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-emerald-800 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 shadow-xs transition-all print:hidden">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Export Performance CSV
        </a>
        <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm transition-all print:hidden">
            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print
        </button>
        <button onclick="openNewActionModal()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-heim-700 hover:bg-heim-800 shadow-sm transition-all print:hidden">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Record Product Action
        </button>
    </div>
@endsection

@section('content')
{{-- Flash Alerts --}}
@if(session('success'))
<div class="mb-5 rounded-2xl bg-emerald-50 border border-emerald-200/80 p-4 text-sm text-emerald-800 flex items-center justify-between shadow-xs">
    <div class="flex items-center gap-2.5">
        <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="font-semibold">{{ session('success') }}</span>
    </div>
    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900">&times;</button>
</div>
@endif

@if(isset($errors) && $errors->any())
<div class="mb-5 rounded-2xl bg-rose-50 border border-rose-200/80 p-4 text-sm text-rose-800 shadow-xs">
    <div class="font-bold mb-1">Please correct the following:</div>
    <ul class="list-disc list-inside text-xs space-y-0.5">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- Section 2: Comprehensive Filter Toolbar --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6 print:hidden">
    <form method="GET" action="{{ route('reports.decision-dashboard.index') }}" class="flex flex-wrap items-end gap-3" id="analytics-filter-form">
        {{-- Reporting Period --}}
        <div class="w-44">
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Reporting Period</label>
            <select name="type" id="report-type" onchange="toggleDateInputs(this.value)"
                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 focus:bg-white transition-all font-medium">
                @foreach(['daily'=>'Daily Comparison','weekly'=>'Weekly Comparison','monthly'=>'Monthly Comparison','yearly'=>'Yearly Comparison','custom'=>'Custom Date Range','all'=>'All Time Data'] as $val => $label)
                <option value="{{ $val }}" {{ $type === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Branch Selection --}}
        @if(auth()->user()->isOwner())
        <div class="w-44">
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Branch Scope</label>
            <select name="branch_id" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 focus:bg-white transition-all font-medium">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" @selected((string)$scopedBranchId === (string)$b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        @else
        <div class="w-44">
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Branch</label>
            <div class="w-full bg-gray-100 border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-700 font-semibold truncate">
                {{ auth()->user()->branch?->name ?? 'Assigned Branch' }}
            </div>
            <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
        </div>
        @endif

        {{-- Channel Selection --}}
        <div class="w-36">
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Sales Channel</label>
            <select name="channel" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 focus:bg-white transition-all font-medium">
                <option value="all" @selected($channel === 'all')>All Channels</option>
                <option value="pos" @selected($channel === 'pos')>POS Only</option>
                <option value="grab" @selected($channel === 'grab')>Grab Orders</option>
            </select>
        </div>

        {{-- Category Filter --}}
        <div class="w-40">
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Category</label>
            <select name="category_id" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 focus:bg-white transition-all font-medium">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected((string)$categoryId === (string)$cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Product Filter --}}
        <div class="w-44">
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Individual Product</label>
            <select name="product_id" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 focus:bg-white transition-all font-medium">
                <option value="">All Products</option>
                @foreach($productsList as $p)
                    <option value="{{ $p->id }}" @selected((string)$productId === (string)$p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Ranking Metric --}}
        <div class="w-36">
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Rank By</label>
            <select name="ranking_by" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 focus:bg-white transition-all font-medium">
                <option value="units" @selected($rankingBy === 'units')>Units Sold</option>
                <option value="revenue" @selected($rankingBy === 'revenue')>Net Revenue</option>
            </select>
        </div>

        {{-- Anchor Date / Custom Range --}}
        <div id="date-input" class="{{ $type === 'custom' || $type === 'all' ? 'hidden' : '' }}">
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Anchor Date</label>
            <input type="date" name="date" value="{{ $date }}"
                class="bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 focus:bg-white transition-all">
        </div>

        <div id="range-inputs" class="{{ $type === 'custom' ? '' : 'hidden' }} flex gap-2">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">From</label>
                <input type="date" name="from" value="{{ $from }}" {{ $type === 'custom' ? 'required' : '' }}
                    class="bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 focus:bg-white transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">To</label>
                <input type="date" name="to" value="{{ $to }}" {{ $type === 'custom' ? 'required' : '' }}
                    class="bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 focus:bg-white transition-all">
            </div>
        </div>

        <button type="submit" class="brand-btn-filter">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Apply Filters
        </button>

        <div class="ml-auto flex items-center gap-2 self-center text-xs font-semibold px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200">
            <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Comparing: <span class="font-black">{{ $dateWindows['label'] }}</span>
        </div>
    </form>
</div>

{{-- Section 1: Executive KPI Overview (Consolidated & Comparison) --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    {{-- Consolidated Net Sales --}}
    <div class="bg-gradient-to-br from-heim-800 to-heim-950 rounded-2xl p-5 text-white shadow-sm relative overflow-hidden group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-heim-200 uppercase tracking-wider mb-1">Consolidated Net Sales</p>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-black">₱{{ number_format($branchSummary['total_sales'], 2) }}</span>
                </div>
                <div class="mt-2 text-xs flex items-center gap-1.5 font-medium">
                    @if($branchSummary['growth_percent'] !== null)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md font-bold text-[10px] {{ $branchSummary['growth_percent'] >= 0 ? 'bg-emerald-400/20 text-emerald-200' : 'bg-rose-400/20 text-rose-200' }}">
                            {{ $branchSummary['growth_percent'] >= 0 ? '+' : '' }}{{ $branchSummary['growth_percent'] }}%
                        </span>
                        <span class="text-heim-300 text-[11px]">vs prior (₱{{ number_format($branchSummary['prev_total_sales'], 2) }})</span>
                    @else
                        <span class="text-heim-300 text-[11px]">Baseline period</span>
                    @endif
                </div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>

    {{-- Total Volume Units --}}
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm relative overflow-hidden group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Products Sold</p>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-black text-gray-900">{{ number_format($branchSummary['total_units']) }}</span>
                    <span class="text-xs text-gray-400 font-bold">units</span>
                </div>
                <p class="text-xs text-gray-500 mt-2">Across <span class="font-bold text-gray-800">{{ number_format($branchSummary['total_orders']) }}</span> paid orders</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4v10l8 4 8-4V7z"/></svg>
            </div>
        </div>
    </div>

    {{-- Open Action Tracker --}}
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm relative overflow-hidden group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Documented Actions</p>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-black text-indigo-900">{{ $openActions->count() }}</span>
                    <span class="text-xs text-indigo-600 font-bold">open</span>
                </div>
                <p class="text-xs text-gray-500 mt-2"><span class="font-bold text-emerald-700">{{ $completedActions->count() }}</span> completed &amp; evaluated</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-700 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            </div>
        </div>
    </div>

    {{-- Financial Reconciliation Warning Summary --}}
    <div class="bg-white rounded-2xl p-5 border {{ $reconciliation['open_balance_total'] > 0 || $reconciliation['unbalanced_shift_count'] > 0 ? 'border-amber-200 bg-amber-50/30' : 'border-gray-100' }} shadow-sm relative overflow-hidden group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Financial Reconciliation</p>
                <div class="flex items-baseline gap-2">
                    <span class="text-xl sm:text-2xl font-black {{ $reconciliation['open_balance_total'] > 0 ? 'text-amber-800' : 'text-gray-900' }}">
                        ₱{{ number_format($reconciliation['open_balance_total'], 2) }}
                    </span>
                    <span class="text-xs text-gray-400 font-bold">open</span>
                </div>
                <p class="text-[11px] text-gray-500 mt-2">
                    {{ $reconciliation['unbalanced_shift_count'] }} shift variance(s) &bull; {{ $reconciliation['void_count'] }} void(s)
                </p>
            </div>
            <div class="w-12 h-12 rounded-2xl {{ $reconciliation['open_balance_total'] > 0 ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600' }} flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
        </div>
    </div>
</div>

{{-- Financial Warnings Banner (if any) --}}
@if(!empty($reconciliation['warnings']))
<div class="bg-amber-50/70 border border-amber-200 rounded-2xl p-4 mb-6">
    <div class="flex items-center gap-2 text-amber-900 font-bold text-sm mb-2">
        <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <span>Financial &amp; Cash Reconciliation Alerts for Selected Window</span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
        @foreach($reconciliation['warnings'] as $warn)
        <div class="bg-white rounded-xl p-3 border border-amber-100 shadow-xs">
            <div class="font-bold text-gray-900 flex justify-between">
                <span>{{ $warn['title'] }}</span>
                <span class="text-amber-800 font-black">{{ $warn['metric'] }}</span>
            </div>
            <p class="text-gray-600 mt-1">{{ $warn['message'] }}</p>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Section 1 & 4: Branch Comparison Matrix --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div>
            <h2 class="font-bold text-gray-900 text-base">Branch Performance Comparison</h2>
            <p class="text-xs text-gray-500 mt-0.5">Sales volume, order counts, growth vs prior equivalent window, and outstanding balances per location</p>
        </div>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
            {{ $branchSummary['branches']->count() }} Location(s)
        </span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-6 py-3.5 text-left">Branch Name</th>
                    <th class="px-6 py-3.5 text-right">Orders</th>
                    <th class="px-6 py-3.5 text-right">Units Sold</th>
                    <th class="px-6 py-3.5 text-right">Net Sales</th>
                    <th class="px-6 py-3.5 text-right">Period Growth</th>
                    <th class="px-6 py-3.5 text-right">Open Receivables</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($branchSummary['branches'] as $bRow)
                <tr class="hover:bg-gray-50/70 transition-colors">
                    <td class="px-6 py-3.5">
                        <div class="font-bold text-gray-900">{{ $bRow['name'] }}</div>
                        <div class="text-[11px] text-gray-400 font-mono">{{ $bRow['code'] }}</div>
                    </td>
                    <td class="px-6 py-3.5 text-right font-medium text-gray-700">{{ number_format($bRow['total_orders']) }}</td>
                    <td class="px-6 py-3.5 text-right font-medium text-gray-700">{{ number_format($bRow['units_sold']) }}</td>
                    <td class="px-6 py-3.5 text-right font-bold text-heim-800">₱{{ number_format($bRow['total_sales'], 2) }}</td>
                    <td class="px-6 py-3.5 text-right">
                        @if($bRow['growth_percent'] !== null)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $bRow['growth_percent'] >= 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                {{ $bRow['growth_percent'] >= 0 ? '+' : '' }}{{ $bRow['growth_percent'] }}%
                            </span>
                        @else
                            <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-3.5 text-right">
                        @if($bRow['open_balance'] > 0)
                            <span class="font-bold text-amber-700">₱{{ number_format($bRow['open_balance'], 2) }}</span>
                        @else
                            <span class="text-xs text-emerald-600 font-semibold">Settled</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Section 5: Evidence-Based AI Insights Panel --}}
<div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-6 text-white shadow-md mb-8">
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-700 pb-4 mb-5">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                    Active AI Intelligence
                </span>
                <h2 class="text-lg font-black tracking-tight text-white">Evidence-Based Product Recommendations</h2>
            </div>
            <p class="text-xs text-slate-300 mt-1">Grounding verified transaction facts with actionable operational proposals</p>
        </div>
        <div class="text-xs text-slate-400">
            Found <span class="font-bold text-emerald-300">{{ $insights->count() }}</span> insight(s) in this reporting window
        </div>
    </div>

    @if($insights->isEmpty())
        <div class="p-8 text-center bg-slate-800/50 rounded-xl border border-slate-700">
            <svg class="w-8 h-8 mx-auto text-slate-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-sm font-semibold text-slate-300">Catalog trajectory is steady</p>
            <p class="text-xs text-slate-400 mt-1">No significant anomalies, low-stock breaches, or severe volume swings were detected for the selected period.</p>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @foreach($insights as $insight)
            <div class="bg-slate-800/90 border border-slate-700 rounded-xl p-4 flex flex-col justify-between hover:border-slate-600 transition-colors">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide
                            {{ match($insight['finding_category']) {
                                'Low ingredient stock' => 'bg-rose-500/20 text-rose-300 border border-rose-500/30',
                                'Declining sales' => 'bg-amber-500/20 text-amber-300 border border-amber-500/30',
                                'Increasing sales', 'High sales' => 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30',
                                'Zero sales' => 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30',
                                default => 'bg-slate-600 text-slate-200'
                            } }}">
                            {{ $insight['finding_category'] }}
                        </span>
                        <span class="text-[11px] font-mono text-slate-400">{{ $insight['product_name'] }}</span>
                    </div>

                    {{-- Fact & Recommendation --}}
                    <div class="text-xs text-slate-200 mb-2 leading-relaxed">
                        <span class="font-bold text-white">Observed Fact:</span> {{ $insight['observed_fact'] }}
                    </div>

                    <div class="text-xs text-slate-300 bg-slate-900/60 p-2.5 rounded-lg border border-slate-700/60 mb-3 leading-relaxed">
                        <span class="font-bold text-emerald-300">Recommendation:</span> {{ $insight['suggested_action'] }}
                    </div>

                    {{-- Structured Evidence Disclosure Accordion --}}
                    <details class="text-[11px] text-slate-400 bg-slate-950/40 rounded-lg p-2.5 border border-slate-800 mb-3">
                        <summary class="cursor-pointer font-bold text-slate-300 hover:text-white transition-colors">
                            View Verified Evidence &amp; Methodology
                        </summary>
                        <div class="mt-2 space-y-1.5 pt-2 border-t border-slate-800">
                            <div><span class="font-semibold text-slate-300">Current Metric:</span> {{ $insight['actual_metric'] }}</div>
                            <div><span class="font-semibold text-slate-300">Comparison Metric:</span> {{ $insight['comparison_metric'] }}</div>
                            <div><span class="font-semibold text-slate-300">Methodology:</span> {{ $insight['calculation_method'] }}</div>
                            <div><span class="font-semibold text-amber-300/80">Data Limitations:</span> {{ $insight['data_limitations'] }}</div>
                        </div>
                    </details>
                </div>

                <div class="pt-2 border-t border-slate-700/60 flex items-center justify-between">
                    <span class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">
                        Urgency: <span class="{{ $insight['urgency'] === 'high' ? 'text-rose-400 font-bold' : ($insight['urgency'] === 'medium' ? 'text-amber-300 font-bold' : 'text-slate-300') }}">{{ ucfirst($insight['urgency']) }}</span>
                    </span>
                    <button type="button" 
                        onclick="prefillActionModal({{ json_encode($insight) }})" 
                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-xs transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Turn into Documented Action
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Section 3: Product Action Tracking --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8" id="actions-section">
    <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="font-bold text-gray-900 text-base">Product Action Tracking</h2>
            <p class="text-xs text-gray-500 mt-0.5">Owner approvals, staff assignments, operational completion logs, and follow-up reviews</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="openNewActionModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-white bg-heim-700 hover:bg-heim-800 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Action
            </button>
        </div>
    </div>

    @if($openActions->isEmpty() && $completedActions->isEmpty())
        <div class="p-10 text-center text-gray-400 text-sm">
            <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            No active product actions logged yet. Convert an AI recommendation above or record an action manually.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-6 py-3.5 text-left">Action ID &amp; Target</th>
                        <th class="px-6 py-3.5 text-left">Category &amp; Evidence</th>
                        <th class="px-6 py-3.5 text-left">Recommended Action</th>
                        <th class="px-6 py-3.5 text-center">Owner Decision</th>
                        <th class="px-6 py-3.5 text-left">Assigned / Due</th>
                        <th class="px-6 py-3.5 text-center">Status</th>
                        <th class="px-6 py-3.5 text-right">Controls</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($openActions->concat($completedActions) as $act)
                    <tr class="hover:bg-gray-50/70 transition-colors">
                        <td class="px-6 py-4">
                            <span class="font-mono font-bold text-heim-800 text-xs">{{ $act->action_code }}</span>
                            <div class="font-semibold text-gray-900 mt-0.5">{{ $act->product?->name ?? 'General Category' }}</div>
                            <div class="text-[11px] text-gray-400">{{ $act->branch?->name ?? 'All Branches' }}</div>
                        </td>
                        <td class="px-6 py-4 max-w-xs">
                            <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-700 mb-1">
                                {{ $act->finding_category }}
                            </span>
                            <p class="text-xs text-gray-600 line-clamp-2">{{ $act->evidence_summary }}</p>
                        </td>
                        <td class="px-6 py-4 max-w-xs">
                            <p class="text-xs text-gray-800 font-medium line-clamp-2">{{ $act->recommended_action }}</p>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold
                                {{ match($act->owner_decision) {
                                    'Approved' => 'bg-emerald-50 text-emerald-800 border border-emerald-200',
                                    'Rejected' => 'bg-rose-50 text-rose-800 border border-rose-200',
                                    'Deferred' => 'bg-slate-100 text-slate-800 border border-slate-300',
                                    'In Progress' => 'bg-blue-50 text-blue-800 border border-blue-200',
                                    'Completed' => 'bg-purple-50 text-purple-800 border border-purple-200',
                                    default => 'bg-amber-50 text-amber-800 border border-amber-200'
                                } }}">
                                {{ $act->owner_decision }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-xs font-semibold text-gray-900">{{ $act->assigned_to_name ?? 'Unassigned' }}</div>
                            <div class="text-[11px] text-gray-400">
                                Due: {{ $act->due_date ? $act->due_date->format('M j, Y') : 'No date' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-extrabold uppercase tracking-wide
                                {{ match($act->status) {
                                    'completed' => 'bg-emerald-100 text-emerald-800',
                                    'in_progress' => 'bg-blue-100 text-blue-800',
                                    'approved' => 'bg-indigo-100 text-indigo-800',
                                    'rejected' => 'bg-rose-100 text-rose-800',
                                    default => 'bg-amber-100 text-amber-800'
                                } }}">
                                {{ str_replace('_', ' ', $act->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap space-x-1">
                            <a href="{{ route('reports.decision-dashboard.actions.show', $act) }}" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold">
                                Details
                            </a>
                            @if(auth()->user()->isOwner())
                            <button type="button" onclick="openDecisionModal({{ json_encode($act) }})" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-heim-50 hover:bg-heim-100 text-heim-800 border border-heim-200 text-xs font-bold">
                                Decision
                            </button>
                            @endif
                            @if(!in_array($act->status, ['completed', 'rejected']))
                            <button type="button" onclick="openExecutionModal({{ json_encode($act) }})" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold">
                                Update
                            </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Section 1: Detailed Product Ranking Tables --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
    <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="font-bold text-gray-900 text-base">Full Product Performance Ledger</h2>
            <p class="text-xs text-gray-500 mt-0.5">Ranked by {{ $rankingBy === 'revenue' ? 'Net Sales Revenue' : 'Units Sold' }} across active menu items</p>
        </div>
        <div class="flex items-center gap-2">
            <input type="text" id="table-search" placeholder="Quick search..." onkeyup="filterProductTable(this.value)"
                class="bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 text-xs focus:ring-2 focus:ring-heim-500 focus:bg-white">
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm" id="product-performance-table">
            <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-6 py-3.5 text-left">#</th>
                    <th class="px-6 py-3.5 text-left">Product &amp; Category</th>
                    <th class="px-6 py-3.5 text-right">Units Sold</th>
                    <th class="px-6 py-3.5 text-right">Net Revenue</th>
                    <th class="px-6 py-3.5 text-right">Previous Units</th>
                    <th class="px-6 py-3.5 text-right">Volume Growth</th>
                    <th class="px-6 py-3.5 text-center">Trend</th>
                    <th class="px-6 py-3.5 text-center">Stock Health</th>
                    <th class="px-6 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($productPerformance as $index => $prod)
                <tr class="hover:bg-gray-50/70 transition-colors product-row">
                    <td class="px-6 py-3.5 text-gray-400 font-mono text-xs">{{ $index + 1 }}</td>
                    <td class="px-6 py-3.5">
                        <div class="font-bold text-gray-900">{{ $prod->name }}</div>
                        <div class="text-[11px] text-gray-400">{{ $prod->category_name }}</div>
                    </td>
                    <td class="px-6 py-3.5 text-right font-extrabold text-gray-900">{{ number_format($prod->units_sold) }}</td>
                    <td class="px-6 py-3.5 text-right font-extrabold text-heim-800">₱{{ number_format($prod->net_revenue, 2) }}</td>
                    <td class="px-6 py-3.5 text-right text-gray-500 text-xs">{{ number_format($prod->prev_units_sold) }}</td>
                    <td class="px-6 py-3.5 text-right">
                        @if($prod->unit_change_percent !== null)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $prod->unit_change_percent >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                {{ $prod->unit_change_percent >= 0 ? '+' : '' }}{{ $prod->unit_change_percent }}%
                            </span>
                        @elseif($prod->units_sold > 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700">New Demand</span>
                        @else
                            <span class="text-xs text-gray-400">0%</span>
                        @endif
                    </td>
                    <td class="px-6 py-3.5 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase
                            {{ match($prod->trend) {
                                'increasing' => 'bg-emerald-100 text-emerald-800',
                                'decreasing' => 'bg-rose-100 text-rose-800',
                                'zero_sales' => 'bg-gray-100 text-gray-600',
                                default => 'bg-slate-100 text-slate-700'
                            } }}">
                            {{ str_replace('_', ' ', $prod->trend) }}
                        </span>
                    </td>
                    <td class="px-6 py-3.5 text-center">
                        @if($prod->has_low_stock)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                                Low Stock
                            </span>
                        @else
                            <span class="text-xs text-emerald-600 font-semibold">&check; Adequate</span>
                        @endif
                    </td>
                    <td class="px-6 py-3.5 text-right">
                        <button type="button" 
                            onclick="createCustomProductAction({{ $prod->id }}, '{{ addslashes($prod->name) }}', '{{ $prod->trend === 'increasing' ? 'Increasing sales' : ($prod->trend === 'zero_sales' ? 'Zero sales' : ($prod->has_low_stock ? 'Low ingredient stock' : 'High sales')) }}', 'Sold {{ $prod->units_sold }} units (₱{{ number_format($prod->net_revenue, 2) }}) in period.')"
                            class="text-xs font-bold text-heim-700 hover:text-heim-900">
                            + Log Action
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-6 py-10 text-center text-gray-400">No active products match the selected filters.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- MODAL 1: Create / Document Product Action Modal --}}
<div id="new-action-modal" class="fixed inset-0 z-50 bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
            <h3 class="font-bold text-gray-900 text-base" id="action-modal-title">Record Documented Product Action</h3>
            <button type="button" onclick="closeNewActionModal()" class="text-gray-400 hover:text-gray-700 text-xl font-bold">&times;</button>
        </div>
        <form method="POST" action="{{ route('reports.decision-dashboard.actions.store') }}">
            @csrf
            <input type="hidden" name="period_start" value="{{ $dateWindows['start_date'] }}">
            <input type="hidden" name="period_end" value="{{ $dateWindows['end_date'] }}">
            <input type="hidden" name="reporting_period" value="{{ $dateWindows['label'] }}">

            <div class="space-y-4 text-xs">
                <div>
                    <label class="block font-bold text-gray-600 mb-1">Target Product</label>
                    <select name="product_id" id="modal-product-id" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-heim-500">
                        <option value="">(Category / General Store Wide)</option>
                        @foreach($productsList as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if(auth()->user()->isOwner())
                <div>
                    <label class="block font-bold text-gray-600 mb-1">Branch Scope</label>
                    <select name="branch_id" id="modal-branch-id" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-heim-500">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                @endif

                <div>
                    <label class="block font-bold text-gray-600 mb-1">Finding Category *</label>
                    <select name="finding_category" id="modal-finding-category" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-heim-500 font-medium">
                        <option value="High sales">High sales</option>
                        <option value="Low sales">Low sales</option>
                        <option value="Zero sales">Zero sales</option>
                        <option value="Declining sales">Declining sales</option>
                        <option value="Increasing sales">Increasing sales</option>
                        <option value="Low ingredient stock">Low ingredient stock</option>
                        <option value="High inventory with low sales">High inventory with low sales</option>
                        <option value="Branch performance difference">Branch performance difference</option>
                        <option value="Insufficient data">Insufficient data</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-gray-600 mb-1">Evidence Summary *</label>
                    <textarea name="evidence_summary" id="modal-evidence-summary" rows="3" required placeholder="Specific numbers, growth rates, and context from reports..."
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-heim-500"></textarea>
                </div>

                <div>
                    <label class="block font-bold text-gray-600 mb-1">Recommended Action Proposal *</label>
                    <textarea name="recommended_action" id="modal-recommended-action" rows="3" required placeholder="What operational, menu, or ingredient step should be taken..."
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-heim-500"></textarea>
                </div>

                <input type="hidden" name="source_reference" id="modal-source-reference" value="Decision Dashboard Manual Action">

                @if(auth()->user()->isOwner())
                <div class="grid grid-cols-2 gap-3 pt-2 border-t border-gray-100">
                    <div>
                        <label class="block font-bold text-gray-600 mb-1">Initial Owner Decision</label>
                        <select name="owner_decision" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-heim-500 font-medium">
                            <option value="Needs Review">Needs Review</option>
                            <option value="Approved">Approved</option>
                            <option value="Deferred">Deferred</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-gray-600 mb-1">Assign Responsible</label>
                        <select name="assigned_to_user_id" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-heim-500">
                            <option value="">(None)</option>
                            @foreach($assignableUsers as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ ucfirst($u->role) }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-gray-600 mb-1">Due Date</label>
                    <input type="date" name="due_date" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-heim-500">
                </div>
                @endif
            </div>

            <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeNewActionModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100">Cancel</button>
                <button type="submit" class="brand-btn-primary text-xs">Save Action Item</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL 2: Owner Decision & Assignment Modal --}}
@if(auth()->user()->isOwner())
<div id="decision-modal" class="fixed inset-0 z-50 bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
            <h3 class="font-bold text-gray-900 text-base">Record Owner Decision</h3>
            <button type="button" onclick="closeDecisionModal()" class="text-gray-400 hover:text-gray-700 text-xl font-bold">&times;</button>
        </div>
        <form method="POST" id="decision-form" action="">
            @csrf
            @method('PATCH')
            <div class="space-y-4 text-xs">
                <div>
                    <span class="text-gray-500 font-semibold">Action ID:</span>
                    <span class="font-bold text-heim-800" id="decision-action-code"></span>
                </div>
                <div>
                    <label class="block font-bold text-gray-600 mb-1">Executive Decision *</label>
                    <select name="owner_decision" id="decision-select" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm font-bold focus:ring-2 focus:ring-heim-500">
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                        <option value="Deferred">Deferred</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                        <option value="Needs Review">Needs Review</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-gray-600 mb-1">Assign Responsible Staff</label>
                    <select name="assigned_to_user_id" id="decision-assigned" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-heim-500">
                        <option value="">(Keep Current / Unassigned)</option>
                        @foreach($assignableUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ ucfirst($u->role) }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-gray-600 mb-1">Target Completion Date</label>
                    <input type="date" name="due_date" id="decision-due-date" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-heim-500">
                </div>
                <div>
                    <label class="block font-bold text-gray-600 mb-1">Owner Decision Notes</label>
                    <textarea name="decision_notes" rows="2" placeholder="Rationale or directions for staff..."
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-heim-500"></textarea>
                </div>
            </div>
            <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeDecisionModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100">Cancel</button>
                <button type="submit" class="brand-btn-primary text-xs">Save Decision</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- MODAL 3: Action Execution / Status Update Modal (for Staff & Managers) --}}
<div id="execution-modal" class="fixed inset-0 z-50 bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
            <h3 class="font-bold text-gray-900 text-base">Update Action Progress</h3>
            <button type="button" onclick="closeExecutionModal()" class="text-gray-400 hover:text-gray-700 text-xl font-bold">&times;</button>
        </div>
        <form method="POST" id="execution-form" action="">
            @csrf
            @method('PATCH')
            <div class="space-y-4 text-xs">
                <div>
                    <span class="text-gray-500 font-semibold">Updating Action:</span>
                    <span class="font-bold text-heim-800" id="execution-action-code"></span>
                </div>
                <div>
                    <label class="block font-bold text-gray-600 mb-1">Execution Status *</label>
                    <select name="status" id="execution-status" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm font-bold focus:ring-2 focus:ring-heim-500">
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="needs_review">Needs Review</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-gray-600 mb-1">Completion / Progress Notes</label>
                    <textarea name="completion_notes" id="execution-notes" rows="3" placeholder="Document specific operational steps executed (e.g., barista recipe alignment, stock buffer verified)..."
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-heim-500"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-bold text-gray-600 mb-1">Follow-up Start Date</label>
                        <input type="date" name="follow_up_start" id="execution-fu-start" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-600 mb-1">Follow-up End Date</label>
                        <input type="date" name="follow_up_end" id="execution-fu-end" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm">
                    </div>
                </div>
            </div>
            <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeExecutionModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 hover:bg-gray-100">Cancel</button>
                <button type="submit" class="brand-btn-primary text-xs">Update Status</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function toggleDateInputs(type) {
    var dateInput = document.getElementById('date-input');
    var rangeInputs = document.getElementById('range-inputs');
    var rangeFields = rangeInputs ? rangeInputs.querySelectorAll('input[type="date"]') : [];
    if (dateInput) {
        dateInput.classList.toggle('hidden', type === 'custom' || type === 'all');
    }
    if (rangeInputs) {
        rangeInputs.classList.toggle('hidden', type !== 'custom');
    }
    rangeFields.forEach(function (field) {
        field.required = type === 'custom';
    });
}

function filterProductTable(query) {
    var filter = query.toLowerCase();
    var rows = document.querySelectorAll('#product-performance-table tbody tr.product-row');
    rows.forEach(function(row) {
        var text = row.innerText.toLowerCase();
        row.style.display = text.indexOf(filter) > -1 ? '' : 'none';
    });
}

function openNewActionModal() {
    document.getElementById('new-action-modal').classList.remove('hidden');
}

function closeNewActionModal() {
    document.getElementById('new-action-modal').classList.add('hidden');
}

function prefillActionModal(insight) {
    document.getElementById('action-modal-title').innerText = 'Document Action from Insight: ' + insight.finding_category;
    if (insight.product_id) {
        document.getElementById('modal-product-id').value = insight.product_id;
    }
    if (insight.branch_id && document.getElementById('modal-branch-id')) {
        document.getElementById('modal-branch-id').value = insight.branch_id;
    }
    document.getElementById('modal-finding-category').value = insight.finding_category;
    document.getElementById('modal-evidence-summary').value = insight.observed_fact + '\n\n' + insight.actual_metric + ' (Comparison: ' + insight.comparison_metric + ')';
    document.getElementById('modal-recommended-action').value = insight.suggested_action;
    document.getElementById('modal-source-reference').value = 'AI Insight: ' + insight.finding_category + ' (' + insight.product_name + ')';
    openNewActionModal();
}

function createCustomProductAction(productId, productName, category, evidence) {
    document.getElementById('action-modal-title').innerText = 'Log Action for ' + productName;
    document.getElementById('modal-product-id').value = productId;
    document.getElementById('modal-finding-category').value = category;
    document.getElementById('modal-evidence-summary').value = evidence;
    document.getElementById('modal-recommended-action').value = 'Review sales performance, barista preparation consistency, and ingredient buffer.';
    document.getElementById('modal-source-reference').value = 'Product Ledger: ' + productName;
    openNewActionModal();
}

function openDecisionModal(act) {
    var form = document.getElementById('decision-form');
    form.action = '/reports/decision-dashboard/actions/' + act.id + '/decision';
    document.getElementById('decision-action-code').innerText = act.action_code + ' (' + (act.product ? act.product.name : 'Category') + ')';
    document.getElementById('decision-select').value = act.owner_decision || 'Approved';
    if (act.assigned_to_user_id) {
        document.getElementById('decision-assigned').value = act.assigned_to_user_id;
    }
    if (act.due_date) {
        document.getElementById('decision-due-date').value = act.due_date.substring(0, 10);
    }
    document.getElementById('decision-modal').classList.remove('hidden');
}

function closeDecisionModal() {
    document.getElementById('decision-modal').classList.add('hidden');
}

function openExecutionModal(act) {
    var form = document.getElementById('execution-form');
    form.action = '/reports/decision-dashboard/actions/' + act.id + '/status';
    document.getElementById('execution-action-code').innerText = act.action_code + ' (' + (act.product ? act.product.name : 'General') + ')';
    document.getElementById('execution-status').value = act.status === 'needs_review' ? 'in_progress' : act.status;
    document.getElementById('execution-notes').value = act.completion_notes || '';
    if (act.follow_up_start) {
        document.getElementById('execution-fu-start').value = act.follow_up_start.substring(0, 10);
    }
    if (act.follow_up_end) {
        document.getElementById('execution-fu-end').value = act.follow_up_end.substring(0, 10);
    }
    document.getElementById('execution-modal').classList.remove('hidden');
}

function closeExecutionModal() {
    document.getElementById('execution-modal').classList.add('hidden');
}
</script>
@endpush
@endsection
