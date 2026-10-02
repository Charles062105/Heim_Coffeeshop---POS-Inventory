@extends('layouts.app')
@section('title', 'Add User')
@section('header', 'Add User Account')
@section('subheader', 'Create staff credentials and assign security permissions')

@section('header-actions')
    <a href="{{ route('users.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 shadow-xs transition-all">
        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Users
    </a>
@endsection

@section('content')
<div class="max-w-lg mx-auto">
    <div class="brand-card rounded-2xl p-6 shadow-sm">
        <div class="flex items-center gap-3 pb-4 mb-5 border-b border-gray-100">
            <div class="w-10 h-10 rounded-xl bg-heim-50 border border-heim-100 flex items-center justify-center text-heim-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            </div>
            <div>
                <h2 class="font-bold text-gray-900 text-base">New Staff Account</h2>
                <p class="text-xs text-gray-400">Configure profile, access role, and authentication credentials</p>
            </div>
        </div>

        <form method="POST" action="{{ route('users.store') }}" class="space-y-5">
            @csrf
            <div>
                <label class="brand-label mb-1.5">Full Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g., Jane Doe" class="brand-input" required autofocus>
            </div>

            <div>
                <label class="brand-label mb-1.5">Email Address <span class="text-rose-500">*</span></label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="name@coffee.com" class="brand-input" required>
            </div>

            <div>
                <label class="brand-label mb-1.5">System Role & Permissions <span class="text-rose-500">*</span></label>
                <select name="role" required class="brand-input">
                    <option value="">Select role...</option>
                    @foreach($roles as $role)
                    <option value="{{ $role }}" {{ old('role') === $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-gray-500 mt-1.5">
                    <strong>Owner:</strong> Full access to the system, pricing, users, and authorization.<br>
                    <strong>Manager:</strong> Manage inventory, stock, and authorized voids and discounts.<br>
                    <strong>Cashier:</strong> Dedicated to POS counter sales and ticket creation.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="brand-label mb-1.5">Password <span class="text-rose-500">*</span></label>
                    <input type="password" name="password" placeholder="Min. 8 characters" class="brand-input" required minlength="8">
                    <p class="text-[10px] text-gray-400 mt-1">At least 8 characters with uppercase and lowercase letters, a number, and a special character.</p>
                </div>
                <div>
                    <label class="brand-label mb-1.5">Confirm Password <span class="text-rose-500">*</span></label>
                    <input type="password" name="password_confirmation" placeholder="Repeat password" class="brand-input" required>
                </div>
            </div>

            @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 rounded-xl px-4 py-3 text-sm text-rose-700">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
            @endif

            <div class="flex items-center gap-3 pt-3 border-t border-gray-100">
                <a href="{{ route('users.index') }}" class="brand-btn-cancel flex-1 justify-center">
                    Cancel
                </a>
                <button type="submit" class="brand-button flex-1">
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
