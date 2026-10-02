@extends('layouts.app')
@section('title', 'Products')
@section('header', 'Products')
@section('subheader', 'Beverage menu catalog, pricing tiers, and recipe associations')

@section('header-actions')
    <a href="{{ route('products.create') }}" class="brand-button gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
        Add Product
    </a>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Filter toolbar --}}
    <div class="brand-card rounded-2xl p-4 shadow-sm">
        <form method="GET" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2.5 flex-1">
                <div class="relative flex-1 min-w-[200px] sm:max-w-xs">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search product name..."
                        class="w-full pl-9 pr-3.5 py-2 text-sm bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-heim-500 transition-all">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <div class="w-44">
                    <select name="category_id" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-heim-500 transition-all">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="w-36">
                    <select name="status" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-heim-500 transition-all">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Unarchived Only</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Archived Only</option>
                    </select>
                </div>

                <button type="submit" class="brand-btn-filter">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filter
                </button>

                @if(request()->hasAny(['search', 'category_id', 'status']))
                <a href="{{ route('products.index') }}" class="brand-btn-reset">
                    Clear
                </a>
                @endif
            </div>

            <div class="text-xs font-semibold text-gray-500 self-end sm:self-center shrink-0">
                Total: <span class="text-gray-900 font-bold">{{ $products->total() }}</span> products
            </div>
        </form>
    </div>

    {{-- Products Card Grid --}}
    @if($products->isNotEmpty())
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5">
        @foreach($products as $product)
        @php
            $catName = $product->category?->name ?? 'Uncategorized';
            $catBadge = match($catName) {
                'Espresso'           => 'bg-amber-50 text-amber-800 border-amber-200',
                'Cold Brew'          => 'bg-sky-50 text-sky-800 border-sky-200',
                'Matcha Series'      => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                'Non-Coffee'         => 'bg-purple-50 text-purple-800 border-purple-200',
                'Refreshers'         => 'bg-teal-50 text-teal-800 border-teal-200',
                'Chicken Wings'      => 'bg-orange-50 text-orange-800 border-orange-200',
                'Spicy Korean Wings' => 'bg-red-50 text-red-800 border-red-200',
                'Buldak Series'      => 'bg-rose-50 text-rose-800 border-rose-200',
                'Chicken Burgers'    => 'bg-yellow-50 text-yellow-800 border-yellow-200',
                'Quesadillas'        => 'bg-lime-50 text-lime-800 border-lime-200',
                'Desserts'           => 'bg-pink-50 text-pink-800 border-pink-200',
                'Drinks'             => 'bg-blue-50 text-blue-800 border-blue-200',
                default              => 'bg-gray-100 text-gray-700 border-gray-200',
            };
        @endphp
        <div class="brand-card rounded-2xl hover:border-heim-300 hover:shadow-md transition-all flex flex-col justify-between p-5 group">
            <div>
                {{-- Category --}}
                <div class="flex items-center gap-2 mb-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $catBadge }}">
                        {{ $catName }}
                    </span>
                </div>

                {{-- Product Title & Description --}}
                <h3 class="font-bold text-gray-900 text-base leading-snug group-hover:text-heim-700 transition-colors">
                    {{ $product->name }}
                </h3>
                @if($product->description)
                <p class="text-xs text-gray-400 mt-1 line-clamp-2 leading-relaxed">
                    {{ $product->description }}
                </p>
                @endif

                {{-- Sizes & Pricing --}}
                <div class="mt-4 pt-3 border-t border-gray-100 space-y-2">
                    <div class="flex items-center justify-between text-[11px] font-semibold text-gray-400 uppercase tracking-wider">
                        <span>Sizes & Prices</span>
                        <span>{{ $product->sizes->count() }} {{ Str::plural('size', $product->sizes->count()) }}</span>
                    </div>

                    <div class="space-y-1.5">
                        @forelse($product->sizes as $size)
                        <div class="flex items-center justify-between bg-gray-50/80 hover:bg-heim-50/40 border border-gray-100 rounded-xl px-3 py-1.5 text-xs transition-colors">
                            <span class="font-medium text-gray-700">{{ $size->size_name }}</span>
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-heim-700">₱{{ number_format($size->price, 2) }}</span>
                                @if($size->status === 'inactive')
                                <span class="text-[10px] bg-red-100 text-red-600 px-1.5 py-0.5 rounded font-semibold uppercase">Archived</span>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div class="text-xs text-gray-400 italic py-1">No sizes configured</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Footer Action Buttons --}}
            <div class="mt-5 pt-3.5 border-t border-gray-100 flex items-center justify-between gap-2">
                <a href="{{ route('recipes.index') }}" class="inline-flex items-center gap-1 text-xs text-heim-700 hover:text-heim-800 bg-heim-50 hover:bg-heim-100 border border-heim-100 px-2.5 py-1.5 rounded-xl font-semibold transition-colors" title="Manage Recipes">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <span>Recipe</span>
                </a>

                <div class="flex items-center gap-1.5">
                    <a href="{{ route('products.edit', $product) }}" class="inline-flex items-center gap-1 text-xs text-gray-700 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-xl font-medium transition-colors">
                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span>Edit</span>
                    </a>
                    <form method="POST" action="{{ route('products.toggle', $product) }}" class="inline-block">
                        @csrf @method('PATCH')
                        <button type="submit" class="text-xs {{ $product->status === 'active' ? 'text-green-700 hover:bg-green-50 border-green-200' : 'text-red-700 hover:bg-red-50 border-red-200' }} border px-2.5 py-1.5 rounded-xl font-medium transition-colors">
                            {{ $product->status === 'active' ? 'Archive' : 'Unarchive' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if($products->hasPages())
    <div class="brand-card rounded-2xl shadow-sm px-6 py-4">
        {{ $products->links() }}
    </div>
    @endif

    @else
    <div class="brand-card rounded-2xl p-12 text-center text-gray-400">
        <div class="max-w-sm mx-auto">
            <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-300 mx-auto mb-3">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <p class="font-bold text-gray-800 text-base">No products found</p>
            <p class="text-xs text-gray-400 mt-1">Try adjusting your filters or click "Add Product" to create a new beverage.</p>
            @if(request()->hasAny(['search', 'category_id', 'status']))
            <a href="{{ route('products.index') }}" class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold text-heim-600 hover:text-heim-700 underline">
                Reset all filters
            </a>
            @else
            <a href="{{ route('products.create') }}" class="mt-4 brand-button inline-flex">
                Add Product
            </a>
            @endif
        </div>
    </div>
    @endif

</div>
@endsection
