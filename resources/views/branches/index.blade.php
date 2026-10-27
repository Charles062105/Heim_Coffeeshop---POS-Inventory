@extends('layouts.app')
@section('title', 'Branches')
@section('header', 'Branch Management')
@section('subheader', 'Create store locations and manage their branch-specific staff, stock, and sales data')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <section class="brand-card rounded-2xl p-6 shadow-sm">
        <h2 class="text-base font-bold text-gray-900 mb-1">Add a Branch</h2>
        <p class="text-xs text-gray-500 mb-5">Each branch has separate inventory and reports.</p>
        <form method="POST" action="{{ route('branches.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="brand-label mb-1.5">Branch Name</label>
                <input name="name" value="{{ old('name') }}" class="brand-input" required maxlength="150" placeholder="e.g. Downtown">
            </div>
            <div>
                <label class="brand-label mb-1.5">Branch Code</label>
                <input name="code" value="{{ old('code') }}" class="brand-input uppercase" required maxlength="20" placeholder="e.g. DOWNTOWN">
            </div>
            <div>
                <label class="brand-label mb-1.5">Address <span class="text-gray-400">(optional)</span></label>
                <input name="address" value="{{ old('address') }}" class="brand-input" maxlength="255">
            </div>
            <div>
                <label class="brand-label mb-1.5">Phone <span class="text-gray-400">(optional)</span></label>
                <input name="phone" value="{{ old('phone') }}" class="brand-input" maxlength="30">
            </div>
            @if($errors->any())
                <ul class="rounded-xl bg-rose-50 border border-rose-200 p-3 text-sm text-rose-700 list-disc list-inside">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            @endif
            <button type="submit" class="brand-button w-full justify-center">Create Branch</button>
        </form>
    </section>

    <section class="brand-card rounded-2xl shadow-sm overflow-hidden lg:col-span-2">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-base font-bold text-gray-900">Branches</h2>
            <p class="text-xs text-gray-500 mt-1">Assign each manager and cashier to their working branch in User Accounts.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-6 py-3 text-left">Branch</th>
                        <th class="px-6 py-3 text-left">Code</th>
                        <th class="px-6 py-3 text-center">Staff</th>
                        <th class="px-6 py-3 text-center">Status</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($branches as $branch)
                    <tr>
                        <td class="px-6 py-4">
                            <p class="font-bold text-gray-900">{{ $branch->name }}</p>
                            @if($branch->address)<p class="text-xs text-gray-500">{{ $branch->address }}</p>@endif
                        </td>
                        <td class="px-6 py-4 font-mono text-xs text-gray-600">{{ $branch->code }}</td>
                        <td class="px-6 py-4 text-center">{{ $branch->users_count }}</td>
                        <td class="px-6 py-4 text-center">@include('components.status-badge', ['status' => $branch->is_active ? 'active' : 'inactive'])</td>
                        <td class="px-6 py-4 text-right">
                            <form method="POST" action="{{ route('branches.toggle', $branch) }}">
                                @csrf @method('PATCH')
                                <button class="text-xs font-bold {{ $branch->is_active ? 'text-rose-700' : 'text-emerald-700' }}">
                                    {{ $branch->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
