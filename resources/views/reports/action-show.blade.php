@extends('layouts.app')
@section('title', 'Product Action #' . $action->action_code)
@section('header', 'Product Action #' . $action->action_code)
@section('subheader', 'Decision history, operational execution notes, and before-and-after follow-up metric evaluation')

@section('header-actions')
    <div class="flex items-center gap-2">
        <a href="{{ route('reports.decision-dashboard.index') }}" class="inline-flex items-center gap-1 px-3 py-2 rounded-xl text-xs font-bold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 shadow-xs transition-all">
            &larr; Back to Decision Dashboard
        </a>
        <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 shadow-xs transition-all print:hidden">
            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print
        </button>
    </div>
@endsection

@section('content')
@if(session('success'))
<div class="mb-5 rounded-2xl bg-emerald-50 border border-emerald-200/80 p-4 text-sm text-emerald-800 flex items-center justify-between shadow-xs">
    <div class="flex items-center gap-2.5">
        <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="font-semibold">{{ session('success') }}</span>
    </div>
    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900">&times;</button>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    {{-- Left: Action Summary & Scope --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="font-mono text-base font-black text-heim-800 bg-heim-50 px-2.5 py-1 rounded-lg border border-heim-200">
                        {{ $action->action_code }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold
                        {{ match($action->owner_decision) {
                            'Approved' => 'bg-emerald-50 text-emerald-800 border border-emerald-200',
                            'Rejected' => 'bg-rose-50 text-rose-800 border border-rose-200',
                            'Deferred' => 'bg-slate-100 text-slate-800 border border-slate-300',
                            'In Progress' => 'bg-blue-50 text-blue-800 border border-blue-200',
                            'Completed' => 'bg-purple-50 text-purple-800 border border-purple-200',
                            default => 'bg-amber-50 text-amber-800 border border-amber-200'
                        } }}">
                        Decision: {{ $action->owner_decision }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold uppercase
                        {{ match($action->status) {
                            'completed' => 'bg-emerald-100 text-emerald-800',
                            'in_progress' => 'bg-blue-100 text-blue-800',
                            'approved' => 'bg-indigo-100 text-indigo-800',
                            'rejected' => 'bg-rose-100 text-rose-800',
                            default => 'bg-amber-100 text-amber-800'
                        } }}">
                        Status: {{ str_replace('_', ' ', $action->status) }}
                    </span>
                </div>
                <div class="text-xs text-gray-400">
                    Logged {{ $action->created_at->format('M j, Y h:i A') }}
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 py-3 border-y border-gray-100 text-xs mb-4">
                <div>
                    <span class="text-gray-400 block font-semibold">Target Product</span>
                    <span class="font-bold text-gray-900 text-sm">{{ $action->product?->name ?? 'Category / General' }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block font-semibold">Branch</span>
                    <span class="font-bold text-gray-900 text-sm">{{ $action->branch?->name ?? 'All Branches' }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block font-semibold">Assigned Staff</span>
                    <span class="font-bold text-gray-900 text-sm">{{ $action->assigned_to_name ?? 'Unassigned' }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block font-semibold">Target Due Date</span>
                    <span class="font-bold text-gray-900 text-sm">{{ $action->due_date ? $action->due_date->format('M j, Y') : 'None' }}</span>
                </div>
            </div>

            <div class="space-y-4 text-xs">
                <div>
                    <h4 class="font-bold text-gray-500 uppercase tracking-wider mb-1">Finding Category</h4>
                    <span class="inline-block px-2.5 py-1 rounded-md bg-gray-100 text-gray-800 font-bold">
                        {{ $action->finding_category }}
                    </span>
                </div>

                <div>
                    <h4 class="font-bold text-gray-500 uppercase tracking-wider mb-1">Observed Fact &amp; Evidence</h4>
                    <div class="bg-gray-50 rounded-xl p-3.5 text-gray-800 whitespace-pre-line border border-gray-100 leading-relaxed">
                        {{ $action->evidence_summary }}
                    </div>
                </div>

                <div>
                    <h4 class="font-bold text-gray-500 uppercase tracking-wider mb-1">Recommended Action Proposal</h4>
                    <div class="bg-emerald-50/50 rounded-xl p-3.5 text-emerald-950 whitespace-pre-line border border-emerald-100 font-medium leading-relaxed">
                        {{ $action->recommended_action }}
                    </div>
                </div>

                @if($action->completion_notes)
                <div>
                    <h4 class="font-bold text-gray-500 uppercase tracking-wider mb-1">Execution &amp; Completion Notes</h4>
                    <div class="bg-indigo-50/50 rounded-xl p-3.5 text-indigo-950 whitespace-pre-line border border-indigo-100 font-medium leading-relaxed">
                        {{ $action->completion_notes }}
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Section 6: Follow-Up Comparative Analysis --}}
        <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-5">
                <div>
                    <h3 class="font-bold text-gray-900 text-base">Follow-Up Comparative Analysis</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Observed metrics before vs. after documented operational action</p>
                </div>
                @if($followUp['has_data'])
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200">
                    Comparable Windows Active
                </span>
                @endif
            </div>

            @if(!$followUp['has_data'])
                <div class="p-6 text-center text-gray-400 text-xs bg-gray-50 rounded-xl border border-gray-100">
                    <p class="font-semibold text-gray-600">Awaiting Follow-Up Period Evaluation</p>
                    <p class="mt-1">Provide follow-up date parameters or complete the action to compare observed sales, discounts, and inventory metrics before and after.</p>
                </div>
            @else
                {{-- Comparison Grid --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-5">
                    {{-- Units Sold --}}
                    <div class="bg-gray-50 rounded-xl p-3.5 border border-gray-100">
                        <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block">Units Sold</span>
                        <div class="flex items-baseline justify-between mt-1.5">
                            <span class="text-xs text-gray-500">Before: <b class="text-gray-800">{{ $followUp['before']['units'] }}</b></span>
                            <span class="text-xs text-gray-500">After: <b class="text-heim-800">{{ $followUp['after']['units'] }}</b></span>
                        </div>
                        <div class="mt-2 text-xs font-bold {{ ($followUp['changes']['unit_delta'] ?? 0) >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                            {{ ($followUp['changes']['unit_delta'] ?? 0) >= 0 ? '+' : '' }}{{ $followUp['changes']['unit_delta'] }} units
                            @if($followUp['changes']['unit_delta_percent'] !== null)
                                ({{ $followUp['changes']['unit_delta_percent'] }}%)
                            @endif
                        </div>
                    </div>

                    {{-- Net Revenue --}}
                    <div class="bg-gray-50 rounded-xl p-3.5 border border-gray-100">
                        <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block">Net Product Revenue</span>
                        <div class="flex items-baseline justify-between mt-1.5">
                            <span class="text-xs text-gray-500">Before: <b class="text-gray-800">₱{{ number_format($followUp['before']['revenue'], 2) }}</b></span>
                            <span class="text-xs text-gray-500">After: <b class="text-heim-800">₱{{ number_format($followUp['after']['revenue'], 2) }}</b></span>
                        </div>
                        <div class="mt-2 text-xs font-bold {{ ($followUp['changes']['revenue_delta'] ?? 0) >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                            {{ ($followUp['changes']['revenue_delta'] ?? 0) >= 0 ? '+' : '' }}₱{{ number_format($followUp['changes']['revenue_delta'], 2) }}
                            @if($followUp['changes']['revenue_delta_percent'] !== null)
                                ({{ $followUp['changes']['revenue_delta_percent'] }}%)
                            @endif
                        </div>
                    </div>

                    {{-- Ingredient Usage --}}
                    <div class="bg-gray-50 rounded-xl p-3.5 border border-gray-100">
                        <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block">Ingredient Consumption</span>
                        <div class="flex items-baseline justify-between mt-1.5">
                            <span class="text-xs text-gray-500">Before: <b class="text-gray-800">{{ number_format($followUp['before']['consumption'], 2) }}</b></span>
                            <span class="text-xs text-gray-500">After: <b class="text-heim-800">{{ number_format($followUp['after']['consumption'], 2) }}</b></span>
                        </div>
                        <div class="mt-2 text-xs font-semibold text-gray-600">
                            Delta: {{ number_format($followUp['changes']['consumption_delta'], 2) }} units
                        </div>
                    </div>

                    {{-- Waste Recorded --}}
                    <div class="bg-gray-50 rounded-xl p-3.5 border border-gray-100">
                        <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block">Waste Recorded</span>
                        <div class="flex items-baseline justify-between mt-1.5">
                            <span class="text-xs text-gray-500">Before: <b class="text-gray-800">{{ number_format($followUp['before']['waste'], 2) }}</b></span>
                            <span class="text-xs text-gray-500">After: <b class="text-heim-800">{{ number_format($followUp['after']['waste'], 2) }}</b></span>
                        </div>
                        <div class="mt-2 text-xs font-semibold {{ $followUp['changes']['waste_delta'] > 0 ? 'text-rose-600' : 'text-gray-600' }}">
                            Delta: {{ number_format($followUp['changes']['waste_delta'], 2) }} units
                        </div>
                    </div>

                    {{-- Order Discounts --}}
                    <div class="bg-gray-50 rounded-xl p-3.5 border border-gray-100">
                        <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block">Order Discounts</span>
                        <div class="flex items-baseline justify-between mt-1.5">
                            <span class="text-xs text-gray-500">Before: <b class="text-gray-800">₱{{ number_format($followUp['before']['discounts'], 2) }}</b></span>
                            <span class="text-xs text-gray-500">After: <b class="text-heim-800">₱{{ number_format($followUp['after']['discounts'], 2) }}</b></span>
                        </div>
                        <div class="mt-2 text-xs font-semibold text-gray-600">
                            Delta: ₱{{ number_format($followUp['changes']['discount_delta'], 2) }}
                        </div>
                    </div>

                    {{-- Stock Health --}}
                    <div class="bg-gray-50 rounded-xl p-3.5 border border-gray-100">
                        <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider block">Current Stock Health</span>
                        <div class="mt-2 text-xs font-bold {{ $followUp['stock_status'] === 'Adequate' ? 'text-emerald-700' : 'text-rose-700' }}">
                            {{ $followUp['stock_status'] }}
                        </div>
                    </div>
                </div>

                {{-- Methodology Disclaimer (Mandatory Requirement) --}}
                <div class="p-3.5 rounded-xl bg-amber-50/60 border border-amber-200/80 text-[11px] text-amber-900 leading-relaxed">
                    <span class="font-bold">Comparative Methodology Notice:</span> {{ $followUp['disclaimer'] }}
                </div>
            @endif
        </div>
    </div>

    {{-- Right: Timeline Audit History & Action Controls --}}
    <div class="space-y-6">
        @if(auth()->user()->isOwner())
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
            <h3 class="font-bold text-gray-900 text-sm mb-3">Owner Decision Control</h3>
            <form method="POST" action="{{ route('reports.decision-dashboard.actions.decision', $action) }}" class="space-y-3 text-xs">
                @csrf
                @method('PATCH')
                <div>
                    <label class="block font-bold text-gray-600 mb-1">Executive Decision</label>
                    <select name="owner_decision" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-sm font-bold focus:ring-2 focus:ring-heim-500">
                        @foreach(['Needs Review','Approved','Rejected','In Progress','Completed','Deferred'] as $opt)
                            <option value="{{ $opt }}" @selected($action->owner_decision === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-gray-600 mb-1">Target Due Date</label>
                    <input type="date" name="due_date" value="{{ $action->due_date ? $action->due_date->format('Y-m-d') : '' }}"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-sm">
                </div>
                <div>
                    <label class="block font-bold text-gray-600 mb-1">Decision Directives / Notes</label>
                    <textarea name="decision_notes" rows="2" placeholder="Instructions..."
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-sm"></textarea>
                </div>
                <button type="submit" class="w-full brand-btn-primary text-xs py-2.5">
                    Save Executive Decision
                </button>
            </form>
        </div>
        @endif

        {{-- Execution Progress Form --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
            <h3 class="font-bold text-gray-900 text-sm mb-3">Execution Progress</h3>
            <form method="POST" action="{{ route('reports.decision-dashboard.actions.status', $action) }}" class="space-y-3 text-xs">
                @csrf
                @method('PATCH')
                <div>
                    <label class="block font-bold text-gray-600 mb-1">Status</label>
                    <select name="status" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-sm font-bold focus:ring-2 focus:ring-heim-500">
                        <option value="in_progress" @selected($action->status === 'in_progress')>In Progress</option>
                        <option value="completed" @selected($action->status === 'completed')>Completed</option>
                        <option value="needs_review" @selected($action->status === 'needs_review')>Needs Review</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-gray-600 mb-1">Operational Completion Notes</label>
                    <textarea name="completion_notes" rows="3" placeholder="Document specific operational execution..."
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2.5 text-sm">{{ $action->completion_notes }}</textarea>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-bold text-gray-600 mb-1">Follow-up Start</label>
                        <input type="date" name="follow_up_start" value="{{ $action->follow_up_start ? $action->follow_up_start->format('Y-m-d') : '' }}" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2 text-xs">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-600 mb-1">Follow-up End</label>
                        <input type="date" name="follow_up_end" value="{{ $action->follow_up_end ? $action->follow_up_end->format('Y-m-d') : '' }}" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2 text-xs">
                    </div>
                </div>
                <button type="submit" class="w-full px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs transition-colors">
                    Update Execution Progress
                </button>
            </form>
        </div>

        {{-- Audit Trail Timeline --}}
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
            <h3 class="font-bold text-gray-900 text-sm mb-3">Audit Trail &amp; History</h3>
            <div class="space-y-3 relative before:absolute before:inset-0 before:left-3.5 before:w-0.5 before:bg-gray-100 text-xs">
                @forelse($action->audits as $audit)
                <div class="relative pl-7">
                    <div class="absolute left-2 top-1 w-3 h-3 rounded-full bg-heim-600 ring-4 ring-white"></div>
                    <div class="font-bold text-gray-900">{{ ucwords(str_replace('_', ' ', $audit->event)) }}</div>
                    <div class="text-[11px] text-gray-500">
                        {{ $audit->user_name }} ({{ ucfirst($audit->user_role) }}) &bull; {{ $audit->created_at->format('M j, Y h:i A') }}
                    </div>
                    @if($audit->notes)
                        <div class="mt-1 text-gray-600 bg-gray-50 p-2 rounded-lg border border-gray-100">
                            {{ $audit->notes }}
                        </div>
                    @endif
                </div>
                @empty
                <p class="text-xs text-gray-400">No audit events recorded.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
