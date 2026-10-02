@extends('layouts.app')
@section('title', 'Account Information')
@section('header', 'Account Information')
@section('subheader', 'View your active account details and system access permissions')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- User identity card --}}
    <div class="brand-card rounded-2xl p-6 sm:p-8 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 border-b border-gray-100 pb-6 mb-6">
            <div class="flex items-center gap-5 min-w-0">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-heim-700 to-heim-900 text-white font-black text-2xl flex items-center justify-center shadow-md flex-shrink-0">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <h2 class="font-bold text-xl text-gray-900 leading-tight truncate">{{ $user->name }}</h2>
                    <p class="text-sm text-gray-500 truncate mt-0.5">{{ $user->email }}</p>
                    <div class="flex items-center gap-2 mt-2.5">
                        @include('components.status-badge', ['status' => $user->role])
                        @include('components.status-badge', ['status' => $user->status])
                    </div>
                </div>
            </div>
            <div class="text-xs text-gray-500 font-medium bg-gray-50 px-4 py-3 rounded-xl border border-gray-100 self-start sm:self-center">
                <div>Member since</div>
                <div class="font-bold text-gray-800 text-sm mt-0.5">{{ $user->created_at ? $user->created_at->format('M d, Y') : '—' }}</div>
            </div>
        </div>

        {{-- Read-only Details Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-gray-50/80 rounded-xl p-4 border border-gray-100">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Full Name</p>
                <p class="text-sm font-bold text-gray-800">{{ $user->name }}</p>
            </div>
            <div class="bg-gray-50/80 rounded-xl p-4 border border-gray-100">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Email Address</p>
                <p class="text-sm font-bold text-gray-800">{{ $user->email }}</p>
            </div>
            <div class="bg-gray-50/80 rounded-xl p-4 border border-gray-100">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Assigned Role</p>
                <p class="text-sm font-bold text-gray-800 capitalize">{{ $user->role }}</p>
            </div>
            <div class="bg-gray-50/80 rounded-xl p-4 border border-gray-100">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Account Status</p>
                <p class="text-sm font-bold text-gray-800 capitalize">{{ $user->status }}</p>
            </div>
        </div>
    </div>

    {{-- System Policy Notice Card --}}
    <div class="rounded-2xl border border-blue-200/80 bg-blue-50/50 p-6 shadow-xs flex items-start gap-4">
        <div class="w-10 h-10 rounded-xl bg-blue-100 border border-blue-200 flex items-center justify-center text-blue-600 flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <h3 class="font-bold text-blue-900 text-sm">Account Administration Policy</h3>
            <p class="text-xs text-blue-700/90 mt-1 leading-relaxed">
                Self-service modification of names, passwords, and account deletions has been disabled by security policy. Account details, password resets, and user management are managed by the Store Owner under <strong>Administration &gt; Users & Accounts</strong>.
            </p>
        </div>
    </div>

</div>
@endsection
