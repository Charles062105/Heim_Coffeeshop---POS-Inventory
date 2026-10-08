@extends('layouts.app')
@section('title', 'Categories')
@section('header', 'Categories')
@section('subheader', 'Organize the menu into clear, manageable product groups')

@section('header-actions')
    <a href="{{ route('categories.create') }}" class="brand-button gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
        Add Category
    </a>
@endsection

@section('content')
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="brand-card rounded-2xl p-5">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Total Categories</p>
        <p class="mt-1 text-2xl sm:text-3xl font-black text-gray-900">{{ $categories->count() }}</p>
    </div>
    <div class="brand-card rounded-2xl p-5">
        <p class="text-xs font-bold uppercase tracking-wider text-heim-600">Unarchived Groups</p>
        <p class="mt-1 text-2xl sm:text-3xl font-black text-heim-700">{{ $categories->where('status', 'active')->count() }}</p>
    </div>
    <div class="brand-card rounded-2xl p-5">
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Products Assigned</p>
        <p class="mt-1 text-2xl sm:text-3xl font-black text-gray-900">{{ $categories->sum('products_count') }}</p>
    </div>
</div>

<div class="brand-card rounded-2xl overflow-hidden shadow-sm">
    <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-6 py-4">
        <div>
            <h2 class="font-bold text-gray-900 text-base">Menu Categories</h2>
            <p class="text-xs text-gray-400 mt-0.5">Keep product discovery simple and organized at the counter.</p>
        </div>
        <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
            {{ $categories->count() }} groups
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50/75 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-3.5 text-left">Category</th>
                    <th class="px-6 py-3.5 text-center">Products</th>
                    <th class="px-6 py-3.5 text-center">Status</th>
                    <th class="px-6 py-3.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($categories as $cat)
                @php
                    $catColor = match($cat->name) {
                        'Espresso'           => 'bg-amber-400 border-amber-500',
                        'Cold Brew'          => 'bg-sky-400 border-sky-500',
                        'Matcha Series'      => 'bg-emerald-400 border-emerald-500',
                        'Non-Coffee'         => 'bg-purple-400 border-purple-500',
                        'Refreshers'         => 'bg-teal-400 border-teal-500',
                        'Chicken Wings'      => 'bg-orange-400 border-orange-500',
                        'Spicy Korean Wings' => 'bg-red-500 border-red-600',
                        'Buldak Series'      => 'bg-rose-500 border-rose-600',
                        'Chicken Burgers'    => 'bg-yellow-400 border-yellow-500',
                        'Quesadillas'        => 'bg-lime-500 border-lime-600',
                        'Desserts'           => 'bg-pink-400 border-pink-500',
                        'Drinks'             => 'bg-blue-400 border-blue-500',
                        default              => 'bg-gray-400 border-gray-500',
                    };
                @endphp
                <tr class="hover:bg-heim-50/40 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <span class="w-3 h-3 rounded-full border shadow-xs {{ $catColor }} shrink-0"></span>
                            <div>
                                <div class="font-bold text-gray-900">{{ $cat->name }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">Menu group</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center font-semibold text-gray-600">{{ $cat->products_count }}</td>
                    <td class="px-6 py-4 text-center">@include('components.status-badge', ['status' => $cat->status])</td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('categories.edit', $cat) }}" class="inline-flex min-h-9 items-center text-xs text-heim-700 hover:text-heim-900 font-bold transition-colors">Edit</a>
                            @if($cat->products_count === 0)
                            <form method="POST" action="{{ route('categories.destroy', $cat) }}" data-confirm="Delete this category? Products must be reassigned first." data-confirm-title="Delete category">
                                @csrf @method('DELETE')
                                <button type="submit" class="inline-flex min-h-9 items-center text-xs text-rose-500 hover:text-rose-700 font-semibold transition-colors">Delete</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <div class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-300 mb-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            </div>
                            <p class="text-sm font-semibold text-gray-700">No categories found</p>
                            <p class="text-xs text-gray-400 mt-0.5">Click "Add Category" to create your first menu group.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
