@extends('layouts.app')
@section('title', 'Cash Movements Report')
@section('header', 'Cash Movements')
@section('subheader', 'Audit all Cash In and Cash Out transactions across shifts')

@section('header-actions')
<div class="flex items-center gap-2">
    @if(auth()->user()->canExportOrPrint())
    <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}"
       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-emerald-800 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 shadow-xs transition-all print:hidden">
        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Export Excel
    </a>
    @endif
</div>
@endsection

@section('content')
{{-- Summary cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-5 border border-emerald-100 shadow-sm">
        <p class="text-xs font-bold text-emerald-700 uppercase tracking-wider mb-1">Total Cash In</p>
        <p class="text-2xl font-black text-emerald-900">&#x20B1;{{ number_format($totalCashIn, 2) }}</p>
        <p class="text-xs text-gray-400 mt-1">Approved only</p>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-amber-100 shadow-sm">
        <p class="text-xs font-bold text-amber-700 uppercase tracking-wider mb-1">Total Cash Out</p>
        <p class="text-2xl font-black text-amber-900">&#x20B1;{{ number_format($totalCashOut, 2) }}</p>
        <p class="text-xs text-gray-400 mt-1">Approved only</p>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
        <p class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">Net Movement</p>
        <p class="text-2xl font-black {{ $netMovement >= 0 ? 'text-emerald-900' : 'text-rose-900' }}">
            {{ $netMovement >= 0 ? '+' : '' }}&#x20B1;{{ number_format($netMovement, 2) }}
        </p>
        <p class="text-xs text-gray-400 mt-1">Cash In minus Cash Out</p>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
        <p class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">Movements</p>
        <p class="text-2xl font-black text-gray-900">{{ $totalApproved }}</p>
        <p class="text-xs text-gray-400 mt-1">Approved &middot; {{ $totalReversed }} reversed</p>
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('reports.cash-movements') }}"
      class="mb-5 flex flex-wrap gap-3 items-end bg-white rounded-2xl border border-gray-100 p-4 shadow-sm print:hidden">
    <div>
        <label class="block text-xs font-bold text-gray-600 mb-1">From</label>
        <input type="date" name="from" value="{{ $filters['from'] ?? $from }}"
               class="rounded-xl border border-gray-200 px-3 py-2 text-sm w-36">
    </div>
    <div>
        <label class="block text-xs font-bold text-gray-600 mb-1">To</label>
        <input type="date" name="to" value="{{ $filters['to'] ?? $to }}"
               class="rounded-xl border border-gray-200 px-3 py-2 text-sm w-36">
    </div>
    <div>
        <label class="block text-xs font-bold text-gray-600 mb-1">Type</label>
        <select name="type" class="rounded-xl border border-gray-200 px-3 py-2 text-sm">
            <option value="">All types</option>
            <option value="cash_in" @selected(($filters['type'] ?? '') === 'cash_in')>Cash In</option>
            <option value="cash_out" @selected(($filters['type'] ?? '') === 'cash_out')>Cash Out</option>
        </select>
    </div>
    <div>
        <label class="block text-xs font-bold text-gray-600 mb-1">Status</label>
        <select name="status" class="rounded-xl border border-gray-200 px-3 py-2 text-sm">
            <option value="">All</option>
            <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Approved</option>
            <option value="reversed" @selected(($filters['status'] ?? '') === 'reversed')>Reversed</option>
        </select>
    </div>
    @if($branches->isNotEmpty())
    <div>
        <label class="block text-xs font-bold text-gray-600 mb-1">Branch</label>
        <select name="branch_id" class="rounded-xl border border-gray-200 px-3 py-2 text-sm">
            <option value="">All branches</option>
            @foreach($branches as $branch)
            <option value="{{ $branch->id }}" @selected($selectedBranchId == $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
    </div>
    @endif
    <div>
        <label class="block text-xs font-bold text-gray-600 mb-1">Cashier name</label>
        <input type="text" name="cashier_name" value="{{ $filters['cashier_name'] ?? '' }}"
               placeholder="Search cashier..." class="rounded-xl border border-gray-200 px-3 py-2 text-sm w-40">
    </div>
    <button type="submit"
            class="px-4 py-2 rounded-xl text-xs font-bold bg-heim-600 text-white hover:bg-heim-700 transition-all shadow-sm">
        Filter
    </button>
    <a href="{{ route('reports.cash-movements') }}"
       class="px-4 py-2 rounded-xl text-xs font-bold text-gray-600 bg-gray-50 border border-gray-200 hover:bg-gray-100 transition-all">
        Reset
    </a>
</form>

{{-- Movement ledger --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="p-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-bold text-gray-900 text-sm">Movement Ledger</h2>
        <span class="text-xs text-gray-500">{{ $from }} &rarr; {{ $to }}</span>
    </div>
    @if($movements->isEmpty())
    <div class="px-6 py-12 text-center text-gray-400">
        <svg class="mx-auto mb-3 w-10 h-10 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="text-sm font-semibold">No cash movements found</p>
        <p class="text-xs mt-1">Adjust the date range or filters above.</p>
    </div>
    @else
    <div class="overflow-x-auto">
    <table class="w-full text-xs">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Date &amp; Time</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Type</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Amount</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Reason</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Cashier</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Authorized By</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Shift</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($movements as $mov)
            <tr class="hover:bg-gray-50/60 transition-colors {{ $mov->status === 'reversed' ? 'opacity-60' : '' }}">
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                    {{ $mov->created_at?->copy()->timezone(config('app.business_timezone'))->format('M d, Y h:i A') }}
                </td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center gap-1 font-bold {{ $mov->type === 'cash_in' ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ $mov->type === 'cash_in' ? '&#x2191; Cash In' : '&#x2193; Cash Out' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right font-bold {{ $mov->status === 'reversed' ? 'line-through text-gray-400' : ($mov->type === 'cash_in' ? 'text-emerald-700' : 'text-amber-700') }}">
                    {{ $mov->type === 'cash_in' ? '+' : '&minus;' }}&#x20B1;{{ number_format((float) $mov->amount, 2) }}
                </td>
                <td class="px-4 py-3 text-gray-700 max-w-xs">
                    <span class="block truncate" title="{{ $mov->reason }}">{{ $mov->reason }}</span>
                    @if($mov->status === 'reversed')
                    <span class="text-rose-500 block mt-0.5 truncate" title="{{ $mov->reversal_reason }}">&#x21A9; {{ $mov->reversal_reason }}</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-700">{{ $mov->cashier?->name ?? '&mdash;' }}</td>
                <td class="px-4 py-3 text-gray-700">{{ $mov->authorizedBy?->name ?? '&mdash;' }}</td>
                <td class="px-4 py-3 text-gray-500">
                    <a href="{{ route('reports.shifts.show', $mov->shift_id) }}"
                       class="underline underline-offset-2 hover:text-heim-700">#{{ $mov->shift_id }}</a>
                </td>
                <td class="px-4 py-3 text-center">
                    @if($mov->status === 'reversed')
                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">Reversed</span>
                    @else
                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Approved</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    <div class="p-4 border-t border-gray-100">
        {{ $movements->links() }}
    </div>
    @endif
</div>
@endsection
