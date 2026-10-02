@extends('layouts.app')
@section('title', 'Ingredients')
@section('header', 'Ingredients')
@section('subheader', 'Configure master raw ingredients, units of measurement, and minimum stock alerts')

@section('header-actions')
    <div class="flex items-center gap-2">
        <a href="{{ route('ingredients.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-heim-600 hover:bg-heim-700 shadow-md shadow-heim-600/20 active:scale-95 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            Add Ingredient
        </a>
    </div>
@endsection

@section('content')
{{-- Integrated filter toolbar --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
    <form method="GET" class="flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex flex-1 flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[200px]">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search ingredient name..."
                    class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 focus:bg-white transition-all">
            </div>

            <div class="w-36">
                <select name="status" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 focus:bg-white transition-all">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Unarchived Only</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Archived Only</option>
                </select>
            </div>

            <div class="w-40">
                <select name="stock_status" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 focus:bg-white transition-all">
                    <option value="">All Stock Levels</option>
                    <option value="low_stock" {{ request('stock_status') === 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                    <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                </select>
            </div>

            <button type="submit" class="brand-btn-filter">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter
            </button>

            @if(request('search') || request('status') || request('stock_status'))
            <a href="{{ route('ingredients.index') }}" class="brand-btn-reset">
                Clear
            </a>
            @endif
        </div>

        <div class="text-xs font-semibold text-gray-500 self-end md:self-center">
            Total: <span class="text-gray-900 font-bold">{{ $ingredients->total() }}</span> records
        </div>
    </form>
</div>

{{-- Master table --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50/75 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3.5 text-left">Ingredient</th>
                    <th class="px-5 py-3.5 text-center">Unit</th>
                    <th class="px-5 py-3.5 text-right">Current Stock</th>
                    <th class="px-5 py-3.5 text-right">Min Threshold</th>
                    <th class="px-5 py-3.5 text-center">Stock Level</th>
                    <th class="px-5 py-3.5 text-center">Catalog Status</th>
                    <th class="px-5 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($ingredients as $ingredient)
                @php
                    $ss = $ingredient->getStockStatus();
                    $current = (float) $ingredient->getCurrentStock();
                    $min = (float) $ingredient->minimum_stock;
                    $rowBorder = $ss === 'out_of_stock' ? 'border-l-4 border-l-red-500 bg-red-50/20' : ($ss === 'low_stock' ? 'border-l-4 border-l-amber-500 bg-amber-50/20' : '');
                @endphp
                <tr class="hover:bg-heim-50/30 transition-colors {{ $rowBorder }}">
                    <td class="px-5 py-4">
                        <div class="font-bold text-gray-900">{{ $ingredient->name }}</div>
                        <div class="text-xs text-gray-400">ID: #{{ $ingredient->id }}</div>
                    </td>
                    <td class="px-5 py-4 text-center">
                        <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold bg-gray-100 text-gray-600 uppercase tracking-wide">
                            {{ $ingredient->unit }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <span class="font-extrabold text-base {{ $ss === 'out_of_stock' ? 'text-red-600' : ($ss === 'low_stock' ? 'text-amber-600' : 'text-heim-700') }}">
                            {{ number_format($current, 2) }}
                        </span>
                        <span class="text-xs text-gray-400 ml-0.5">{{ $ingredient->unit }}</span>
                    </td>
                    <td class="px-5 py-4 text-right text-gray-500 font-medium">
                        {{ number_format($min, 2) }}
                        <span class="text-xs text-gray-400 ml-0.5">{{ $ingredient->unit }}</span>
                    </td>
                    <td class="px-5 py-4 text-center">
                        @include('components.status-badge', ['status' => $ss])
                    </td>
                    <td class="px-5 py-4 text-center">
                        @include('components.status-badge', ['status' => $ingredient->status])
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('ingredients.edit', $ingredient) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-heim-700 bg-heim-50 hover:bg-heim-100 px-2.5 py-1.5 rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Edit
                            </a>
                            <form method="POST" action="{{ route('ingredients.toggle', $ingredient) }}" class="inline" data-confirm="{{ $ingredient->status === 'active' ? 'Archive' : 'Unarchive' }} {{ $ingredient->name }}?" data-confirm-title="Change ingredient status">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs font-medium px-2.5 py-1.5 rounded-lg border {{ $ingredient->status === 'active' ? 'border-green-200 text-green-700 hover:bg-green-50' : 'border-red-200 text-red-700 hover:bg-red-50' }} transition-colors">
                                    {{ $ingredient->status === 'active' ? 'Archive' : 'Unarchive' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-12 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <div class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-300 mb-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-700">No ingredients found</p>
                            <p class="text-xs text-gray-400">Add ingredients to start recipe tracking and stock management.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($ingredients->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
        {{ $ingredients->links() }}
    </div>
    @endif
</div>
@endsection
