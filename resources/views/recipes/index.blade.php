@extends('layouts.app')
@section('title', 'Recipes')
@section('header', 'Recipes & Formulations')

@section('content')
<div x-data="{ searchQuery: '', selectedCategory: 'all' }" class="space-y-5">

    {{-- Top Controls & Search Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex flex-wrap items-center gap-2.5">
            <div class="relative">
                <input 
                    x-model="searchQuery" 
                    type="text" 
                    placeholder="Search recipes & products..."
                    class="w-56 sm:w-72 border border-gray-200 bg-white rounded-xl pl-9 pr-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 focus:border-transparent shadow-sm"
                >
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            @php
                $categories = $products->pluck('category')->filter()->unique('id');
            @endphp
            @if($categories->isNotEmpty())
            <select x-model="selectedCategory" class="border border-gray-200 bg-white rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 shadow-sm">
                <option value="all">All Categories</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
            @endif
        </div>

        <div class="flex items-center gap-2 text-xs text-gray-500">
            <span class="inline-flex items-center gap-1 bg-white border border-gray-200 px-3 py-1.5 rounded-xl shadow-sm">
                <span class="w-2 h-2 rounded-full bg-heim-500"></span>
                <span class="font-medium text-gray-700">{{ $products->count() }}</span> Products
            </span>
        </div>
    </div>

    {{-- Recipe Cards Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse($products as $product)
        @if($product->sizes->isNotEmpty())
        @php
            $catName = $product->category?->name ?? 'Uncategorized';
            $catBadge = match($catName) {
                'Espresso'   => 'bg-amber-50 text-amber-800 border-amber-200',
                'Cold Brew'  => 'bg-sky-50 text-sky-800 border-sky-200',
                'Non-Coffee' => 'bg-pink-50 text-pink-800 border-pink-200',
                'Refreshers' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                default      => 'bg-gray-100 text-gray-700 border-gray-200',
            };
        @endphp
        <div 
            x-show="(selectedCategory === 'all' || selectedCategory === '{{ $product->category_id }}') && ('{{ strtolower(addslashes($product->name)) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower(addslashes($product->category?->name ?? '')) }}'.includes(searchQuery.toLowerCase()))"
            class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:border-heim-300 hover:shadow-md transition-all flex flex-col justify-between overflow-hidden group"
        >
            <div>
                {{-- Product Card Header --}}
                <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/70 flex items-center justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 bg-heim-100 text-heim-800 rounded-xl flex items-center justify-center font-bold text-xs flex-shrink-0 shadow-sm">
                            {{ strtoupper(substr($product->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <h2 class="font-bold text-gray-900 text-sm leading-tight truncate group-hover:text-heim-700 transition-colors">{{ $product->name }}</h2>
                            <span class="inline-flex items-center px-2 py-0.2 rounded-md text-[11px] font-semibold border {{ $catBadge }} mt-0.5">
                                {{ $catName }}
                            </span>
                        </div>
                    </div>

                    <span class="text-xs text-gray-500 font-medium bg-white px-2.5 py-1 rounded-lg border border-gray-200 flex-shrink-0">
                        {{ $product->sizes->count() }} {{ Str::plural('size', $product->sizes->count()) }}
                    </span>
                </div>

                {{-- Sizes & Recipe Ingredients Details --}}
                <div class="p-4 space-y-3">
                    @foreach($product->sizes as $size)
                    <div class="bg-gray-50/80 border border-gray-100 rounded-xl p-3 hover:bg-heim-50/30 transition-colors">
                        {{-- Size Title Bar --}}
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-gray-900 text-xs sm:text-sm">{{ $size->size_name }}</span>
                                <span class="text-xs font-bold text-heim-700 bg-white px-2 py-0.5 rounded-md border border-gray-200">
                                    ₱{{ number_format($size->price, 2) }}
                                </span>
                            </div>
                            <a href="{{ route('recipes.edit', $size) }}"
                                class="inline-flex items-center gap-1 px-2.5 py-1 {{ $size->recipe && $size->recipe->recipeIngredients->isNotEmpty() ? 'border border-gray-200 text-gray-700 hover:bg-white bg-white/80' : 'bg-heim-600 hover:bg-heim-700 text-white' }} text-[11px] font-semibold rounded-lg transition-colors shadow-sm"
                                title="Configure recipe ingredients for {{ $size->size_name }}">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                <span>{{ $size->recipe ? 'Edit' : 'Set' }}</span>
                            </a>
                        </div>

                        {{-- Ingredients Formula Pills --}}
                        @if($size->recipe && $size->recipe->recipeIngredients->isNotEmpty())
                            <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                @foreach($size->recipe->recipeIngredients as $ri)
                                <span class="inline-flex items-center gap-1 bg-white text-gray-700 text-[11px] px-2 py-0.5 rounded-md border border-gray-200 font-medium">
                                    <span class="text-heim-700 font-bold">{{ $ri->quantity }} {{ $ri->ingredient?->unit }}</span>
                                    <span class="text-gray-600">{{ $ri->ingredient?->name ?? 'Unknown' }}</span>
                                </span>
                                @endforeach
                            </div>
                        @elseif($size->recipe)
                            <div class="flex items-center gap-1.5 text-[11px] text-amber-600 bg-amber-50/60 border border-amber-200/60 rounded-lg px-2.5 py-1 mt-1">
                                <svg class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>Recipe initiated — add ingredients</span>
                            </div>
                        @else
                            <div class="flex items-center gap-1.5 text-[11px] text-gray-400 italic py-0.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
                                <span>No formula configured yet</span>
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
        @empty
        <div class="col-span-full bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center text-gray-400">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="font-semibold text-gray-700 text-base">No recipes available</p>
            <p class="text-xs text-gray-400 mt-1">Please add products first before configuring recipes.</p>
            <a href="{{ route('products.create') }}" class="mt-4 inline-flex items-center gap-1.5 bg-heim-600 hover:bg-heim-700 text-white text-xs font-semibold px-4 py-2 rounded-xl transition-colors">
                Add Product
            </a>
        </div>
        @endforelse
    </div>
    {{-- Add-on Ingredient Mappings --}}
    <div class="mt-8">
        <div class="flex items-center gap-3 mb-4">
            <h2 class="text-base font-black text-gray-900">Add-on Ingredient Consumption</h2>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-purple-100 text-purple-700">
                {{ $addons->where('addonIngredients.count', '>', 0)->count() ?? $addons->filter(fn($a) => $a->addonIngredients->isNotEmpty())->count() }} mapped
            </span>
        </div>
        <p class="text-xs text-gray-400 mb-4 -mt-2">
            These mappings are configured on the Add-ons page. Additive mappings are deducted on top of the recipe; substitutions replace the named recipe ingredient.
            Flavor selectors (₱0.00) have no ingredient mapping as they are modifier choices only.
        </p>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50/80 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3.5 text-left">Add-on Name</th>
                        <th class="px-5 py-3.5 text-right">Price</th>
                        <th class="px-5 py-3.5 text-left text-purple-700">Ingredient Consumed per Order</th>
                        <th class="px-5 py-3.5 text-center">Tracked</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($addons as $addon)
                    <tr class="hover:bg-gray-50/60 transition-colors">
                        <td class="px-5 py-3.5">
                            <div class="font-bold text-gray-900">{{ $addon->name }}</div>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            @if((float)$addon->price > 0)
                                <span class="font-bold text-heim-700">₱{{ number_format($addon->price, 2) }}</span>
                            @else
                                <span class="text-xs text-gray-400 font-medium">Free / modifier</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            @if($addon->addonIngredients->isNotEmpty())
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($addon->addonIngredients as $ai)
                                    <span class="inline-flex items-center gap-1 bg-purple-50 text-purple-800 text-[11px] px-2 py-0.5 rounded-md border border-purple-200 font-medium">
                                        <span class="font-black">{{ $ai->quantity }} {{ $ai->ingredient?->unit }}</span>
                                        <span class="text-purple-600">{{ $ai->ingredient?->name }}{{ $ai->replacesIngredient ? ' replaces ' . $ai->replacesIngredient->name : '' }}</span>
                                    </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-xs text-gray-400 italic">No ingredient deduction (modifier only)</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            @if($addon->addonIngredients->isNotEmpty())
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-green-700 bg-green-50 border border-green-200 px-2 py-0.5 rounded-full">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    Tracked
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-gray-400 bg-gray-50 border border-gray-200 px-2 py-0.5 rounded-full">
                                    Modifier
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-5 py-10 text-center text-gray-400 text-sm">No add-ons configured.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
