@extends('layouts.app')
@section('title', 'Edit User')
@section('header', 'Edit User Account')
@section('subheader', 'Modify staff contact info, role assignments, or reset credentials')

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
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div>
                <h2 class="font-bold text-gray-900 text-base">Edit Account Details</h2>
                <p class="text-xs text-gray-400">Update information for {{ $user->name }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-5">
            @csrf @method('PUT')
            <div>
                <label class="brand-label mb-1.5">Full Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="brand-input" required autofocus>
            </div>

            <div>
                <label class="brand-label mb-1.5">Email Address <span class="text-rose-500">*</span></label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="brand-input" required>
            </div>

            @if(auth()->user()->isOwner() && auth()->id() !== $user->id)
            <div>
                <label class="brand-label mb-1.5">System Role & Permissions</label>
                <select name="role" class="brand-input">
                    @foreach($roles as $role)
                    <option value="{{ $role }}" {{ old('role', $user->role) === $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="border-t border-gray-100 pt-4">
                <p class="text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">Reset Password</p>
                <p class="text-xs text-gray-400 mb-3">Leave blank if keeping current password. New passwords need at least 8 characters with uppercase and lowercase letters, a number, and a special character.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <input type="password" name="password" placeholder="New password" class="brand-input" minlength="8">
                    </div>
                    <div>
                        <input type="password" name="password_confirmation" placeholder="Confirm new password" class="brand-input">
                    </div>
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
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
