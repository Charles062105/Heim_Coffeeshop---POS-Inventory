@extends('layouts.app')
@section('title', 'System Notifications')
@section('header', 'System Notifications')
@section('subheader', 'Operational alerts, critical inventory warnings, and automated system logs')

@section('header-actions')
    <form method="POST" action="{{ route('notifications.markAllRead') }}">
        @csrf
        <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 shadow-sm transition-all">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Mark All as Read
        </button>
    </form>
@endsection

@section('content')
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
    <div class="brand-card rounded-2xl p-4"><p class="text-xs font-bold uppercase tracking-wider text-gray-400">All notifications</p><p class="mt-1 text-2xl font-black text-gray-900">{{ number_format($notifications->total()) }}</p></div>
    <div class="brand-card rounded-2xl p-4"><p class="text-xs font-bold uppercase tracking-wider text-gray-400">Unread</p><p class="mt-1 text-2xl font-black text-heim-700">{{ number_format($unreadCount) }}</p></div>
    <div class="brand-card rounded-2xl p-4"><p class="text-xs font-bold uppercase tracking-wider text-gray-400">Open alerts</p><p class="mt-1 text-2xl font-black text-amber-600">{{ number_format($openCount) }}</p></div>
</div>
{{-- Filter toolbar --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
    <form method="GET" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            <div class="w-48">
                <select name="type" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 focus:bg-white transition-all">
                    <option value="">All Alert Types</option>
                    @foreach($types as $t)
                    <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>
                        {{ ucwords(str_replace('_', ' ', $t)) }}
                    </option>
                    @endforeach
                </select>
            </div>

            <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 cursor-pointer select-none bg-gray-50 px-3 py-2 rounded-xl border border-gray-200 hover:bg-gray-100 transition-colors">
                <input type="checkbox" name="unread" value="1" {{ request('unread') ? 'checked' : '' }}
                       class="w-4 h-4 rounded text-heim-600 focus:ring-heim-500 border-gray-300">
                <span>Unread Alerts Only</span>
            </label>

            <button type="submit" class="brand-btn-filter">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter
            </button>

            @if(request('type') || request('unread'))
            <a href="{{ route('notifications.index') }}" class="brand-btn-reset">
                Clear
            </a>
            @endif
        </div>

        <div class="text-xs font-semibold text-gray-500">
            Total: <span class="text-gray-900 font-bold">{{ $notifications->total() }}</span> notifications
        </div>
    </form>
</div>

<div class="space-y-3">
    @forelse($notifications as $notification)
    @php
        $isUnread = !$notification->read_by_user && !$notification->read_at;
        $isOutOfStock = $notification->type === 'out_of_stock';
        $isLowStock = $notification->type === 'low_stock';
        $borderAccent = $isOutOfStock ? 'border-l-4 border-l-red-500' : ($isLowStock ? 'border-l-4 border-l-amber-500' : 'border-l-4 border-l-blue-500');
        $cardBg = $isUnread ? 'bg-white shadow-sm ring-1 ring-heim-200/50' : 'bg-white/80 shadow-none border-gray-100 opacity-90';
    @endphp
    <div class="rounded-2xl border border-gray-100 p-4 sm:p-5 flex items-start gap-3 sm:gap-4 transition-all hover:shadow-md {{ $cardBg }} {{ $borderAccent }}">
        <div class="flex-shrink-0 mt-0.5">
            @if($isOutOfStock)
                <div class="w-10 h-10 bg-red-100 border border-red-200 text-red-700 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
            @elseif($isLowStock)
                <div class="w-10 h-10 bg-amber-100 border border-amber-200 text-amber-700 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            @else
                <div class="w-10 h-10 bg-blue-100 border border-blue-200 text-blue-700 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
            @endif
        </div>

        <div class="flex-1 min-w-0">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-1 sm:gap-3">
                <div class="flex items-center gap-2">
                    <h3 class="font-bold text-gray-900 text-sm">{{ $notification->title }}</h3>
                    @if($isUnread)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-heim-100 text-heim-700">
                        NEW
                    </span>
                    @endif
                </div>
                <span class="text-[11px] text-gray-400 font-medium whitespace-nowrap">{{ $notification->created_at->diffForHumans() }}</span>
            </div>

            <p class="text-sm text-gray-600 mt-1 leading-relaxed">{{ $notification->message }}</p>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mt-3 pt-2 border-t border-gray-50 text-xs">
                @if($isUnread)
                <form method="POST" action="{{ route('notifications.read', $notification) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="font-bold text-heim-600 hover:text-heim-700 hover:underline inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Mark as Read
                    </button>
                </form>
                @endif

                @if(!$notification->is_resolved)
                <form method="POST" action="{{ route('notifications.resolve', $notification) }}" data-confirm="Mark {{ $notification->title }} as resolved?" data-confirm-title="Resolve notification">
                    @csrf @method('PATCH')
                    <button type="submit" class="text-gray-500 hover:text-gray-800 font-medium inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Resolve Alert
                    </button>
                </form>
                @else
                <span class="inline-flex items-center gap-1 text-gray-400 text-xs font-semibold">
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Resolved
                </span>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-14 text-center">
        <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-300 mx-auto mb-3">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        </div>
        <p class="text-sm font-semibold text-gray-700">No notifications found</p>
        <p class="text-xs text-gray-400">All alerts and warnings have been processed.</p>
    </div>
    @endforelse
</div>

@if($notifications->hasPages())
<div class="mt-5">
    {{ $notifications->links() }}
</div>
@endif
@endsection
