@extends('layouts.app')
@section('title', 'User Management')
@section('header', 'User Accounts')
@section('subheader', 'Manage staff access privileges, role hierarchy, and authentication status')

@section('header-actions')
    <a href="{{ route('users.create') }}" class="brand-button gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
        Add User
    </a>
@endsection

@section('content')
{{-- Filter toolbar --}}
<div class="brand-card rounded-2xl p-4 mb-6 shadow-sm">
    <form method="GET" class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
        <div class="flex flex-1 flex-wrap items-end gap-3">
            <div class="w-44">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Staff Role</label>
                <select name="role" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 focus:bg-white transition-all">
                    <option value="">All Roles</option>
                    @foreach($roles as $role)
                    <option value="{{ $role }}" @selected(request('role') === $role)>{{ ucfirst($role) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Account Status</label>
                <select name="status" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 focus:bg-white transition-all">
                    <option value="">All Statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Unarchived Only</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Archived Only</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="brand-btn-filter justify-center h-[38px]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filter
                </button>
                @if(request('role') || request('status'))
                <a href="{{ route('users.index') }}" class="brand-btn-reset h-[38px] flex items-center justify-center">
                    Clear
                </a>
                @endif
            </div>
        </div>

        <div class="text-xs font-semibold text-gray-500 self-end sm:self-center shrink-0">
            Total: <span class="text-gray-900 font-bold">{{ $users->total() }}</span> accounts
        </div>
    </form>
</div>

{{-- Accounts Table --}}
<div class="brand-card rounded-2xl shadow-sm overflow-hidden">
    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
        <div>
            <h2 class="font-bold text-gray-900 text-base">Authorized Staff Accounts</h2>
            <p class="text-xs text-gray-400">Manage user credentials, POS permissions, and account lifecycle</p>
        </div>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
            {{ $users->total() }} accounts
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50/75 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-3.5 text-left">Staff Member</th>
                    <th class="px-6 py-3.5 text-left">Email Address</th>
                    <th class="px-6 py-3.5 text-center">System Role</th>
                    <th class="px-6 py-3.5 text-center">Status</th>
                    <th class="px-6 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($users as $user)
                <tr class="hover:bg-heim-50/40 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-heim-100 text-xs font-black text-heim-800 shadow-xs">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </span>
                            <div>
                                <span class="font-bold text-gray-900">{{ $user->name }}</span>
                                @if($user->id === auth()->id())
                                <span class="ml-1.5 text-[10px] bg-heim-100 text-heim-800 font-bold px-1.5 py-0.5 rounded">You</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-gray-600 font-medium">{{ $user->email }}</td>
                    <td class="px-6 py-4 text-center">@include('components.status-badge', ['status' => $user->role])</td>
                    <td class="px-6 py-4 text-center">@include('components.status-badge', ['status' => $user->status])</td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('users.edit', $user) }}" class="inline-flex min-h-9 items-center text-xs text-heim-700 hover:text-heim-900 font-bold transition-colors">
                                Edit
                            </a>
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('users.toggle', $user) }}" data-confirm="{{ $user->status === 'active' ? 'Archive' : 'Unarchive' }} user account {{ $user->name }}?" data-confirm-title="Change account status">
                                @csrf @method('PATCH')
                                <button type="submit" class="inline-flex min-h-9 items-center text-xs font-semibold {{ $user->status === 'active' ? 'text-green-700 hover:text-green-800' : 'text-red-700 hover:text-red-800' }} transition-colors">
                                    {{ $user->status === 'active' ? 'Archive' : 'Unarchive' }}
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <div class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-300 mb-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-700">No users found</p>
                            <p class="text-xs text-gray-400">Adjust the role and status filters to locate accounts.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
        {{ $users->links() }}
    </div>
    @endif
</div>
@endsection
