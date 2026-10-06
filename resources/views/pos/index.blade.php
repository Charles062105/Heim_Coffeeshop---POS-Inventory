@extends('layouts.app')
@section('title', 'Point of Sale')
@section('header', 'Point of Sale')
@section('subheader', 'Front counter order entry, beverage customization, and instant stock deduction')

@section('header-actions')
    @if($activeShift)
        <button type="button" onclick="openShiftOutModal()" aria-label="Shift active. End shift" class="inline-flex items-center gap-1.5 px-2.5 sm:px-3.5 py-2 rounded-xl text-xs font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 shadow-xs transition-all">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="whitespace-nowrap">Shift Active<span class="hidden xl:inline"> · {{ $activeShift->localStartTime()->format('h:i A') }}</span></span>
            <span class="ml-1 whitespace-nowrap rounded-md bg-emerald-200/80 px-1.5 py-0.5 text-[10px] font-bold uppercase text-emerald-900"><span class="hidden sm:inline">End Shift</span><span class="sm:hidden">End</span></span>
        </button>
    @else
        <button type="button" onclick="openShiftInModal()" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-800 bg-rose-50 border border-rose-200 hover:bg-rose-100 shadow-xs transition-all">
            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
            <span>No Active Shift</span>
            <span class="ml-1 text-[10px] bg-rose-200/80 text-rose-900 px-1.5 py-0.5 rounded-md font-bold uppercase">Shift In</span>
        </button>
    @endif
    <button type="button" onclick="openHeldOrdersModal()" aria-label="Saved tickets" class="inline-flex items-center gap-1.5 px-2.5 sm:px-3.5 py-2 rounded-xl text-xs font-semibold text-amber-800 bg-amber-50 border border-amber-200 hover:bg-amber-100 shadow-xs transition-all relative">
        <span>📌</span>
        <span class="hidden sm:inline">Held Orders</span>
        <span id="held-count-badge" class="px-1.5 py-0.2 rounded-full text-[10px] font-extrabold bg-amber-600 text-white">0</span>
    </button>
    <a href="{{ route('orders.index') }}" aria-label="View orders" class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-2.5 sm:px-3.5 py-2 text-xs font-semibold text-gray-700 shadow-xs transition-all hover:bg-gray-50">
        <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        <span class="hidden sm:inline">Orders</span>
    </a>
    @if(auth()->user()?->canAuthorize())
        <a href="{{ route('voids.index') }}" aria-label="Void list" class="hidden xl:inline-flex items-center rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 shadow-xs transition-all hover:bg-gray-50">Void List</a>
    @endif
@endsection

@section('content')
@unless($activeShift)
    <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
        <section class="w-full max-w-md rounded-3xl bg-white p-7 text-center shadow-2xl">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-100 text-2xl">🔒</div>
            <h2 class="mt-4 text-xl font-extrabold text-gray-900">POS Locked · Shift Required</h2>
            <p class="mt-2 text-sm text-gray-600">Count and confirm your starting drawer cash before creating orders, recording payments, or saving tickets.</p>
            <p class="mt-2 text-xs text-gray-500">Cashier and shift time are recorded automatically from your account.</p>
            <button type="button" onclick="openShiftInModal()" class="brand-button mt-5 w-full">Start Shift</button>
        </section>
    </div>
@endunless
<div class="pos-terminal flex h-full min-h-0 flex-col gap-3 bg-gradient-to-b from-emerald-50/70 via-white to-emerald-50/40">
<div class="grid min-h-0 flex-1 grid-cols-1 gap-4 sm:gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(24rem,30rem)] lg:[grid-template-columns:minmax(0,1.6fr)_minmax(26rem,0.9fr)] lg:grid-rows-[minmax(0,1fr)]">

    {{-- ── Left: Product Grid ───────────────────────────────────────────────── --}}
    <div class="order-1 flex min-h-0 min-w-0 flex-col overflow-hidden rounded-[28px] border border-emerald-100 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.06)] ring-1 ring-white/70 lg:h-full">

        {{-- Category tabs & Search --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-emerald-100 bg-gradient-to-r from-emerald-50/80 via-white to-emerald-50/60 p-3 sm:p-4 flex-shrink-0">
            <div class="hidden sm:block shrink-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-heim-700">Menu catalog</p>
                <p class="text-xs text-gray-500 mt-0.5">Select a drink to begin</p>
            </div>
            <div class="flex items-center gap-1.5 overflow-x-auto sidebar-scroll min-w-0 flex-1 py-0.5">
                <button onclick="filterCategory('all')" id="cat-all"
                    class="cat-btn active px-3.5 py-2 rounded-xl text-xs font-extrabold uppercase tracking-[0.12em] whitespace-nowrap transition-all bg-heim-700 text-white shadow-[0_8px_20px_rgba(16,185,129,0.14)]">
                    All
                </button>
                @foreach($categories as $cat)
                <button onclick="filterCategory({{ $cat->id }})" id="cat-{{ $cat->id }}"
                    class="cat-btn px-3.5 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all text-gray-600 hover:bg-emerald-50 hover:text-emerald-800">
                    {{ $cat->name }}
                </button>
                @endforeach
            </div>
            <div class="relative w-full sm:w-56 flex-shrink-0">
                <input id="pos-search" type="search" oninput="searchProducts(this.value)" placeholder="Search items..." aria-label="Search menu items"
                    class="w-full pl-8 pr-3 py-2 text-xs rounded-xl border border-emerald-100 bg-white/90 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-emerald-300 transition-all shadow-sm">
                <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        {{-- Product grid --}}
        <div id="product-grid" class="flex-1 overflow-y-auto p-3 sm:p-5 w-full bg-gray-50/35">
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-3 2xl:grid-cols-4 gap-3.5 sm:gap-4 w-full content-start">
                @foreach($categories as $cat)
                    @foreach($cat->products as $product)
                    @php
                        $catTheme = match($cat->name) {
                            'Espresso'           => ['classes' => 'bg-amber-100 text-amber-800', 'badge' => 'bg-amber-50 text-amber-800 border-amber-200', 'icon' => 'coffee'],
                            'Cold Brew'          => ['classes' => 'bg-sky-100 text-sky-800', 'badge' => 'bg-sky-50 text-sky-800 border-sky-200', 'icon' => 'coldbrew'],
                            'Matcha Series'      => ['classes' => 'bg-emerald-100 text-emerald-800', 'badge' => 'bg-emerald-50 text-emerald-800 border-emerald-200', 'icon' => 'leaf'],
                            'Non-Coffee'         => ['classes' => 'bg-purple-100 text-purple-800', 'badge' => 'bg-purple-50 text-purple-800 border-purple-200', 'icon' => 'cup'],
                            'Refreshers'         => ['classes' => 'bg-teal-100 text-teal-800', 'badge' => 'bg-teal-50 text-teal-800 border-teal-200', 'icon' => 'sparkle'],
                            'Chicken Wings'      => ['classes' => 'bg-orange-100 text-orange-800', 'badge' => 'bg-orange-50 text-orange-800 border-orange-200', 'icon' => 'wings'],
                            'Spicy Korean Wings' => ['classes' => 'bg-red-100 text-red-800', 'badge' => 'bg-red-50 text-red-800 border-red-200', 'icon' => 'chili'],
                            'Buldak Series'      => ['classes' => 'bg-rose-100 text-rose-800', 'badge' => 'bg-rose-50 text-rose-800 border-rose-200', 'icon' => 'fire'],
                            'Chicken Burgers'    => ['classes' => 'bg-yellow-100 text-yellow-800', 'badge' => 'bg-yellow-50 text-yellow-800 border-yellow-200', 'icon' => 'burger'],
                            'Quesadillas'        => ['classes' => 'bg-lime-100 text-lime-800', 'badge' => 'bg-lime-50 text-lime-800 border-lime-200', 'icon' => 'quesadilla'],
                            'Desserts'           => ['classes' => 'bg-pink-100 text-pink-800', 'badge' => 'bg-pink-50 text-pink-800 border-pink-200', 'icon' => 'dessert'],
                            'Drinks'             => ['classes' => 'bg-blue-100 text-blue-800', 'badge' => 'bg-blue-50 text-blue-800 border-blue-200', 'icon' => 'drinks'],
                            default              => ['classes' => 'bg-gray-100 text-gray-700', 'badge' => 'bg-gray-100 text-gray-700 border-gray-200', 'icon' => 'default'],
                        };

                        $sizeData = $product->sizes->map(function ($size) {
                            $ingredients = $size->recipe?->recipeIngredients ?? collect();
                            $available = $ingredients->every(fn ($row) => $row->ingredient
                                && $row->ingredient->inventory
                                && (float) $row->ingredient->inventory->current_stock >= (float) $row->quantity);

                            return [
                                'id' => $size->id,
                                'size_name' => $size->size_name,
                                'price' => (float) $size->price,
                                'grab_price' => $size->grab_price !== null ? (float) $size->grab_price : (float) $size->price,
                                'available' => $available,
                                'low_stock' => $available && $ingredients->contains(fn ($row) => $row->ingredient
                                    && $row->ingredient->inventory
                                    && (float) $row->ingredient->inventory->current_stock <= $row->ingredient->getReorderThreshold()),
                                'recipe' => [
                                    'recipe_ingredients' => $size->recipe?->recipeIngredients
                                        ->map(function ($ri) {
                                            return [
                                                'ingredient' => ['name' => $ri->ingredient->name],
                                            ];
                                        })
                                        ->toArray() ?? [],
                                ],
                            ];
                        })->values()->all();
                        $isOutOfStock = count($sizeData) > 0 && collect($sizeData)->every(fn ($size) => ! $size['available']);
                        $isLowStock = ! $isOutOfStock && collect($sizeData)->contains(fn ($size) => $size['low_stock']);
                    @endphp
                    <button type="button" class="product-card w-full bg-white border border-emerald-100 hover:border-emerald-300 rounded-2xl text-left cursor-pointer hover:shadow-[0_16px_32px_rgba(16,185,129,0.10)] hover:-translate-y-0.5 transition-all duration-200 group relative p-3.5 sm:p-4 flex flex-col justify-between select-none focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0 disabled:hover:shadow-none"
                        data-category="{{ $cat->id }}"
                        data-category-name="{{ $cat->name }}"
                        data-product-id="{{ $product->id }}"
                        data-product-name="{{ $product->name }}"
                        data-sizes="{{ json_encode($sizeData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}"
                        @disabled($isOutOfStock)
                        aria-label="{{ $isOutOfStock ? $product->name . ', out of stock' : 'Add ' . $product->name . ' to order' }}"
                        onclick="handleProductClick(this)">
                        
                        <div>
                            <div class="flex items-start justify-between gap-2 mb-3">
                                <div class="w-9 h-9 {{ $catTheme['classes'] }} rounded-xl flex items-center justify-center group-hover:scale-105 transition-transform shadow-xs ring-1 ring-white/50">
                                    @if($catTheme['icon'] === 'coffee')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10h14a1 1 0 011 1v5a3 3 0 01-3 3H7a3 3 0 01-3-3v-5a1 1 0 011-1zm2-2V7a5 5 0 0110 0v1M9 18h6"/></svg>
                                    @elseif($catTheme['icon'] === 'coldbrew')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    @elseif($catTheme['icon'] === 'leaf')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                    @elseif($catTheme['icon'] === 'chili')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/></svg>
                                    @elseif($catTheme['icon'] === 'fire')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    @elseif($catTheme['icon'] === 'wings')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                    @elseif($catTheme['icon'] === 'burger')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                                    @elseif($catTheme['icon'] === 'dessert')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.701 2.701 0 01-1.5-.454M9 6v2m3-2v2m3-2v2M9 3h.01M12 3h.01M15 3h.01M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                                    @elseif($catTheme['icon'] === 'drinks')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    @else
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10h14a1 1 0 011 1v5a3 3 0 01-3 3H7a3 3 0 01-3-3v-5a1 1 0 011-1zm2-2V7a5 5 0 0110 0v1M9 18h6"/></svg>
                                    @endif
                                </div>
                                <span class="text-xs font-bold text-gray-600 uppercase tracking-wider bg-gray-100 border border-gray-200 px-2 py-0.5 rounded-lg">
                                    {{ $product->sizes->count() }} {{ $product->sizes->count() === 1 ? 'size' : 'sizes' }}
                                </span>
                            </div>

                            <p class="font-extrabold text-gray-950 text-base leading-snug group-hover:text-heim-800 transition-colors line-clamp-2">{{ $product->name }}</p>
                            <p class="mt-1.5 text-xs leading-relaxed text-slate-600" data-product-size-prices>
                                @foreach($product->sizes as $size)
                                    <span>{{ $size->size_name }} ₱{{ number_format($size->price, 2) }}</span>@if(!$loop->last)<span aria-hidden="true"> · </span>@endif
                                @endforeach
                            </p>
                        </div>

                        <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-xs font-extrabold {{ $isOutOfStock ? 'text-rose-700 bg-rose-50 border-rose-200' : ($isLowStock ? 'text-amber-800 bg-amber-50 border-amber-200' : 'text-emerald-800 bg-emerald-50 border-emerald-200') }} border px-2.5 py-1 rounded-xl" data-stock-status>
                                {{ $isOutOfStock ? 'OUT OF STOCK' : ($isLowStock ? '⚠ Low stock' : 'Available') }}
                            </span>

                            <span class="w-8 h-8 rounded-xl bg-gray-100 text-gray-500 group-hover:bg-heim-700 group-hover:text-white flex items-center justify-center transition-all shadow-xs" aria-hidden="true">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                </svg>
                            </span>
                        </div>
                    </button>
                    @endforeach
                @endforeach
            </div>
            <div id="product-grid-empty" class="hidden py-12 text-center" role="status" aria-live="polite">
                <p class="text-sm font-semibold text-gray-700">No menu items match your search.</p>
                <p class="mt-1 text-xs text-gray-500">Try another name or clear the search and category filters.</p>
                <button type="button" onclick="clearProductFilters()" class="mt-3 rounded-lg border border-heim-200 bg-white px-3 py-2 text-xs font-bold text-heim-800 hover:bg-heim-50">
                    Clear filters
                </button>
            </div>
        </div>
    </div>

    {{-- ── Right: Order Panel ────────────────────────────────────────────────── --}}
    <div class="pos-order-panel order-2 flex h-auto min-h-0 w-full min-w-0 flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm lg:h-full">

        <div class="flex items-start justify-between gap-3 border-b border-slate-100 bg-white px-4 py-3.5 sm:px-5">
            <div class="min-w-0">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-heim-800">Current Order</p>
                <p id="current-ticket-number" class="mt-0.5 truncate font-mono text-sm font-bold text-slate-700">New ticket</p>
            </div>
            <div class="text-right text-xs leading-5 text-slate-600">
                <p><span class="font-semibold">Customer:</span> <span id="current-order-customer">—</span></p>
                <p><span class="font-semibold">Order type:</span> <span id="current-order-type">Dine-in</span></p>
            </div>
        </div>

        {{-- Order Type Toggle: Dine-in/Take-out | GRAB --}}
        <div class="mx-3 mt-2 mb-1.5 flex shrink-0 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" role="group" aria-label="Order type">
            <button type="button" id="ot-dine-in" onclick="setOrderType('dine_in')"
                aria-pressed="true" class="flex-1 py-2.5 text-sm font-extrabold transition-all bg-heim-700 text-white">
                Dine-in
            </button>
            <button type="button" id="ot-take-out" onclick="setOrderType('take_out')"
                aria-pressed="false" class="flex-1 py-2.5 text-sm font-extrabold transition-all bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-800">
                Take-out
            </button>
            <button type="button" id="ot-grab" onclick="setOrderType('grab')"
                aria-pressed="false" class="flex-1 py-2.5 text-sm font-extrabold transition-all bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-800">
                Grab
            </button>
        </div>

        {{-- Grab Order Fields (hidden by default) --}}
        <div id="grab-fields" class="hidden shrink-0 px-4 py-3.5 bg-emerald-50/60 border-b border-emerald-200 space-y-2.5">
            <div class="flex items-center justify-between gap-2">
                <p class="text-xs font-extrabold uppercase tracking-wider text-emerald-800">Grab Order</p>
                <span class="rounded-full border border-emerald-200 bg-white px-2.5 py-1 text-[11px] font-black text-emerald-800">Grab Price: ON</span>
            </div>
            <div class="grid grid-cols-2 gap-2.5">
                <div>
                    <label class="text-xs font-bold text-emerald-900 block mb-1">Grab Order Code <span class="text-rose-500">*</span></label>
                    <input id="grab-order-code" type="text" placeholder="GF-20260927-0012"
                        class="w-full border border-emerald-300 bg-white rounded-xl px-3.5 py-2.5 text-sm font-mono font-bold text-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 uppercase"
                        oninput="this.value = this.value.toUpperCase()" autocomplete="off">
                </div>
                <div>
                    <label class="text-xs font-bold text-emerald-900 block mb-1">Rider Code <span class="text-rose-500">*</span></label>
                    <input id="grab-rider-code" type="text" placeholder="RDR-025"
                        class="w-full border border-emerald-300 bg-white rounded-xl px-3.5 py-2.5 text-sm font-mono font-bold text-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 uppercase"
                        oninput="this.value = this.value.toUpperCase()" autocomplete="off">
                </div>
            </div>
            <p class="text-xs text-emerald-700 font-medium flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                GrabFood is the platform; Grab pricing applies automatically.
            </p>
        </div>

        {{-- Cashier and customer details --}}
        <div class="shrink-0 space-y-2 border-b border-heim-100 bg-gradient-to-r from-heim-50 to-white px-3 py-2.5 sm:px-4">
            <div class="grid grid-cols-2 gap-2.5">
                <div class="min-w-0">
                    <span class="mb-1 block text-[11px] font-extrabold uppercase tracking-wider text-heim-800">Cashier</span>
                    <p class="truncate text-sm font-bold leading-5 text-slate-900">{{ auth()->user()?->name }}</p>
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ auth()->user()?->role }}</span>
                    <input id="cashier-name" type="hidden" value="{{ auth()->user()?->name }}">
                </div>
                <div class="min-w-0">
                    <label id="order-customer-label" for="order-customer-name" class="mb-1 block text-[11px] font-extrabold uppercase tracking-wider text-gray-600">Customer / Table</label>
                    <input id="order-customer-name" type="text" maxlength="150" placeholder="Customer name or table"
                        oninput="updateOrderSummary()"
                        class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-heim-500">
                </div>
            </div>
            <div class="flex items-center justify-end">
                <label class="inline-flex min-h-8 cursor-pointer items-center gap-2 rounded-lg border border-heim-200 bg-heim-50 px-2.5 text-xs font-extrabold text-heim-900">
                    <input id="split-toggle" type="checkbox" onchange="toggleSplitAssignments(this.checked)" class="h-4 w-4 rounded border-gray-300 text-heim-600 focus:ring-heim-500">
                    Split Payment
                </label>
            </div>
            <div id="split-people-panel" class="hidden rounded-xl border border-indigo-200 bg-indigo-50/60 p-3">
                <p class="text-xs font-black uppercase tracking-wider text-indigo-900">Split Order</p>
                <div id="split-people" class="mt-2 flex flex-wrap items-center gap-2" role="group" aria-label="Split order people"></div>
                <div class="mt-3 flex gap-2">
                    <input id="new-split-person" type="text" maxlength="150" placeholder="Add a person"
                        onkeydown="if(event.key==='Enter'){event.preventDefault();addSplitPerson();}"
                        class="min-w-0 flex-1 rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500">
                    <button type="button" onclick="addSplitPerson()" class="rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm font-bold text-indigo-900 hover:bg-indigo-100">Add person</button>
                </div>
                <label for="split-assignee-select" class="mt-3 block text-xs font-bold text-indigo-900">Assign products to</label>
                <select id="split-assignee-select" onchange="selectSplitPersonByValue(this.value)" class="mt-1 w-full rounded-xl border border-indigo-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-heim-500">
                    <option value="">Choose a person</option>
                </select>
            </div>
        </div>
        <p id="pos-feedback" class="hidden mx-4 mt-3 shrink-0 rounded-xl border px-3.5 py-2.5 text-xs font-semibold" role="status" aria-live="polite"></p>

        {{-- Cart header --}}
        <div class="flex shrink-0 items-center justify-between gap-2 px-3 py-2 sm:px-4 border-b border-gray-100 bg-white/80">
            <div class="flex items-center gap-2.5">
                <div>
                    <span class="block text-[10px] font-black uppercase tracking-[0.18em] text-gray-500">Current order</span>
                    <span class="block font-black text-gray-900 text-base leading-5">Order items</span>
                </div>
                <span id="cart-badge" class="px-3 py-1 rounded-full text-sm font-black bg-heim-100 text-heim-800">0</span>
            </div>
            <div class="flex shrink-0 items-center gap-1.5">
                <button type="button" onclick="holdCurrentOrder()" id="hold-btn" title="Save this order without completing payment"
                    class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-2 rounded-xl bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 transition-colors disabled:opacity-40 disabled:cursor-not-allowed whitespace-nowrap"
                    disabled>
                    <span>📌</span> Hold
                </button>
                <button onclick="clearCart()" class="text-xs text-gray-500 hover:text-rose-600 transition-colors font-bold px-1.5 py-1 whitespace-nowrap">Clear</button>
            </div>
        </div>

        {{-- Cart Items --}}
        <div id="cart-items" class="min-h-[15rem] flex-1 space-y-3 overflow-y-auto overscroll-contain bg-slate-50/60 p-3 sm:p-3.5 lg:min-h-[15rem]">
            <div id="empty-cart" class="flex flex-col items-center justify-center h-full min-h-[14rem] text-center py-10 px-4 select-none">
                <div class="w-16 h-16 rounded-2xl bg-amber-50 border border-amber-200/80 flex items-center justify-center text-3xl mb-3 shadow-xs">
                    ☕
                </div>
                <p class="text-base font-extrabold text-slate-800">Your ticket is empty</p>
                <p class="text-xs font-semibold text-slate-500 mt-1 max-w-xs leading-relaxed">Tap any item from the catalog on the left to add it to this order.</p>
            </div>
            <div id="cart-list" class="space-y-3" role="list" aria-label="Order items list"></div>
        </div>

        {{-- Discount Dropdown & Tax Breakdown --}}
        <div class="shrink-0 px-3 py-2.5 sm:px-4 border-t border-gray-100 bg-gray-50/50 space-y-2">
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="discount-type" class="text-sm font-extrabold text-gray-800">Discount</label>
                    <span id="discount-badge" class="hidden text-xs font-bold uppercase tracking-wider px-2 py-0.5 rounded-lg bg-amber-100 text-amber-800">
                        20% Off
                    </span>
                </div>
                <p class="mb-1 text-[10px] leading-4 text-gray-500">Senior/PWD discounts require an ID. Custom discounts are limited to managers and owners.</p>
                <select id="discount-type" onchange="onDiscountTypeChange()"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm font-bold text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-heim-500/20 focus:border-heim-500 shadow-2xs">
                    <option value="none">No Discount (0%)</option>
                    <option value="senior">Senior Citizen (20% Off)</option>
                    <option value="pwd">PWD (20% Off)</option>
                    @if(auth()->user()?->canAuthorize())
                        <option value="custom">Custom Amount (₱)</option>
                    @endif
                </select>
            </div>

            {{-- Senior Citizen / PWD ID Input (Visible for SC / PWD) --}}
            <div id="discount-id-wrapper" class="hidden space-y-1.5">
                <label id="discount-id-label" for="discount-id-number" class="text-xs font-bold text-heim-900 block">
                    Customer ID Number
                </label>
                <input id="discount-id-number" type="text" placeholder="e.g. SC-12345 or PWD-67890" maxlength="100"
                    data-discount-type="none"
                    class="w-full border border-heim-200 bg-white rounded-xl px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-1 focus:ring-heim-400">
                <p class="text-xs text-emerald-700 font-semibold flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    20% discount on the VAT-exclusive amount + VAT exemption
                </p>
            </div>

            {{-- Custom Discount Amount Input (Visible for Custom) --}}
            <div id="custom-discount-wrapper" class="hidden">
                <div class="flex items-center gap-2.5">
                    <label for="discount" class="text-xs font-bold text-gray-600 whitespace-nowrap">Amount (₱)</label>
                    <input id="discount" type="number" min="0" value="0" step="0.01"
                        oninput="updateTotals()"
                        class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm text-right font-mono font-bold focus:outline-none focus:ring-1 focus:ring-heim-400">
                </div>
            </div>
        </div>

        {{-- Totals & Tax Information --}}
        <div class="shrink-0 px-3 pb-3 sm:px-4 space-y-1.5 border-t border-gray-100 pt-2.5 text-sm">
            <div class="flex justify-between text-gray-600 font-medium">
                <span>Subtotal</span>
                <span id="subtotal-display" class="font-mono text-gray-900 font-bold text-base">₱0.00</span>
            </div>
            <div id="discount-display-row" class="hidden flex justify-between text-rose-600 font-bold text-base">
                <span id="discount-display-label">Discount</span>
                <span id="discount-display" class="font-mono">-₱0.00</span>
            </div>

            {{-- Tax information rows --}}
            <div class="pt-2 pb-2 border-y border-dashed border-gray-200 text-xs text-gray-500 space-y-1">
                <div class="flex justify-between">
                    <span>VATable Sales</span>
                    <span id="vatable-display" class="font-mono text-gray-700 font-semibold">₱0.00</span>
                </div>
                <div id="exempt-row" class="hidden flex justify-between text-emerald-700 font-semibold">
                    <span>VAT-Exempt Sales</span>
                    <span id="exempt-display" class="font-mono font-bold">₱0.00</span>
                </div>
                <div class="flex justify-between font-semibold text-gray-600">
                    <span id="tax-name-label">{{ $taxSetting->name ?? 'VAT' }} ({{ number_format($taxSetting->rate ?? 12, 2) }}%)</span>
                    <span id="tax-display" class="font-mono font-bold">₱0.00</span>
                </div>
            </div>

            <div class="flex justify-between items-baseline font-black text-gray-900 pt-1.5">
                <span class="text-lg uppercase tracking-wider">TOTAL</span>
                <span id="total-display" class="font-mono text-heim-800 text-2xl font-black">₱0.00</span>
            </div>
        </div>

        {{-- Checkout button --}}
        <div class="sticky bottom-0 z-20 shrink-0 bg-white p-3 sm:p-4 border-t border-gray-100 shadow-[0_-8px_16px_rgba(15,23,42,0.08)]">
            <button onclick="openCheckout()" id="checkout-btn"
                class="w-full bg-heim-700 hover:bg-heim-800 text-white font-black py-4 sm:py-[1.125rem] rounded-2xl shadow-md hover:shadow-lg transition-all disabled:opacity-40 disabled:cursor-not-allowed text-lg tracking-wide flex items-center justify-center gap-2"
                disabled>
                <span id="checkout-button-label">PAY ₱0.00</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
        </div>
    </div>
</div>
</div>

<button type="button" id="mobile-order-shortcut" onclick="scrollToOrderPanel()"
    class="fixed inset-x-4 bottom-4 z-30 hidden flex items-center justify-between rounded-2xl bg-heim-800 px-4 py-3 text-left text-white shadow-xl ring-1 ring-white/20 lg:hidden"
    aria-label="View current order">
    <span class="flex min-w-0 items-center gap-2">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white/15" aria-hidden="true">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 6h14M5 12h14M5 18h8"/></svg>
        </span>
        <span class="min-w-0">
            <span class="block text-xs font-extrabold"><span id="mobile-order-count">0 items</span> · View order</span>
            <span class="block text-[10px] font-semibold text-emerald-100">Current ticket</span>
        </span>
    </span>
    <span id="mobile-order-total" class="ml-3 shrink-0 font-mono text-base font-black">₱0.00</span>
</button>

{{-- ── Held Orders Modal ─────────────────────────────────────────────────────── --}}
<div id="held-orders-modal" class="fixed inset-0 bg-black/60 z-50 hidden items-center justify-center p-4" style="display:none">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl max-h-[calc(100dvh-3rem)] flex flex-col overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-amber-50/50">
            <div class="flex items-center gap-2.5">
                <span class="text-xl">📌</span>
                <div>
                    <h3 class="font-bold text-gray-900 text-base">Held & Pinned Tickets</h3>
                    <p class="text-xs text-gray-500">Unfinished orders saved for later reopening and payment</p>
                </div>
            </div>
            <button type="button" onclick="closeHeldOrdersModal()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div id="held-orders-list" class="p-6 overflow-y-auto space-y-3 flex-1 min-h-[16rem]">
            {{-- Dynamically populated --}}
        </div>

        <div class="px-6 py-3.5 border-t border-gray-100 bg-gray-50 flex items-center justify-between">
            <span id="held-modal-total-count" class="text-xs font-semibold text-gray-500">0 orders on hold</span>
            <button type="button" onclick="closeHeldOrdersModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-xl text-xs font-semibold hover:bg-gray-100 transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

{{-- ── Void Ticket Modal ─────────────────────────────────────────────────────── --}}
<div id="void-ticket-modal" class="fixed inset-0 bg-black/60 z-[60] items-center justify-center p-4" style="display:none">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm flex flex-col overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-rose-50/60">
            <div class="flex items-center gap-2.5">
                <span class="text-xl">🚫</span>
                <div>
                    <h3 class="font-bold text-gray-900 text-base">Void Saved Ticket</h3>
                    <p id="void-ticket-order-number" class="text-xs text-gray-500"></p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('void-ticket-modal').style.display='none'"
                class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="p-6 space-y-4">
            <p class="text-sm text-gray-600">Please provide a reason for voiding this ticket. This action cannot be undone.</p>
            <div>
                <label for="void-ticket-reason" class="block text-xs font-semibold text-gray-700 mb-1.5">Reason / Comment <span class="text-rose-500">*</span></label>
                <textarea id="void-ticket-reason"
                    class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-rose-400 focus:border-transparent resize-none"
                    rows="3"
                    placeholder="e.g. Customer cancelled order, duplicate entry…"></textarea>
                <p id="void-ticket-error" class="hidden mt-1.5 text-xs text-rose-600 font-medium"></p>
            </div>
        </div>

        <div class="px-6 pb-6 flex gap-3">
            <button type="button" onclick="document.getElementById('void-ticket-modal').style.display='none'"
                class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-xl text-sm font-semibold hover:bg-gray-50 transition-colors">
                Cancel
            </button>
            <button type="button" id="void-ticket-confirm-btn" onclick="confirmVoidHeldOrder()"
                class="flex-1 px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                Void Ticket
            </button>
        </div>
    </div>
</div>

{{-- ── Size Selection Modal ──────────────────────────────────────────────────── --}}
<div id="size-modal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="size-modal-title" style="display:none">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg sm:max-w-xl max-h-[calc(100dvh-4rem)] flex flex-col overflow-hidden border border-gray-100" id="size-modal-content">
        <div class="p-5 sm:p-6 border-b border-gray-100 flex items-center justify-between bg-heim-50/50">
            <div>
                <h3 id="size-modal-title" class="font-black text-gray-900 text-xl tracking-tight">Select Size</h3>
                <p class="text-xs text-gray-500 mt-0.5">Customize size and add-ons for this item</p>
            </div>
            <button type="button" onclick="closeSizeModal()" class="text-gray-400 hover:text-gray-700 p-2 rounded-xl hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-heim-500" aria-label="Close modal">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div id="size-options" class="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1"></div>
        <div class="p-4 sm:p-5 border-t border-gray-100 bg-gray-50/80 flex items-center justify-end gap-3 shrink-0">
            <button type="button" onclick="closeSizeModal()" class="px-5 py-3 rounded-xl border border-gray-300 text-sm font-bold text-gray-700 hover:bg-gray-100 transition-colors">Cancel</button>
            <button type="button" onclick="confirmAddToCart()" class="px-6 py-3 rounded-xl bg-heim-700 hover:bg-heim-800 text-white text-sm font-black shadow-sm hover:shadow transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Add to Order</span>
            </button>
        </div>
    </div>
</div>

{{-- ── Checkout Modal ────────────────────────────────────────────────────────── --}}
<div id="checkout-modal" class="fixed inset-0 z-50 hidden items-end justify-center bg-black/60 p-0 sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="checkout-modal-title" style="display:none">
    <div class="flex h-[100dvh] max-h-[100dvh] w-full flex-col overflow-hidden bg-white shadow-2xl sm:h-auto sm:max-h-[calc(100dvh-2rem)] sm:max-w-md sm:rounded-2xl">
        {{-- Header --}}
        <div class="flex shrink-0 items-center justify-between border-b border-gray-100 bg-white px-4 py-3 sm:px-6 sm:pt-6 sm:pb-4">
            <div>
                <h3 id="checkout-modal-title" class="text-lg font-bold text-gray-900">Payment</h3>
                <p class="text-sm text-gray-400 mt-0.5">Order total: <span id="co-total" class="font-extrabold text-heim-700"></span> · Remaining: <span id="co-remaining" class="font-bold text-gray-700"></span></p>
            </div>
            <button type="button" onclick="closeCheckout()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors focus:outline-none" aria-label="Close modal">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain p-4 sm:space-y-5 sm:p-6">
            <details id="checkout-order-review" class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-800">
                    <span>Review order <span id="checkout-review-count" class="ml-1 text-xs font-medium text-slate-500"></span></span>
                    <span class="text-xs font-semibold text-heim-800">View items</span>
                </summary>
                <div id="checkout-review-rows" class="max-h-52 divide-y divide-slate-100 overflow-y-auto"></div>
            </details>

            {{-- Payment method selector --}}
            <div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2.5">Payment Method</p>
                <div class="grid grid-cols-2 gap-2" id="payment-method-grid">
                    <!-- Cash -->
                    <button type="button" onclick="selectPaymentMethod('cash')"
                        id="pm-cash"
                        class="pm-btn active flex items-center gap-2 p-3 rounded-xl border-2 border-heim-500 bg-heim-50 transition-all">
                        <span class="text-xl">💵</span>
                        <div class="text-left">
                            <p class="text-xs font-bold text-gray-800">Cash</p>
                            <p class="text-[10px] text-gray-500">Physical tender</p>
                        </div>
                    </button>
                    <!-- Online Payment -->
                    <button type="button" onclick="selectPaymentMethod('online')"
                        id="pm-online"
                        class="pm-btn flex items-center gap-2 p-3 rounded-xl border-2 border-gray-200 bg-white hover:border-blue-300 hover:bg-blue-50/40 transition-all">
                        <span class="text-xl">📱</span>
                        <div class="text-left">
                            <p class="text-xs font-bold text-gray-800">Online</p>
                            <p class="text-[10px] text-gray-500">E-Wallet / GCash</p>
                        </div>
                    </button>

                    <!-- GrabFood -->
                    <button type="button" onclick="selectPaymentMethod('grabfood')"
                        id="pm-grabfood"
                        class="pm-btn flex items-center gap-2 p-3 rounded-xl border-2 border-gray-200 bg-white hover:border-emerald-300 hover:bg-emerald-50/40 transition-all">
                        <span class="text-xl">🛵</span>
                        <div class="text-left">
                            <p class="text-xs font-bold text-gray-800">GrabFood</p>
                            <p class="text-[10px] text-gray-500">Platform settlement</p>
                        </div>
                    </button>
                </div>
                <p id="grab-payment-hint" class="hidden mt-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] font-medium text-emerald-800">
                    Grab orders can be paid in cash at the counter or recorded as a GrabFood platform settlement.
                </p>
            </div>

            <div class="space-y-3 rounded-xl border border-gray-100 bg-gray-50/70 p-3">
                <div>
                    <label for="payment-person-name" class="block text-xs font-bold text-gray-700 mb-1.5">Paying Person</label>
                    <select id="payment-person-name" onchange="selectCheckoutPayer()"
                        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-heim-500">
                        <option value="">Full order / select a person</option>
                    </select>
                    <p id="payer-assignment-hint" class="text-[11px] text-gray-500 mt-1">For a split order, choose the person whose assigned products this payment covers.</p>
                </div>
                <div>
                    <label for="amount-to-pay" class="block text-xs font-bold text-gray-700 mb-1.5">Payment Amount (₱)</label>
                    <input id="amount-to-pay" type="number" min="0.01" step="0.01"
                        oninput="computeChange(); updateOnlineAmount()"
                        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-right font-mono text-lg font-bold bg-white focus:outline-none focus:ring-2 focus:ring-heim-500">
                    <p class="text-[11px] text-gray-500 mt-1">A smaller amount records a partial payment.</p>
                </div>
                <div>
                    <label for="payment-comment" class="block text-xs font-bold text-gray-700 mb-1.5">Platform / Payment Note <span class="font-normal text-gray-400">(optional)</span></label>
                    <input id="payment-comment" type="text" maxlength="255" placeholder="e.g. GCash personal account"
                        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-heim-500">
                </div>
            </div>
            <div id="checkout-split-summary" class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white">
                <div class="border-b border-gray-100 bg-gray-50 px-3 py-2 text-xs font-bold text-gray-700">Split payment summary</div>
                <div id="checkout-split-rows"></div>
            </div>
            <div id="checkout-staged-summary" class="hidden overflow-hidden rounded-xl border border-heim-200 bg-heim-50/60">
                <div class="flex items-center justify-between border-b border-heim-100 px-3 py-2 text-xs font-bold text-heim-900">
                    <span>Recorded in this checkout</span>
                    <span id="checkout-staged-remaining">₱0.00 remaining</span>
                </div>
                <div id="checkout-staged-rows" class="divide-y divide-heim-100"></div>
            </div>

            {{-- Cash section --}}
            <div id="cash-section" class="space-y-3">
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-sm font-medium text-gray-700">Amount Received (₱)</label>
                        <button type="button" onclick="setCashAmount(orderTotal)" class="text-xs font-semibold text-heim-600 hover:text-heim-800 bg-heim-50 hover:bg-heim-100 px-2 py-0.5 rounded-md transition-colors">
                            Exact Amount
                        </button>
                    </div>
                    <input id="amount-received" type="number" min="0" step="0.01" placeholder="0.00"
                        oninput="computeChange()"
                        class="w-full border border-gray-300 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 text-right font-mono text-lg font-bold">
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <button type="button" onclick="setCashAmount(orderTotal)" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-heim-100 text-gray-700 hover:text-heim-800 transition-colors">Exact</button>
                    <button type="button" onclick="setCashAmount(100)"  class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-heim-100 text-gray-700 hover:text-heim-800 transition-colors">₱100</button>
                    <button type="button" onclick="setCashAmount(200)"  class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-heim-100 text-gray-700 hover:text-heim-800 transition-colors">₱200</button>
                    <button type="button" onclick="setCashAmount(500)"  class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-heim-100 text-gray-700 hover:text-heim-800 transition-colors">₱500</button>
                    <button type="button" onclick="setCashAmount(1000)" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-heim-100 text-gray-700 hover:text-heim-800 transition-colors">₱1,000</button>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-xl text-sm border border-gray-100">
                    <span class="text-gray-500 font-medium">Change:</span>
                    <span id="change-display" class="font-bold text-base text-heim-700 font-mono">₱0.00</span>
                </div>
            </div>

            {{-- Online payment (reference number) section --}}
            <div id="online-section" class="hidden space-y-3">
                <div class="flex items-start gap-3 bg-blue-50 border border-blue-200 rounded-xl p-3">
                    <svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs text-blue-700 font-medium" id="online-hint">Ask customer to complete the online payment and verify the reference number.</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">
                        Reference No. <span class="text-red-500">*</span>
                    </label>
                    <input id="reference-number-input" type="text" placeholder="e.g. Ref No. 1234567890"
                        class="w-full border-2 border-gray-300 rounded-xl px-3.5 py-2.5 text-sm font-mono font-bold tracking-wider focus:outline-none focus:ring-2 focus:ring-heim-500 focus:border-heim-500 uppercase"
                        oninput="this.value = this.value.toUpperCase()"
                        autocomplete="off">
                    <p class="text-xs text-gray-400 mt-1">Enter the confirmation/reference number from the sender's payment app.</p>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-xl text-sm border border-gray-100">
                    <span class="text-gray-500 font-medium">Amount Due:</span>
                    <span class="font-extrabold text-heim-700 text-base font-mono" id="online-amount-display">₱0.00</span>
                </div>
            </div>

            {{-- Pay Later section --}}
            <div id="pay-later-section" class="hidden space-y-3">
                <div class="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-xl p-3">
                    <span class="text-amber-600 text-sm mt-0.5">📋</span>
                    <p class="text-xs text-amber-800 font-medium">This order will be saved as a <strong>Pay Later / Charge</strong> account. The customer will pay the balance at a later time.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-gray-600 mb-1.5">Customer Name <span class="text-rose-500">*</span></label>
                        <input id="pl-customer-name" type="text" placeholder="Full name of customer"
                            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1.5">Phone (optional)</label>
                        <input id="pl-customer-phone" type="text" placeholder="09xx-xxx-xxxx"
                            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1.5">Due Date (optional)</label>
                        <input id="pl-due-date" type="date"
                            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-gray-600 mb-1.5">Notes / Reason (optional)</label>
                        <input id="pl-notes" type="text" placeholder="Reason for charge account..."
                            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>
                <div class="flex justify-between items-center p-3 bg-amber-50/60 rounded-xl text-sm border border-amber-200">
                    <span class="text-gray-600 font-medium">Amount to Charge:</span>
                    <span class="font-extrabold text-amber-700 text-base font-mono" id="pl-amount-display">₱0.00</span>
                </div>
            </div>

            <p id="checkout-error" class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2 hidden"></p>
        </div>

        <div class="grid shrink-0 grid-cols-2 gap-2 border-t border-gray-100 bg-white p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] sm:gap-3 sm:px-6 sm:pt-3 sm:pb-6">
            <button type="button" onclick="closeCheckout()" class="brand-btn-cancel">Back</button>
            <button type="button" id="record-payment-btn" onclick="stageCheckoutPayment()"
                class="rounded-xl border border-heim-200 bg-white px-3 py-2.5 text-xs font-bold text-heim-800 hover:bg-heim-50 transition-colors">
                Record Payment
            </button>
            <button type="button" id="complete-btn" onclick="completeOrder()" class="brand-button active:scale-95 col-span-2">
                Complete Order
            </button>
        </div>
    </div>
</div>

{{-- Hidden form --}}
<form id="order-form" method="POST" action="{{ route('pos.store') }}" class="hidden">
    @csrf
    <input type="hidden" name="cashier_name"          id="f-cashier">
    <input type="hidden" name="payment_method"        id="f-method">
    <input type="hidden" name="amount_received"       id="f-amount">
    <input type="hidden" name="amount_paid"           id="f-amount-paid">
    <input type="hidden" name="person_name"           id="f-person-name">
    <input type="hidden" name="payment_comment"       id="f-payment-comment">
    <input type="hidden" name="discount"              id="f-discount">
    <input type="hidden" name="discount_type"         id="f-discount-type">
    <input type="hidden" name="discount_label"        id="f-discount-label">
    <input type="hidden" name="discount_id_number"    id="f-discount-id-number">
    <input type="hidden" name="authorizer_email"      id="f-auth-email">
    <input type="hidden" name="authorizer_password"   id="f-auth-password">
    <input type="hidden" name="reference_number"      id="f-reference">
    <input type="hidden" name="held_order_id"         id="f-held-order-id">
    <div id="f-payments"></div>
    {{-- Grab order fields --}}
    <input type="hidden" name="order_type"            id="f-order-type" value="dine_in">
    <input type="hidden" name="grab_order_code"       id="f-grab-order-code">
    <input type="hidden" name="rider_code"            id="f-rider-code">
    <input type="hidden" name="customer_name"         id="f-customer-name">
    <div id="f-items"></div>
</form>

{{-- ── Shift In Modal ─────────────────────────────────────────────────────── --}}
<div id="shift-in-modal" class="fixed inset-0 bg-black/60 z-50 hidden items-center justify-center p-4" style="display:none">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-white flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-700 text-xl">⏱️</div>
            <div>
                <h3 class="font-bold text-gray-900 text-base">Start Shift</h3>
                <p class="text-xs text-gray-500">Record your beginning cash before taking orders</p>
            </div>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-3 text-xs text-gray-600">
                <div class="bg-gray-50 rounded-xl p-3">
                    <p class="text-[10px] uppercase tracking-wider font-bold text-gray-400 mb-0.5">Cashier</p>
                    <p class="font-bold text-gray-900 text-sm" id="si-cashier-name">{{ auth()->user()->name }}</p>
                </div>
                <div class="bg-gray-50 rounded-xl p-3">
                    <p class="text-[10px] uppercase tracking-wider font-bold text-gray-400 mb-0.5">Date &amp; Time</p>
                    <p class="font-bold text-gray-900 text-sm" id="si-datetime">{{ now(config('app.business_timezone', 'Asia/Manila'))->format('M d, Y h:i A') }}</p>
                </div>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Beginning Cash <span class="text-rose-500">*</span></label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-sm font-bold text-gray-400">₱</span>
                    <input id="si-beginning-cash" type="number" min="0" step="0.01" placeholder="0.00"
                        class="w-full pl-8 pr-4 py-3 text-lg font-bold font-mono border-2 border-gray-200 rounded-xl focus:border-emerald-500 focus:outline-none text-right">
                </div>
            </div>
            <p id="si-error" class="hidden text-xs text-rose-600 bg-rose-50 border border-rose-200 rounded-lg px-3 py-2"></p>
        </div>
        <div class="flex gap-3 px-6 pb-6">
            <button type="button" onclick="closeShiftInModal()" class="brand-btn-cancel flex-1">Cancel</button>
            <button type="button" onclick="submitShiftIn()" id="si-submit-btn" class="brand-button flex-1">▶ Start Shift</button>
        </div>
    </div>
</div>

{{-- ── Shift Out Modal ─────────────────────────────────────────────────────── --}}
<div id="shift-out-modal" class="fixed inset-0 bg-black/60 z-50 hidden items-center justify-center p-4" style="display:none">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-rose-50 to-white flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center text-rose-700 text-xl">🔒</div>
            <div>
                <h3 class="font-bold text-gray-900 text-base">End Shift</h3>
                <p class="text-xs text-gray-500">Count your cash drawer and close out the shift</p>
            </div>
        </div>
        <div class="p-6 space-y-4">
            <div class="space-y-2" id="so-cash-summary">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Cashier</span>
                    <span class="font-bold text-gray-900" id="so-cashier">-</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Beginning Cash</span>
                    <span class="font-mono font-bold text-gray-900" id="so-beginning">₱0.00</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Cash Sales</span>
                    <span class="font-mono font-bold text-gray-900" id="so-cash-sales">Hidden until counted</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Cash Refunds</span>
                    <span class="font-mono font-bold text-rose-700" id="so-cash-refunds">Hidden until counted</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Cash voids</span>
                    <span class="font-mono font-bold text-rose-700" id="so-cash-voids">Hidden until counted</span>
                </div>
                <div class="flex justify-between text-sm font-semibold border-t border-dashed border-gray-200 pt-2">
                    <span class="text-gray-700">Expected Cash</span>
                    <span class="font-mono text-gray-900" id="so-expected">Enter count to reveal</span>
                </div>
                <div class="mt-3 border-t border-gray-200 pt-2 text-xs text-gray-600">
                    <p class="mb-1 font-bold text-gray-700">Other activity · not in drawer</p>
                    <div class="flex justify-between"><span>Online / e-wallet</span><span id="so-online-sales">₱0.00</span></div>
                    <div class="flex justify-between"><span>Grab orders / settlements</span><span id="so-grab-sales">₱0.00 / ₱0.00</span></div>
                    <div class="flex justify-between"><span>Dine-in / Take-out</span><span id="so-order-type-sales">₱0.00 / ₱0.00</span></div>
                    <div class="flex justify-between"><span>Voids</span><span id="so-voids">0 · ₱0.00</span></div>
                </div>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Actual Cash in Drawer <span class="text-rose-500">*</span></label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-sm font-bold text-gray-400">₱</span>
                    <input id="so-actual-cash" type="number" min="0" step="0.01" placeholder="0.00"
                        oninput="updateShiftOutDifference()"
                        class="w-full pl-8 pr-4 py-3 text-lg font-bold font-mono border-2 border-gray-200 rounded-xl focus:border-rose-400 focus:outline-none text-right">
                </div>
            </div>
            <label class="flex items-center gap-2 text-xs font-semibold text-gray-700">
                <input id="so-use-denominations" type="checkbox" onchange="toggleShiftDenominations()" class="rounded border-gray-300 text-heim-600">
                Count by denomination instead
            </label>
            <div id="so-denomination-panel" class="hidden rounded-xl border border-gray-200 bg-gray-50 p-3">
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    @foreach([1000, 500, 200, 100, 50, 20, 10, 5, 1] as $denomination)
                        <label class="text-[11px] font-semibold text-gray-600">₱{{ $denomination }} notes/coins
                            <input type="number" min="0" step="1" value="0" data-denomination="{{ $denomination }}" oninput="updateShiftOutDifference()" class="mt-1 w-full rounded-lg border border-gray-200 px-2 py-1.5 text-right text-sm">
                        </label>
                    @endforeach
                    @foreach(['0.25', '0.10', '0.05'] as $denomination)
                        <label class="text-[11px] font-semibold text-gray-600">₱{{ $denomination }} coins
                            <input type="number" min="0" step="1" value="0" data-denomination="{{ $denomination }}" oninput="updateShiftOutDifference()" class="mt-1 w-full rounded-lg border border-gray-200 px-2 py-1.5 text-right text-sm">
                        </label>
                    @endforeach
                </div>
                <p class="mt-2 text-right text-xs font-bold text-gray-700">Counted total: <span id="so-denomination-total">₱0.00</span></p>
            </div>
            <div id="so-open-orders-warning" class="hidden rounded-xl border border-amber-200 bg-amber-50 p-3">
                <p id="so-open-orders-text" class="text-xs font-semibold text-amber-900"></p>
                <p class="mt-1 text-[11px] text-amber-800">Settle tickets first or obtain manager/owner approval to close with them open.</p>
                <div class="mt-3 grid gap-2">
                    <input id="so-authorizer-email" type="email" placeholder="Manager/owner email (if required)" class="w-full rounded-lg border border-amber-200 px-3 py-2 text-xs">
                    <input id="so-authorizer-password" type="password" placeholder="Manager/owner password (if required)" class="w-full rounded-lg border border-amber-200 px-3 py-2 text-xs">
                    <input id="so-override-reason" type="text" minlength="10" maxlength="500" placeholder="Reason for closing with open tickets (required)" class="w-full rounded-lg border border-amber-200 px-3 py-2 text-xs">
                </div>
            </div>
            <div class="flex justify-between text-sm font-bold p-3 rounded-xl" id="so-diff-row">
                <span>Difference</span>
                <span class="font-mono" id="so-difference">-</span>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1.5">Comment (optional)</label>
                <input id="so-comment" type="text" placeholder="e.g. Short due to void"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs bg-gray-50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-rose-400">
            </div>
            <p id="so-error" class="hidden text-xs text-rose-600 bg-rose-50 border border-rose-200 rounded-lg px-3 py-2"></p>
        </div>
        <div class="flex gap-3 px-6 pb-6">
            <button type="button" onclick="closeShiftOutModal()" class="brand-btn-cancel flex-1">Cancel</button>
            <button type="button" onclick="submitShiftOut()" id="so-submit-btn" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 px-5 rounded-xl shadow-sm transition-colors flex-1">End Shift</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
@php
    $addonsData = $addons->map(function ($addon) {
        return [
            'id' => $addon->id,
            'name' => $addon->name,
            'price' => (float) $addon->price,
            'available' => $addon->addonIngredients->every(fn ($row) => $row->ingredient
                && $row->ingredient->inventory
                && (float) $row->ingredient->inventory->current_stock >= (float) $row->quantity),
        ];
    })->values()->toArray();
@endphp
const posAddons = @json($addonsData);
const taxConfig = {
    name: @json($taxSetting->name ?? 'VAT'),
    rate: @json((float) ($taxSetting->rate ?? 12.00)),
    isInclusive: @json((bool) ($taxSetting->is_inclusive ?? true)),
    isActive: @json((bool) ($taxSetting->is_active ?? true)),
};

const userCanAuthorize = @json(auth()->user()?->canAuthorize() ?? false);

let cart = [];
let currentProduct = null;
let currentProductSizes = [];
let currentMethod = 'cash';
let orderTotal = 0;
let currentDiscountAmount = 0;
let currentDiscountType = 'none';
let currentDiscountLabel = '';
let currentAuthData = null;
let currentHeldOrderId = null;
let currentHeldOrderNumber = null;
let currentOrderType = 'dine_in';
let splitEnabled = false;
let splitPeople = [];
let activeSplitPerson = '';
let stagedCheckoutPayments = [];
let shiftPreviewTimer = null;

function updateMobileOrderShortcut() {
    const shortcut = document.getElementById('mobile-order-shortcut');
    const countEl = document.getElementById('mobile-order-count');
    const totalEl = document.getElementById('mobile-order-total');
    if (!shortcut) return;

    const count = cart.reduce((sum, item) => sum + (parseInt(item.qty, 10) || 0), 0);
    const cartItems = document.getElementById('cart-items');
    const cartBounds = cartItems?.getBoundingClientRect();
    const visibleHeight = cartBounds
        ? Math.max(0, Math.min(cartBounds.bottom, window.innerHeight) - Math.max(cartBounds.top, 0))
        : 0;
    const visibleRatio = cartBounds?.height ? visibleHeight / cartBounds.height : 0;
    if (countEl) countEl.textContent = `${count} ${count === 1 ? 'item' : 'items'}`;
    if (totalEl) totalEl.textContent = `₱${Number(orderTotal || 0).toFixed(2)}`;
    shortcut.classList.toggle('hidden', count === 0 || visibleRatio >= 0.65);
}

function scrollToOrderPanel() {
    document.querySelector('.pos-order-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function updateOrderSummary() {
    const customer = document.getElementById('order-customer-name')?.value.trim();
    const customerEl = document.getElementById('current-order-customer');
    const typeEl = document.getElementById('current-order-type');
    const ticketEl = document.getElementById('current-ticket-number');
    const customerLabel = document.getElementById('order-customer-label');
    if (customerEl) customerEl.textContent = customer || '—';
    if (typeEl) typeEl.textContent = ({ dine_in: 'Dine-in', take_out: 'Take-out', grab: 'GrabFood' })[currentOrderType] || 'Dine-in';
    if (ticketEl) ticketEl.textContent = currentHeldOrderNumber ? `#${currentHeldOrderNumber}` : 'New ticket';
    if (customerLabel) customerLabel.textContent = currentOrderType === 'grab' ? 'Customer' : 'Customer / Table';
}

// ── Order Type ───────────────────────────────────────────────────────────────
function setOrderType(type) {
    currentOrderType = type;
    const isGrab = type === 'grab';
    const grabFields = document.getElementById('grab-fields');
    const modes = {
        dine_in: document.getElementById('ot-dine-in'),
        take_out: document.getElementById('ot-take-out'),
        grab: document.getElementById('ot-grab'),
    };
    Object.entries(modes).forEach(([mode, button]) => {
        if (!button) return;
        const active = mode === type;
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
        button.className = `flex-1 py-4 text-base font-extrabold transition-all ${active
            ? 'bg-heim-700 text-white'
            : 'bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-800'}`;
    });
    if (grabFields) {
        grabFields.classList.toggle('hidden', !isGrab);
    }
    updateOrderSummary();
    document.querySelectorAll('.product-card').forEach(card => {
        const summary = card.querySelector('[data-product-size-prices]');
        if (!summary) return;
        try {
            const sizes = JSON.parse(card.dataset.sizes || '[]');
            summary.textContent = sizes.map(size => {
                const price = isGrab ? (size.grab_price ?? size.price) : size.price;
                return `${size.size_name} ₱${Number(price).toFixed(2)}`;
            }).join(' · ');
        } catch (error) {
            console.error('Unable to update product prices for the selected order type.', error);
        }
    });

    // If switching to grab, auto-select GrabFood payment
    if (isGrab && currentMethod === 'cash') {
        selectPaymentMethod('grabfood');
    } else if (!isGrab && currentMethod === 'grabfood') {
        selectPaymentMethod('cash');
    }

    // Re-price cart items dynamically based on grab_price vs regular_price
    if (cart.length) {
        cart.forEach(item => {
            item.price = isGrab ? (item.grab_price ?? item.regular_price) : item.regular_price;
            item.unit_price = item.price + (item.addon_total || 0);
        });
        renderCart();
    }
}

document.addEventListener('click', function (event) {
    const button = event.target.closest('.menu-price-btn');
    if (!button) return;

    const productName = button.dataset.productName;
    const sizeLabel = button.dataset.sizeLabel;
    if (!productName || !sizeLabel) return;

    quickAddMenuItem(productName, sizeLabel);
});

// ── Category filter & Search ───────────────────────────────────────────────
function filterCategory(id) {
    document.querySelectorAll('.cat-btn').forEach(b => {
        b.className = 'cat-btn px-3.5 py-2 rounded-xl text-xs font-medium whitespace-nowrap transition-all text-gray-600 hover:bg-gray-100 hover:text-gray-900';
    });
    const active = document.getElementById('cat-' + id);
    if (active) active.className = 'cat-btn active px-3.5 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all bg-heim-600 text-white shadow-xs';

    applyFilters();
}

function searchProducts(val) {
    applyFilters();
}

function clearProductFilters() {
    const searchInput = document.getElementById('pos-search');
    if (searchInput) searchInput.value = '';
    filterCategory('all');
}

function applyFilters() {
    const activeBtn = document.querySelector('.cat-btn.active');
    const catId = activeBtn ? activeBtn.id.replace('cat-', '') : 'all';
    const searchInput = document.getElementById('pos-search');
    const q = searchInput ? searchInput.value.toLowerCase().trim() : '';

    let visibleCount = 0;
    document.querySelectorAll('.product-card').forEach(card => {
        const matchesCat = (catId === 'all' || card.dataset.category == catId);
        const name = (card.dataset.productName || '').toLowerCase();
        const matchesQuery = !q || name.includes(q);
        card.style.display = (matchesCat && matchesQuery) ? '' : 'none';
        if (matchesCat && matchesQuery) visibleCount++;
    });
    document.getElementById('product-grid-empty')?.classList.toggle('hidden', visibleCount > 0);
}

// ── Product/size selection ───────────────────────────────────────────────────
function getRecipeSummary(recipe) {
    const ingredients = (recipe && recipe.recipe_ingredients ? recipe.recipe_ingredients : [])
        .map(item => item.ingredient && item.ingredient.name ? item.ingredient.name : null)
        .filter(Boolean);

    return ingredients.slice(0, 3);
}

function handleProductClick(card) {
    const id = parseInt(card.dataset.productId || card.getAttribute('data-product-id') || '0', 10);
    const name = card.dataset.productName || card.getAttribute('data-product-name') || '';
    const categoryName = card.dataset.categoryName || card.getAttribute('data-category-name') || '';
    let sizes = [];
    try {
        sizes = JSON.parse(card.getAttribute('data-sizes') || card.dataset.sizes || '[]');
    } catch (e) {
        console.error('Failed to parse sizes:', e);
    }
    selectProduct(id, name, sizes, categoryName);
}

function getSelectedAddonIds() {
    return Array.from(document.querySelectorAll('input[name="pos_addon_id"]:checked')).map(input => Number(input.value));
}

function renderAddonPicker(categoryName = '') {
    if (!posAddons || posAddons.length === 0) {
        return '<div class="text-xs text-gray-400 mt-3">No add-ons available.</div>';
    }

    const catLower = (categoryName || '').toLowerCase();
    const isFood = catLower.includes('wing') || catLower.includes('fryer') || catLower.includes('burger') || catLower.includes('quesadilla') || catLower.includes('buldak');
    const isDrink = catLower.includes('espresso') || catLower.includes('cold brew') || catLower.includes('matcha') || catLower.includes('non-coffee') || catLower.includes('refresher') || catLower.includes('drink');

    // Grouping
    const wingFlavors = posAddons.filter(a => a.name.startsWith('Flavor:'));
    const cheeseDrips = posAddons.filter(a => a.name.includes('Cheese Drip') || a.name.includes('Double Cheese') || a.name.includes('Cheesy Buffalo'));
    const sides = posAddons.filter(a => a.name.toLowerCase().includes('rice'));
    const beverageOptions = posAddons.filter(a => !a.name.startsWith('Flavor:') && !cheeseDrips.includes(a) && !sides.includes(a));

    const renderGroup = (title, items, isSingle = false) => {
        if (!items || items.length === 0) return '';
        return `
            <div class="mb-3">
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-gray-500 mb-2">${title}</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    ${items.map(addon => `
                        <label class="flex items-center justify-between gap-3 p-3.5 border-2 border-slate-200 rounded-xl bg-white hover:border-heim-400 hover:bg-heim-50/50 transition-all min-h-[3rem] ${addon.available ? 'cursor-pointer' : 'cursor-not-allowed opacity-50'}">
                            <span class="flex items-center gap-2.5 min-w-0">
                                <input type="${isSingle ? 'radio' : 'checkbox'}" name="pos_addon_id" value="${addon.id}" ${addon.available ? '' : 'disabled'} class="h-5 w-5 rounded border-slate-300 text-heim-600 focus:ring-2 focus:ring-heim-500">
                                <span class="font-semibold text-slate-800 text-sm truncate">${escapeHtml(addon.name.replace('Flavor: ', ''))}</span>
                            </span>
                            <span class="text-right">
                                <span class="block font-black text-heim-700 bg-heim-50 px-2 py-0.5 rounded-lg border border-heim-200 whitespace-nowrap text-xs">
                                    ${Number(addon.price) === 0 ? 'FREE' : '+₱' + Number(addon.price).toFixed(2)}
                                </span>
                                ${addon.available ? '' : '<span class="mt-1 block text-[10px] font-extrabold text-rose-700">Out of stock</span>'}
                            </span>
                        </label>
                    `).join('')}
                </div>
            </div>
        `;
    };

    let html = '<div class="mt-4 pt-4 border-t border-gray-100 space-y-3">';
    if (isFood) {
        if (wingFlavors.length) html += renderGroup('Chicken Wing Flavors', wingFlavors, true);
        if (cheeseDrips.length) html += renderGroup('Creamy Cheese Drips', cheeseDrips);
        if (sides.length) html += renderGroup('Sides & Extras', sides);
        if (beverageOptions.length) {
            html += `<details class="group mt-2">
                <summary class="text-xs text-gray-500 cursor-pointer font-medium hover:text-heim-700 py-1">Beverage Customizations (optional) ▼</summary>
                ${renderGroup('Beverage Options', beverageOptions)}
            </details>`;
        }
    } else if (isDrink) {
        if (beverageOptions.length) html += renderGroup('Beverage Customizations', beverageOptions);
        if (sides.length) html += renderGroup('Sides & Extras', sides);
        if (wingFlavors.length || cheeseDrips.length) {
            html += `<details class="group mt-2">
                <summary class="text-xs text-gray-500 cursor-pointer font-medium hover:text-heim-700 py-1">Food Add-ons & Flavors ▼</summary>
                ${renderGroup('Wing Flavors', wingFlavors, true)}
                ${renderGroup('Cheese Drips', cheeseDrips)}
            </details>`;
        }
    } else {
        if (wingFlavors.length) html += renderGroup('Wing Flavors', wingFlavors, true);
        if (cheeseDrips.length) html += renderGroup('Cheese Drips', cheeseDrips);
        if (beverageOptions.length) html += renderGroup('Beverage Customizations', beverageOptions);
        if (sides.length) html += renderGroup('Sides & Extras', sides);
    }
    html += '</div>';
    return html;
}

let activeSizeIndex = 0;

function selectProduct(id, name, sizes, categoryName = '') {
    if (!sizes || sizes.length === 0) return;
    currentProduct = { id, name, categoryName };
    currentProductSizes = sizes;
    activeSizeIndex = Math.max(0, sizes.findIndex(size => size.available !== false));
    if (sizes[activeSizeIndex]?.available === false) {
        showPosFeedback('This product is unavailable because one or more ingredients are out of stock.', 'error');
        return;
    }

    document.getElementById('size-modal-title').textContent = name;
    renderModalForm();
    document.getElementById('size-modal').style.display = 'flex';
}

function renderModalForm() {
    const container = document.getElementById('size-options');
    const sizes = currentProductSizes;
    const isSingle = sizes.length === 1;
    const isGrab = currentOrderType === 'grab';

    let sizesHtml = '';
    if (isSingle) {
        const s = sizes[0];
        const displayPrice = isGrab ? (s.grab_price ?? s.price) : s.price;
        sizesHtml = `
            <div class="rounded-xl border ${isGrab ? 'border-emerald-300 bg-emerald-50/50' : 'border-heim-200 bg-heim-50/50'} p-3 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Size</p>
                    <p class="text-sm font-bold text-gray-900">${escapeHtml(s.size_name)}</p>
                </div>
                <div class="text-right">
                    <span class="text-base font-extrabold ${isGrab ? 'text-emerald-700' : 'text-heim-700'} bg-white px-3 py-1 rounded-xl border ${isGrab ? 'border-emerald-200' : 'border-heim-200'} shadow-xs">
                        ₱${parseFloat(displayPrice).toFixed(2)}
                    </span>
                    ${isGrab ? '<div class="text-[10px] text-emerald-700 font-bold mt-1">Grab Pricing Active</div>' : ''}
                </div>
            </div>
        `;
    } else {
        sizesHtml = `
            <div>
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-gray-500">Select Size</p>
                    ${isGrab ? '<span class="text-[10px] font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-full border border-emerald-200">Grab Pricing Active</span>' : ''}
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    ${sizes.map((s, idx) => {
                        const displayPrice = isGrab ? (s.grab_price ?? s.price) : s.price;
                        return `
                        <button type="button" onclick="setActiveSize(${idx})" ${s.available === false ? 'disabled' : ''}
                            aria-pressed="${idx === activeSizeIndex ? 'true' : 'false'}"
                            class="size-choice-btn flex items-center justify-between p-3.5 rounded-xl border-2 transition-all min-h-[3.5rem] disabled:cursor-not-allowed disabled:opacity-50 ${idx === activeSizeIndex ? (isGrab ? 'border-emerald-600 bg-emerald-50 text-emerald-900 shadow-sm ring-2 ring-emerald-200' : 'border-heim-600 bg-heim-50 text-heim-900 shadow-sm ring-2 ring-heim-200') : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50 text-slate-700'}">
                            <span class="text-sm font-bold truncate">${escapeHtml(s.size_name)}</span>
                            <span class="text-right">
                                <span class="block text-sm font-black ${isGrab ? 'text-emerald-700' : 'text-heim-700'} whitespace-nowrap font-mono">₱${parseFloat(displayPrice).toFixed(2)}</span>
                                <span class="block text-[10px] font-bold ${s.available === false ? 'text-rose-700' : (s.low_stock ? 'text-amber-700' : 'text-emerald-700')}">${s.available === false ? 'Unavailable' : (s.low_stock ? 'Low stock' : 'Available')}</span>
                            </span>
                        </button>
                    `;}).join('')}
                </div>
            </div>
        `;
    }

    container.innerHTML = `
        <div class="space-y-3">
            ${sizesHtml}
            ${renderAddonPicker(currentProduct.categoryName)}
            <div class="mt-3 pt-3 border-t border-gray-100">
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-gray-500 mb-1.5">Special Instructions (Optional)</p>
                <input id="modal-item-comment" type="text" placeholder="e.g. Less ice, no sugar, extra hot..."
                    class="w-full text-xs px-3 py-2 rounded-xl border border-gray-200 bg-gray-50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-heim-500">
                <div class="flex flex-wrap gap-1.5 mt-2">
                    <button type="button" onclick="appendModalComment('Less ice')" class="text-[10px] font-semibold bg-gray-100 hover:bg-heim-100 text-gray-700 hover:text-heim-800 px-2 py-0.5 rounded-lg transition-colors">+ Less ice</button>
                    <button type="button" onclick="appendModalComment('No sugar')" class="text-[10px] font-semibold bg-gray-100 hover:bg-heim-100 text-gray-700 hover:text-heim-800 px-2 py-0.5 rounded-lg transition-colors">+ No sugar</button>
                    <button type="button" onclick="appendModalComment('Extra hot')" class="text-[10px] font-semibold bg-gray-100 hover:bg-heim-100 text-gray-700 hover:text-heim-800 px-2 py-0.5 rounded-lg transition-colors">+ Extra hot</button>
                    <button type="button" onclick="appendModalComment('No whipped cream')" class="text-[10px] font-semibold bg-gray-100 hover:bg-heim-100 text-gray-700 hover:text-heim-800 px-2 py-0.5 rounded-lg transition-colors">+ No whipped cream</button>
                </div>
            </div>
        </div>
    `;
}

function appendModalComment(text) {
    const input = document.getElementById('modal-item-comment');
    if (!input) return;
    if (input.value.trim()) {
        if (!input.value.includes(text)) {
            input.value += ', ' + text;
        }
    } else {
        input.value = text;
    }
}

function setActiveSize(idx) {
    activeSizeIndex = idx;
    renderModalForm();
}

function confirmAddToCart() {
    if (!currentProduct || !currentProductSizes[activeSizeIndex]) return;
    const s = currentProductSizes[activeSizeIndex];
    if (s.available === false) {
        showPosFeedback('This size is unavailable because one or more ingredients are out of stock.', 'error');
        return;
    }
    const selectedAddonIds = getSelectedAddonIds();
    const addons = posAddons.filter(addon => selectedAddonIds.includes(addon.id));
    const commentInput = document.getElementById('modal-item-comment');
    const comment = commentInput ? commentInput.value.trim() : '';
    addToCart(currentProduct.id, currentProduct.name, s.id, s.size_name, s.price, s.recipe || null, addons, comment, s.grab_price);
    closeSizeModal();
}

function closeSizeModal() {
    document.getElementById('size-modal').style.display = 'none';
}

function quickAddMenuItem(productName, sizeLabel) {
    const cards = document.querySelectorAll('.product-card');
    const card = Array.from(cards).find(el => {
        const name = (el.dataset.productName || '').trim();
        return name.toLowerCase() === productName.toLowerCase();
    });

    if (!card) return;

    let sizes = [];
    try {
        sizes = JSON.parse(card.getAttribute('data-sizes') || card.dataset.sizes || '[]');
    } catch (e) {
        console.error('Failed to parse sizes:', e);
        return;
    }

    const match = sizes.find(size => {
        const label = (size.size_name || '').toLowerCase();
        return label.includes(sizeLabel.toLowerCase().replace(/\s+/g, ' '));
    }) || sizes.find(size => {
        const label = (size.size_name || '').toLowerCase();
        return label.includes(sizeLabel.toLowerCase().split(' ')[0]);
    });

    if (!match) return;

    addToCart(parseInt(card.dataset.productId || card.getAttribute('data-product-id') || '0', 10) || Number(card.dataset.productId || 0), productName, match.id, match.size_name, match.price, match.recipe || null, [], '', match.grab_price);
}

// ── Cart management ──────────────────────────────────────────────────────────
function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function buildCartItemKey(productId, sizeId, addonIds, comment, assignedTo) {
    const signature = `${productId}|${sizeId}|${addonIds.slice().sort((a, b) => a - b).join(',')}|${comment}|${assignedTo}`;
    return encodeURIComponent(signature).replace(/[!'()*]/g, char => `%${char.charCodeAt(0).toString(16)}`);
}

function refreshCartItemKey(item) {
    const updatedKey = buildCartItemKey(
        item.product_id,
        item.product_size_id,
        (item.addons || []).map(addon => Number(addon.id)),
        item.comment || '',
        item.assigned_to || ''
    );
    if (updatedKey === item.key) return true;

    const duplicate = cart.find(candidate => candidate !== item && candidate.key === updatedKey);
    if (duplicate) {
        const combinedQuantity = (parseInt(duplicate.qty, 10) || 0) + (parseInt(item.qty, 10) || 0);
        if (combinedQuantity > 999) {
            showPosFeedback('Matching order items cannot be combined above 999 units. Reduce a quantity before merging.', 'error');
            return false;
        }
        duplicate.qty = combinedQuantity;
        cart = cart.filter(candidate => candidate !== item);
        return true;
    }

    item.key = updatedKey;
    return true;
}

function addToCart(productId, productName, sizeId, sizeName, price, recipe = null, addons = [], comment = '', grabPrice = null) {
    if (splitEnabled && !activeSplitPerson) {
        showPosFeedback('Add or select a person before adding split-order items.', 'error');
        return;
    }

    const safeComment = (comment || '').trim();
    const assignedTo = splitEnabled ? activeSplitPerson : '';
    const key = buildCartItemKey(productId, sizeId, addons.map(addon => Number(addon.id)), safeComment, assignedTo);
    const existing = cart.find(i => i.key === key);
    if (existing && existing.qty >= 999) {
        showPosFeedback('An order item cannot exceed 999 units.', 'error');
        return;
    }
    const normalizedAddons = addons.map(addon => ({
        id: Number(addon.id),
        name: addon.name,
        price: Number(addon.price || 0),
    }));

    const regPrice = parseFloat(price);
    const gPrice = (grabPrice !== null && grabPrice !== undefined) ? parseFloat(grabPrice) : regPrice;
    const isGrab = currentOrderType === 'grab';
    const activePrice = isGrab ? gPrice : regPrice;

    if (existing) {
        existing.qty++;
    } else {
        const addonTotal = normalizedAddons.reduce((sum, addon) => sum + addon.price, 0);
        cart.push({
            key,
            product_id: productId,
            product_size_id: sizeId,
            name: productName,
            size: sizeName,
            regular_price: regPrice,
            grab_price: gPrice,
            price: activePrice,
            addons: normalizedAddons,
            addon_total: addonTotal,
            unit_price: activePrice + addonTotal,
            qty: 1,
            comment: safeComment,
            assigned_to: assignedTo,
            expanded: false,
            recipe: recipe && recipe.recipe_ingredients ? recipe.recipe_ingredients : []
        });
    }
    renderCart();
    revealCartItem(key);
}

function changeQty(key, delta) {
    const item = cart.find(i => i.key === key);
    if (!item) return;
    const current = parseInt(item.qty, 10) || 1;
    const next = current + delta;
    if (next <= 0) {
        removeItem(key);
        return;
    }
    if (next > 999) {
        showPosFeedback('An order item cannot exceed 999 units.', 'error');
        return;
    }
    item.qty = next;
    renderCart();
}

function setItemQty(key, value) {
    const item = cart.find(i => i.key === key);
    if (!item) return;
    const parsed = Number(value);
    if (String(value).trim() === '' || !Number.isInteger(parsed)) {
        showPosFeedback('Quantity must be a whole number between 1 and 999.', 'error');
        renderCart();
        return;
    }
    if (parsed <= 0) {
        removeItem(key);
        return;
    }
    if (parsed > 999) {
        showPosFeedback('An order item cannot exceed 999 units.', 'error');
        renderCart();
        return;
    }
    item.qty = parsed;
    renderCart();
}

function setItemComment(key, comment) {
    const item = cart.find(i => i.key === key);
    if (!item) return;
    const previousComment = item.comment;
    item.comment = (comment || '').trim();
    if (!refreshCartItemKey(item)) item.comment = previousComment;
    renderCart();
}

function setItemAssignee(key, name) {
    const item = cart.find(i => i.key === key);
    if (!item) return;
    const previousAssignee = item.assigned_to;
    item.assigned_to = (name || '').trim();
    if (!refreshCartItemKey(item)) {
        item.assigned_to = previousAssignee;
        renderCart();
        return;
    }
    if (item.assigned_to && !splitPeople.includes(item.assigned_to)) splitPeople.push(item.assigned_to);
    renderSplitPeople();
    renderCart();
}

function toggleItemDetails(key) {
    const item = cart.find(candidate => candidate.key === key);
    if (!item) return;
    document.getElementById(`cart-item-menu-${key}`)?.removeAttribute('open');

    item.expanded = !item.expanded;
    const details = document.getElementById(`cart-item-details-${key}`);
    const button = document.getElementById(`cart-item-toggle-${key}`);
    if (!details || !button) {
        renderCart();
        return;
    }

    details.classList.toggle('hidden', !item.expanded);
    button.setAttribute('aria-expanded', item.expanded ? 'true' : 'false');
    button.querySelector('[data-item-toggle-label]').textContent = item.expanded ? 'Hide details' : 'Edit item';
    button.querySelector('svg').classList.toggle('rotate-180', item.expanded);
}

function focusItemComment(key) {
    const item = cart.find(candidate => candidate.key === key);
    if (!item) return;
    document.getElementById(`cart-item-menu-${key}`)?.removeAttribute('open');
    if (!item.expanded) toggleItemDetails(key);
    requestAnimationFrame(() => document.getElementById(`cart-item-comment-${key}`)?.focus());
}

function focusItemQuantity(key) {
    document.getElementById(`cart-item-menu-${key}`)?.removeAttribute('open');
    document.getElementById(`cart-item-quantity-${key}`)?.focus();
}

function focusItemAssignee(key) {
    document.getElementById(`cart-item-menu-${key}`)?.removeAttribute('open');
    if (!splitEnabled) {
        const customer = document.getElementById('order-customer-name')?.value.trim();
        if (!splitPeople.length && customer) splitPeople.push(customer);
        toggleSplitAssignments(true);
    }
    const item = cart.find(candidate => candidate.key === key);
    if (item && !item.expanded) toggleItemDetails(key);
    requestAnimationFrame(() => document.getElementById(`cart-item-assignee-${key}`)?.focus());
}

function toggleSplitAssignments(enabled) {
    splitEnabled = enabled;
    const panel = document.getElementById('split-people-panel');
    if (panel) panel.classList.toggle('hidden', !enabled);
    if (enabled && !splitPeople.length) {
        const assigned = cart.map(item => item.assigned_to).filter(Boolean);
        splitPeople = [...new Set(assigned)];
        if (!splitPeople.length) {
            const customer = document.getElementById('order-customer-name')?.value.trim();
            if (customer) splitPeople.push(customer);
        }
        activeSplitPerson = splitPeople[0] || '';
    }
    renderSplitPeople();
    renderCart();
}

function addSplitPerson() {
    const input = document.getElementById('new-split-person');
    const name = input?.value.trim();
    if (!name) {
        input?.focus();
        return;
    }
    if (!splitPeople.some(person => person.toLowerCase() === name.toLowerCase())) {
        splitPeople.push(name);
    }
    activeSplitPerson = splitPeople.find(person => person.toLowerCase() === name.toLowerCase()) || name;
    if (input) input.value = '';
    renderSplitPeople();
}

function selectSplitPerson(index) {
    activeSplitPerson = splitPeople[index] || '';
    renderSplitPeople();
}

function renderSplitPeople() {
    const container = document.getElementById('split-people');
    if (!container) return;
    container.innerHTML = splitPeople.map((person, index) => `
        <button type="button" onclick="selectSplitPerson(${index})" aria-pressed="${person === activeSplitPerson ? 'true' : 'false'}"
            class="rounded-xl border px-3 py-2 text-xs font-semibold transition-colors ${person === activeSplitPerson ? 'border-indigo-500 bg-indigo-100 text-indigo-900' : 'border-indigo-100 bg-white text-gray-700 hover:border-indigo-300'}">
            <span class="mr-1 inline-flex h-4 w-4 items-center justify-center rounded border ${person === activeSplitPerson ? 'border-indigo-700 bg-indigo-700 text-white' : 'border-slate-300 bg-white'}">${person === activeSplitPerson ? '✓' : ''}</span>
            Person ${index + 1} — ${escapeHtml(person)}
        </button>
    `).join('');
    const assigneeSelect = document.getElementById('split-assignee-select');
    if (assigneeSelect) {
        const selectedPerson = activeSplitPerson;
        assigneeSelect.replaceChildren(new Option('Choose a person', ''));
        splitPeople.forEach(person => assigneeSelect.add(new Option(person, person)));
        assigneeSelect.value = splitPeople.includes(selectedPerson) ? selectedPerson : '';
    }
}

function selectSplitPersonByValue(person) {
    activeSplitPerson = splitPeople.includes(person) ? person : '';
    renderSplitPeople();
}

function appendItemComment(key, preset) {
    const item = cart.find(i => i.key === key);
    if (!item) return;
    const previousComment = item.comment;
    if (item.comment) {
        if (!item.comment.includes(preset)) {
            item.comment += ', ' + preset;
        }
    } else {
        item.comment = preset;
    }
    if (!refreshCartItemKey(item)) item.comment = previousComment;
    renderCart();
}

function clearItemComment(key) {
    const item = cart.find(i => i.key === key);
    if (!item) return;
    const previousComment = item.comment;
    item.comment = '';
    if (!refreshCartItemKey(item)) item.comment = previousComment;
    renderCart();
}

function removeItem(key) { cart = cart.filter(i => i.key !== key); renderCart(); }

function revealCartItem(key) {
    requestAnimationFrame(() => {
        const item = Array.from(document.querySelectorAll('#cart-list [data-cart-key]'))
            .find(element => element.dataset.cartKey === key);
        item?.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
    });
}

function clearCart() {
    cart = [];
    currentHeldOrderId = null;
    currentHeldOrderNumber = null;
    setOrderType('dine_in');
    splitEnabled = false;
    splitPeople = [];
    activeSplitPerson = '';
    const splitToggle = document.getElementById('split-toggle');
    if (splitToggle) splitToggle.checked = false;
    const splitPanel = document.getElementById('split-people-panel');
    if (splitPanel) splitPanel.classList.add('hidden');
    renderSplitPeople();
    ['grab-order-code', 'grab-rider-code', 'order-customer-name'].forEach(id => {
        const input = document.getElementById(id);
        if (input) input.value = '';
    });
    const discountType = document.getElementById('discount-type');
    const discountInput = document.getElementById('discount');
    const discountId = document.getElementById('discount-id-number');
    if (discountType) discountType.value = 'none';
    if (discountInput) discountInput.value = '0';
    if (discountId) discountId.value = '';
    onDiscountTypeChange();
    updateOrderSummary();
    renderCart();
}

function renderCart() {
    const emptyEl = document.getElementById('empty-cart');
    const listEl  = document.getElementById('cart-list');
    const btn     = document.getElementById('checkout-btn');
    const holdBtn = document.getElementById('hold-btn');
    const badge   = document.getElementById('cart-badge');
    const totalCount = cart.reduce((sum, i) => sum + (parseInt(i.qty, 10) || 0), 0);
    if (badge) badge.textContent = totalCount;
    if (holdBtn) holdBtn.disabled = cart.length === 0;
    updateMobileOrderShortcut();

    if (cart.length === 0) {
        if (emptyEl) emptyEl.style.display = 'flex';
        if (listEl) listEl.innerHTML = '';
        if (btn) btn.disabled = true;
        updateTotals();
        return;
    }

    if (emptyEl) emptyEl.style.display = 'none';
    const recipeSummary = cart.map(item => {
        const names = (item.recipe || []).map(r => r.ingredient && r.ingredient.name ? r.ingredient.name : '').filter(Boolean);
        return names.length ? `Ingredients: ${names.join(', ')}` : '';
    }).filter(Boolean).join(' | ');

    if (listEl) {
        listEl.innerHTML = cart.map(item => {
            const isGrabOrder = currentOrderType === 'grab';
            const accentColor = isGrabOrder ? 'border-l-emerald-500' : 'border-l-heim-600';

            const addonChips = item.addons.length
                ? `<div class="flex flex-wrap gap-1.5 mt-2" role="list" aria-label="Add-ons">${item.addons.map(addon => `<span role="listitem" class="inline-flex items-center gap-1 bg-amber-50 text-amber-900 border border-amber-200/90 font-bold px-2.5 py-1 rounded-lg text-xs"><svg class="w-2.5 h-2.5 text-amber-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>${escapeHtml(addon.name)} <span class="text-amber-700 font-black">+₱${Number(addon.price).toFixed(2)}</span></span>`).join('')}</div>`
                : '<p class="text-xs text-slate-400 mt-1 font-medium italic">No add-ons</p>';

            return `
                <div role="listitem" class="bg-white border border-slate-200 hover:border-heim-300 rounded-xl p-0 shadow-sm transition-colors relative overflow-hidden border-l-4 ${accentColor}" data-cart-key="${item.key}" title="${recipeSummary && item.recipe && item.recipe.length ? recipeSummary : ''}">
                    <div class="p-3 sm:p-3.5">
                        {{-- Item header: name, size badge, total price, remove button --}}
                        <div class="flex items-start gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start gap-2 flex-wrap">
                                    <p class="text-sm sm:text-base font-bold text-slate-900 leading-tight">${escapeHtml(item.name)}</p>
                                    ${isGrabOrder ? '<span class="text-[10px] font-bold text-emerald-800 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded-md uppercase tracking-wide">GRAB</span>' : ''}
                                </div>
                                <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-md text-[11px] font-semibold bg-heim-50 text-heim-800 border border-heim-200">${escapeHtml(item.size)}</span>
                                <p class="mt-1 text-xs font-semibold text-slate-600">₱${item.unit_price.toFixed(2)} × ${item.qty}</p>
                                ${item.addons.length ? `<span class="ml-1 text-[11px] text-slate-500">${item.addons.length} add-on${item.addons.length === 1 ? '' : 's'}</span>` : ''}
                                ${splitEnabled
                                    ? `<p class="mt-1 text-xs font-medium ${item.assigned_to ? 'text-heim-800' : 'text-amber-700'}">${item.assigned_to ? `Assigned to ${escapeHtml(item.assigned_to)}` : 'Unassigned · edit item to assign'}</p>`
                                    : (item.assigned_to ? `<p class="mt-1 text-xs font-medium text-heim-800">Assigned to ${escapeHtml(item.assigned_to)}</p>` : '')}
                                ${item.comment
                                    ? `<p class="mt-1 text-xs text-amber-800"><span class="font-semibold">📝</span> ${escapeHtml(item.comment)}</p>`
                                    : `<button type="button" onclick="focusItemComment('${item.key}')" class="mt-1 text-xs font-semibold text-heim-700 hover:text-heim-900">+ Add comment</button>`}
                            </div>
                            <div class="flex flex-col items-end gap-2 shrink-0">
                                <p class="text-base font-bold text-slate-900 whitespace-nowrap font-mono">₱${(item.unit_price * item.qty).toFixed(2)}</p>
                                <details id="cart-item-menu-${item.key}" class="relative">
                                    <summary aria-label="More actions for ${escapeHtml(item.name)}" class="flex h-9 cursor-pointer list-none items-center rounded-lg border border-slate-200 bg-white px-2 text-xs font-bold text-slate-600 hover:bg-slate-50">More ⋮</summary>
                                    <div class="absolute right-0 z-30 mt-1 min-w-36 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg">
                                        <button type="button" onclick="toggleItemDetails('${item.key}')" class="block w-full rounded-lg px-3 py-2 text-left text-xs font-semibold text-slate-700 hover:bg-slate-50">Edit item</button>
                                        <button type="button" onclick="focusItemComment('${item.key}')" class="block w-full rounded-lg px-3 py-2 text-left text-xs font-semibold text-slate-700 hover:bg-slate-50">Add comment</button>
                                        <button type="button" onclick="focusItemQuantity('${item.key}')" class="block w-full rounded-lg px-3 py-2 text-left text-xs font-semibold text-slate-700 hover:bg-slate-50">Change quantity</button>
                                        <button type="button" onclick="focusItemAssignee('${item.key}')" class="block w-full rounded-lg px-3 py-2 text-left text-xs font-semibold text-slate-700 hover:bg-slate-50">Move to person</button>
                                        <button type="button" onclick="removeItem('${item.key}')" class="block w-full rounded-lg px-3 py-2 text-left text-xs font-semibold text-rose-700 hover:bg-rose-50">Remove item</button>
                                    </div>
                                </details>
                            </div>
                        </div>

                        {{-- Quantity controls + unit price --}}
                        <div class="flex items-center justify-between mt-2.5 gap-2">
                            <div class="flex items-center gap-0 bg-white border border-slate-200 rounded-lg overflow-hidden" role="group" aria-label="Quantity for ${escapeHtml(item.name)}">
                                <button type="button" onclick="changeQty('${item.key}', -1)"
                                    aria-label="Decrease quantity of ${escapeHtml(item.name)}"
                                    class="w-9 h-9 flex items-center justify-center text-slate-600 hover:bg-rose-50 hover:text-rose-700 text-lg font-semibold transition-colors border-r border-slate-200">
                                    −
                                </button>
                                <input id="cart-item-quantity-${item.key}" type="number" min="1" max="999" step="1" value="${item.qty}"
                                    onchange="setItemQty('${item.key}', this.value)"
                                    onkeydown="if(event.key==='Enter'){this.blur();}"
                                    aria-label="Quantity of ${escapeHtml(item.name)}"
                                    class="w-10 text-center text-sm font-bold text-slate-900 bg-white border-0 py-0 h-9 focus:ring-2 focus:ring-heim-500 focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                <button type="button" onclick="changeQty('${item.key}', 1)"
                                    aria-label="Increase quantity of ${escapeHtml(item.name)}"
                                    class="w-9 h-9 flex items-center justify-center text-slate-600 hover:bg-heim-50 hover:text-heim-700 text-lg font-semibold transition-colors border-l border-slate-200">
                                    +
                                </button>
                            </div>
                            <div class="flex items-center gap-2">
                                <p class="hidden text-xs text-slate-500 font-mono sm:block">₱${item.unit_price.toFixed(2)} each</p>
                                <button type="button" id="cart-item-toggle-${item.key}" onclick="toggleItemDetails('${item.key}')"
                                    aria-expanded="${item.expanded ? 'true' : 'false'}" aria-controls="cart-item-details-${item.key}"
                                    class="inline-flex min-h-9 items-center gap-1 rounded-lg border border-heim-200 bg-heim-50 px-2.5 text-xs font-semibold text-heim-800 transition-colors hover:bg-heim-100">
                                    <span data-item-toggle-label>${item.expanded ? 'Hide details' : 'Edit item'}</span>
                                    <svg class="h-3.5 w-3.5 transition-transform ${item.expanded ? 'rotate-180' : ''}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg>
                                </button>
                            </div>
                        </div>

                        {{-- Item special instruction / comment --}}
                        <div id="cart-item-details-${item.key}" class="${item.expanded ? '' : 'hidden'} mt-3.5 pt-3 border-t border-slate-100">
                            ${addonChips}
                            <div class="flex items-center justify-between text-xs mb-2">
                                <span class="font-black text-slate-700 flex items-center gap-1.5 uppercase tracking-wider">
                                    <svg class="w-3.5 h-3.5 text-heim-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                    Note
                                </span>
                                ${item.comment ? `<button type="button" onclick="clearItemComment('${item.key}')" class="text-xs text-rose-500 hover:text-rose-700 font-bold px-2 py-0.5 rounded-lg hover:bg-rose-50 transition-colors">✕ Clear</button>` : ''}
                            </div>
                            <input id="cart-item-comment-${item.key}" type="text" value="${escapeHtml(item.comment || '')}"
                                onchange="setItemComment('${item.key}', this.value)"
                                aria-label="Special instruction for ${escapeHtml(item.name)}"
                                placeholder="e.g. Less ice, no sugar, extra hot..."
                                class="w-full text-sm px-3.5 py-2.5 rounded-xl border-2 border-slate-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-heim-500 focus:border-heim-400 text-slate-800 placeholder-slate-400 font-medium transition-colors">
                            ${splitEnabled ? `
                                <label class="block text-xs font-semibold text-slate-700 mt-2.5 mb-1">Assigned person</label>
                                <select id="cart-item-assignee-${item.key}" onchange="setItemAssignee('${item.key}', this.value)"
                                    aria-label="Assign ${escapeHtml(item.name)} to a person"
                                    class="w-full text-sm px-3.5 py-2.5 rounded-xl border-2 border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-heim-500 focus:border-heim-400 text-slate-800 font-semibold transition-colors">
                                    <option value="">Not assigned</option>
                                    ${[...new Set([...splitPeople, ...(item.assigned_to ? [item.assigned_to] : [])])].map(person => `<option value="${escapeHtml(person)}" ${person === item.assigned_to ? 'selected' : ''}>${escapeHtml(person)}</option>`).join('')}
                                </select>
                            ` : (item.assigned_to ? `<p class="mt-2 text-xs font-black text-indigo-700 flex items-center gap-1"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>For: ${escapeHtml(item.assigned_to)}</p>` : '')}
                            <div class="flex flex-wrap gap-1.5 mt-2.5">
                                <button type="button" onclick="appendItemComment('${item.key}', 'Less ice')" class="text-xs font-bold bg-white border-2 border-slate-200 hover:border-heim-300 hover:bg-heim-50 hover:text-heim-800 px-3 py-1.5 rounded-xl text-slate-600 transition-colors">🧊 Less ice</button>
                                <button type="button" onclick="appendItemComment('${item.key}', 'No sugar')" class="text-xs font-bold bg-white border-2 border-slate-200 hover:border-heim-300 hover:bg-heim-50 hover:text-heim-800 px-3 py-1.5 rounded-xl text-slate-600 transition-colors">🚫 No sugar</button>
                                <button type="button" onclick="appendItemComment('${item.key}', 'Extra hot')" class="text-xs font-bold bg-white border-2 border-slate-200 hover:border-heim-300 hover:bg-heim-50 hover:text-heim-800 px-3 py-1.5 rounded-xl text-slate-600 transition-colors">🔥 Extra hot</button>
                                <button type="button" onclick="appendItemComment('${item.key}', 'No cream')" class="text-xs font-bold bg-white border-2 border-slate-200 hover:border-heim-300 hover:bg-heim-50 hover:text-heim-800 px-3 py-1.5 rounded-xl text-slate-600 transition-colors">🥛 No cream</button>
                            </div>
                        </div>
                    </div>
                </div>`;
        }).join('');
    }

    if (btn) btn.disabled = false;
    updateTotals();
}

function updateTotals() {
    const subtotal = cart.reduce((s, i) => s + (Number(i.unit_price || i.price) * (parseInt(i.qty, 10) || 0)), 0);
    const discountType = document.getElementById('discount-type')?.value || 'none';
    const customDiscountInput = document.getElementById('discount');
    const requestedCustomDiscount = parseFloat(customDiscountInput?.value) || 0;
    const taxRate = taxConfig.isActive ? Math.max(0, Number(taxConfig.rate) || 0) : 0;
    const isStatutory = discountType === 'senior' || discountType === 'pwd';
    const statutoryBase = isStatutory && taxRate > 0 && taxConfig.isInclusive
        ? roundMoney(subtotal / (1 + (taxRate / 100)))
        : subtotal;
    const discount = discountType === 'senior' || discountType === 'pwd'
        ? roundMoney(statutoryBase * 0.20)
        : discountType === 'custom'
            ? roundMoney(Math.min(subtotal, Math.max(0, requestedCustomDiscount)))
            : 0;
    const net = Math.max(0, roundMoney(subtotal - discount));
    let vatableSales = 0;
    let vatExemptSales = 0;
    let taxAmount = 0;

    if (isStatutory) {
        vatExemptSales = Math.max(0, roundMoney(statutoryBase - roundMoney(statutoryBase * 0.20)));
    } else if (taxRate > 0 && taxConfig.isInclusive) {
        vatableSales = roundMoney(net / (1 + (taxRate / 100)));
        taxAmount = roundMoney(net - vatableSales);
    } else if (taxRate > 0) {
        vatableSales = net;
        taxAmount = roundMoney(net * (taxRate / 100));
    } else {
        vatableSales = net;
    }

    orderTotal = isStatutory
        ? vatExemptSales
        : roundMoney(net + (taxConfig.isInclusive ? 0 : taxAmount));
    updateMobileOrderShortcut();
    currentDiscountAmount = discount;
    currentDiscountType = discountType;
    currentDiscountLabel = discountType === 'senior'
        ? 'Senior Citizen (20% Off)'
        : discountType === 'pwd'
            ? 'PWD (20% Off)'
            : discountType === 'custom' && discount > 0
                ? 'Custom Discount'
                : '';

    const formatCurrency = value => '₱' + Number(value || 0).toFixed(2);
    const subtotalEl = document.getElementById('subtotal-display');
    const discountEl = document.getElementById('discount-display');
    const discountLabelEl = document.getElementById('discount-display-label');
    const discountRow = document.getElementById('discount-display-row');
    const totalEl = document.getElementById('total-display');
    const vatableEl = document.getElementById('vatable-display');
    const exemptEl = document.getElementById('exempt-display');
    const exemptRow = document.getElementById('exempt-row');
    const taxEl = document.getElementById('tax-display');
    const taxNameEl = document.getElementById('tax-name-label');

    if (subtotalEl) subtotalEl.textContent = formatCurrency(subtotal);
    if (discountEl) discountEl.textContent = '-' + formatCurrency(discount);
    if (discountLabelEl) discountLabelEl.textContent = currentDiscountLabel || 'Discount';
    if (discountRow) discountRow.classList.toggle('hidden', discount <= 0);
    if (discountRow) discountRow.classList.toggle('flex', discount > 0);
    if (totalEl) totalEl.textContent = formatCurrency(orderTotal);
    const checkoutButtonLabel = document.getElementById('checkout-button-label');
    if (checkoutButtonLabel) checkoutButtonLabel.textContent = `PAY ${formatCurrency(orderTotal)}`;
    const payLaterAmount = document.getElementById('pl-amount-display');
    if (payLaterAmount) payLaterAmount.textContent = formatCurrency(orderTotal);
    if (vatableEl) vatableEl.textContent = formatCurrency(vatableSales);
    if (exemptEl) exemptEl.textContent = formatCurrency(vatExemptSales);
    if (exemptRow) exemptRow.classList.toggle('hidden', !isStatutory || vatExemptSales <= 0);
    if (exemptRow) exemptRow.classList.toggle('flex', isStatutory && vatExemptSales > 0);
    if (taxEl) taxEl.textContent = formatCurrency(taxAmount);
    if (taxNameEl) taxNameEl.textContent = `${taxConfig.name} (${taxRate.toFixed(2)}%)`;
}

function onDiscountTypeChange() {
    const discountType = document.getElementById('discount-type')?.value || 'none';
    const idWrapper = document.getElementById('discount-id-wrapper');
    const idInput = document.getElementById('discount-id-number');
    const customWrapper = document.getElementById('custom-discount-wrapper');
    const badge = document.getElementById('discount-badge');
    const isStatutory = discountType === 'senior' || discountType === 'pwd';
    const isCustom = discountType === 'custom';

    if (idInput && idInput.dataset.discountType !== discountType) {
        idInput.value = '';
        idInput.dataset.discountType = discountType;
    }
    if (idWrapper) idWrapper.classList.toggle('hidden', !isStatutory);
    if (idInput) idInput.required = isStatutory;
    if (customWrapper) customWrapper.classList.toggle('hidden', !isCustom);
    if (badge) {
        badge.textContent = isStatutory ? '20% Off' : 'Custom';
        badge.classList.toggle('hidden', !isStatutory && !isCustom);
    }

    updateTotals();
}

const roundMoney = value => Math.round((Number(value) || 0) * 100) / 100;

// ── Checkout ─────────────────────────────────────────────────────────────────
function checkoutPayerTotals() {
    const subtotal = cart.reduce((sum, item) => sum + (Number(item.unit_price || item.price) * (parseInt(item.qty, 10) || 0)), 0);
    let allocated = 0;
    const totals = new Map();

    cart.forEach((item, index) => {
        const lineSubtotal = Number(item.unit_price || item.price) * (parseInt(item.qty, 10) || 0);
        const lineDue = index === cart.length - 1
            ? roundMoney(orderTotal - allocated)
            : roundMoney((orderTotal * lineSubtotal) / Math.max(subtotal, 0.01));
        allocated = roundMoney(allocated + lineDue);

        const person = (item.assigned_to || '').trim();
        if (person) totals.set(person, roundMoney((totals.get(person) || 0) + lineDue));
    });

    return totals;
}

function updateCheckoutPayers() {
    const payerSelect = document.getElementById('payment-person-name');
    const hint = document.getElementById('payer-assignment-hint');
    if (!payerSelect) return;

    const reviewCount = document.getElementById('checkout-review-count');
    const reviewRows = document.getElementById('checkout-review-rows');
    if (reviewCount) reviewCount.textContent = `· ${cart.length} ${cart.length === 1 ? 'item' : 'items'}`;
    if (reviewRows) {
        reviewRows.innerHTML = cart.map(item => {
            const person = (item.assigned_to || '').trim();
            return `
                <div class="flex items-start justify-between gap-3 px-3 py-2.5 text-xs">
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-800">${escapeHtml(item.name)} <span class="font-normal text-slate-500">· ${escapeHtml(item.size)} × ${item.qty}</span></p>
                        <p class="mt-0.5 text-slate-500">${person ? `Assigned to ${escapeHtml(person)}` : 'Not assigned to a person'}${item.comment ? ` · ${escapeHtml(item.comment)}` : ''}</p>
                        ${item.addons?.length ? `<p class="mt-0.5 text-slate-500">Add-ons: ${item.addons.map(addon => escapeHtml(addon.name)).join(', ')}</p>` : ''}
                    </div>
                    <span class="shrink-0 font-mono font-semibold text-slate-800">₱${(item.unit_price * item.qty).toFixed(2)}</span>
                </div>
            `;
        }).join('');
    }

    const selectedPerson = payerSelect.value;
    const totals = checkoutPayerTotals();
    payerSelect.replaceChildren(new Option(
        totals.size ? 'Full order / select a person' : 'Full order payment',
        ''
    ));

    totals.forEach((amount, person) => {
        const alreadyPaid = stagedCheckoutPayments
            .filter(payment => payment.person_name === person)
            .reduce((sum, payment) => sum + payment.amount_paid, 0);
        const remaining = Math.max(0, Math.round((amount - alreadyPaid) * 100) / 100);
        payerSelect.add(new Option(`${person} · ₱${remaining.toFixed(2)} remaining`, person));
    });
    if ([...payerSelect.options].some(option => option.value === selectedPerson)) {
        payerSelect.value = selectedPerson;
    }

    const summary = document.getElementById('checkout-split-summary');
    const rows = document.getElementById('checkout-split-rows');
    if (summary && rows) {
        summary.classList.toggle('hidden', !totals.size);
        rows.innerHTML = [...totals.entries()].map(([person, amount]) => {
            const paid = stagedCheckoutPayments
                .filter(payment => payment.person_name === person)
                .reduce((sum, payment) => sum + payment.amount_paid, 0);
            const remaining = Math.max(0, Math.round((amount - paid) * 100) / 100);
            const state = remaining <= 0 ? 'PAID' : (paid > 0 ? 'PARTIAL' : 'PENDING');
            const stateClass = remaining <= 0 ? 'text-heim-700' : (paid > 0 ? 'text-blue-700' : 'text-amber-700');
            return `
                <div class="flex items-center justify-between border-b border-gray-100 px-3 py-2 text-xs last:border-0">
                    <span class="font-semibold text-gray-700">${escapeHtml(person)} · ₱${remaining.toFixed(2)} due</span>
                    <span class="font-bold ${stateClass}">${state}</span>
                </div>
            `;
        }).join('');
    }

    if (hint) {
        hint.textContent = totals.size
            ? 'Record payments for each person’s assigned products. A single full-order payment may leave the person blank.'
            : 'No assigned people. This payment applies to the full order.';
    }

    const stagedSummary = document.getElementById('checkout-staged-summary');
    const stagedRows = document.getElementById('checkout-staged-rows');
    const stagedRemaining = document.getElementById('checkout-staged-remaining');
    const stagedTotal = stagedCheckoutPayments.reduce((sum, payment) => sum + payment.amount_paid, 0);
    const remainingTotal = Math.max(0, Math.round((orderTotal - stagedTotal) * 100) / 100);
    if (stagedSummary && stagedRows && stagedRemaining) {
        stagedSummary.classList.toggle('hidden', stagedCheckoutPayments.length === 0);
        stagedRemaining.textContent = `₱${remainingTotal.toFixed(2)} remaining`;
        stagedRows.innerHTML = stagedCheckoutPayments.map((payment, index) => `
            <div class="flex items-center justify-between gap-2 px-3 py-2 text-xs">
                <div class="min-w-0">
                    <p class="truncate font-medium text-gray-700">
                        ${escapeHtml(payment.person_name || 'Full order')} · ${escapeHtml(payment.method_label)}
                        ${payment.reference_number ? `<span class="text-gray-400">(${escapeHtml(payment.reference_number)})</span>` : ''}
                    </p>
                    ${payment.method === 'cash'
                        ? `<p class="mt-0.5 text-[10px] text-gray-500">Received ₱${payment.amount_received.toFixed(2)} · Change ₱${Math.max(0, payment.amount_received - payment.amount_paid).toFixed(2)}</p>`
                        : ''}
                </div>
                <span class="shrink-0 font-mono font-bold text-gray-900">₱${payment.amount_paid.toFixed(2)}</span>
                <button type="button" onclick="removeStagedPayment(${index})" class="shrink-0 font-bold text-rose-600 hover:text-rose-800" aria-label="Remove payment">Remove</button>
            </div>
        `).join('');
    }

    const coRemaining = document.getElementById('co-remaining');
    if (coRemaining) coRemaining.textContent = `₱${remainingTotal.toFixed(2)}`;
}

function selectCheckoutPayer() {
    const payerSelect = document.getElementById('payment-person-name');
    const amountInput = document.getElementById('amount-to-pay');
    const receivedInput = document.getElementById('amount-received');
    const hint = document.getElementById('payer-assignment-hint');
    const totals = checkoutPayerTotals();
    const person = payerSelect?.value || '';
    const paidForPerson = stagedCheckoutPayments
        .filter(payment => person ? payment.person_name === person : true)
        .reduce((sum, payment) => sum + payment.amount_paid, 0);
    const assignedDue = person ? (totals.get(person) || 0) : orderTotal;
    const due = Math.max(0, Math.round((assignedDue - paidForPerson) * 100) / 100);

    if (amountInput) {
        amountInput.value = due.toFixed(2);
        amountInput.max = due.toFixed(2);
    }
    if (currentMethod === 'cash' && receivedInput) {
        receivedInput.value = due.toFixed(2);
    }
    if (hint) {
        hint.textContent = person
            ? `${person} has ₱${due.toFixed(2)} remaining on assigned products.`
            : (totals.size
                ? `The full order has ₱${due.toFixed(2)} remaining. Choose a person for a split payment.`
                : 'No assigned people. This payment applies to the full order.');
    }

    computeChange();
    updateOnlineAmount();
}

function openCheckout() {
    const cashierInput = document.getElementById('cashier-name');
    const cashier = cashierInput.value.trim();
    if (!cashier) {
        showPosFeedback('Please enter the cashier name before checking out.', 'error');
        cashierInput.focus();
        return;
    }
    if (cart.length === 0) {
        showPosFeedback('Cart is empty. Select a product before checking out.', 'error');
        return;
    }
    if (splitEnabled && (!splitPeople.length || cart.some(item => !item.assigned_to))) {
        showPosFeedback('Assign every order item to a person before starting split payment.', 'error');
        return;
    }

    clearPosFeedback();
    stagedCheckoutPayments = [];

    const coTotal = document.getElementById('co-total');
    if (coTotal) coTotal.textContent = '₱' + orderTotal.toFixed(2);
    const coRemaining = document.getElementById('co-remaining');
    if (coRemaining) coRemaining.textContent = '₱' + orderTotal.toFixed(2);

    const amtInput = document.getElementById('amount-received');
    if (amtInput) amtInput.value = '';
    const amountToPayInput = document.getElementById('amount-to-pay');
    if (amountToPayInput) amountToPayInput.value = orderTotal.toFixed(2);
    const personInput = document.getElementById('payment-person-name');
    if (personInput) personInput.value = '';
    updateCheckoutPayers();
    const orderReview = document.getElementById('checkout-order-review');
    if (orderReview) orderReview.open = splitEnabled || checkoutPayerTotals().size > 0;
    const paymentCommentInput = document.getElementById('payment-comment');
    if (paymentCommentInput) paymentCommentInput.value = '';
    ['pl-customer-name', 'pl-customer-phone', 'pl-due-date', 'pl-notes'].forEach(id => {
        const input = document.getElementById(id);
        if (input) input.value = '';
    });

    const changeEl = document.getElementById('change-display');
    if (changeEl) {
        changeEl.textContent = '₱0.00';
        changeEl.className = 'font-bold text-base text-heim-700 font-mono';
    }

    const errEl = document.getElementById('checkout-error');
    if (errEl) errEl.classList.add('hidden');

    currentMethod = currentOrderType === 'grab' ? 'grabfood' : 'cash';
    if (amountToPayInput) amountToPayInput.max = orderTotal.toFixed(2);

    const refInput = document.getElementById('reference-number-input');
    if (refInput) refInput.value = '';
    selectCheckoutPayer();
    selectPaymentMethod(currentMethod);

    const completeBtn = document.getElementById('complete-btn');
    if (completeBtn) {
        completeBtn.textContent = 'Complete Order';
        completeBtn.disabled = false;
    }

    const modal = document.getElementById('checkout-modal');
    if (modal) modal.style.display = 'flex';
}

function closeCheckout() {
    const modal = document.getElementById('checkout-modal');
    if (modal) modal.style.display = 'none';
}

document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    if (document.getElementById('checkout-modal')?.style.display === 'flex') {
        closeCheckout();
    } else if (document.getElementById('size-modal')?.style.display === 'flex') {
        closeSizeModal();
    } else if (document.getElementById('held-orders-modal')?.style.display === 'flex') {
        closeHeldOrdersModal();
    }
});

function showPosFeedback(message, type = 'error') {
    const feedback = document.getElementById('pos-feedback');
    if (!feedback) return;
    feedback.textContent = message;
    feedback.className = 'mx-4 mt-3 rounded-xl border px-3 py-2 text-xs font-semibold ' + (type === 'error'
        ? 'border-rose-200 bg-rose-50 text-rose-700'
        : 'border-heim-200 bg-heim-50 text-heim-800');
}

function clearPosFeedback() {
    const feedback = document.getElementById('pos-feedback');
    if (feedback) feedback.className = 'hidden mx-4 mt-3 rounded-xl border px-3 py-2 text-xs font-semibold';
}

function selectPaymentMethod(method) {

    currentMethod = method;
    const isCash = method === 'cash';
    const isOnlineRef = method === 'online'; // needs reference number
    const showCash = isCash;
    const showOnline = isOnlineRef;

    const allMethods = ['cash', 'online', 'grabfood'];
    const colors = { cash: 'heim', online: 'blue', grabfood: 'emerald' };
    allMethods.forEach(m => {
        const btn = document.getElementById('pm-' + m);
        if (!btn) return;
        if (m === method) {
            const c = colors[m] || 'heim';
            btn.setAttribute('aria-pressed', 'true');
            btn.className = `pm-btn active flex items-center gap-2 p-3 rounded-xl border-2 border-${c}-500 bg-${c}-50 transition-all`;
        } else {
            btn.setAttribute('aria-pressed', 'false');
            btn.className = `pm-btn flex items-center gap-2 p-3 rounded-xl border-2 border-gray-200 bg-white hover:border-gray-300 transition-all`;
        }
    });

    const cashSec    = document.getElementById('cash-section');
    const onlineSec  = document.getElementById('online-section');
    const recordPaymentBtn = document.getElementById('record-payment-btn');
    document.getElementById('grab-payment-hint')?.classList.toggle('hidden', currentOrderType !== 'grab');

    if (cashSec)    cashSec.style.display = showCash ? '' : 'none';
    if (isCash) {
        const amountToPay = parseFloat(document.getElementById('amount-to-pay')?.value) || orderTotal;
        const receivedInput = document.getElementById('amount-received');
        if (receivedInput && (!receivedInput.value || parseFloat(receivedInput.value) < amountToPay)) {
            receivedInput.value = amountToPay.toFixed(2);
        }
        computeChange();
    }
    if (onlineSec) {
        onlineSec.classList.toggle('hidden', !showOnline);
        if (showOnline) {
            const hint = document.getElementById('online-hint');
            if (hint) hint.textContent = 'Ask customer to complete the online payment and verify the reference number.';
            const amtEl = document.getElementById('online-amount-display');
            if (amtEl) amtEl.textContent = '₱' + orderTotal.toFixed(2);
            const refInput = document.getElementById('reference-number-input');
            if (refInput) { refInput.value = ''; refInput.focus(); }
        }
    }
}

function setCashAmount(val) {
    const input = document.getElementById('amount-received');
    if (!input) return;
    const due = parseFloat(document.getElementById('amount-to-pay')?.value) || orderTotal;
    input.value = (Math.round((val === orderTotal ? due : val) * 100) / 100).toFixed(2);
    computeChange();
}

function updateOnlineAmount() {
    const amount = parseFloat(document.getElementById('amount-to-pay')?.value) || 0;
    const amtEl = document.getElementById('online-amount-display');
    if (amtEl) amtEl.textContent = '₱' + amount.toFixed(2);
}

function computeChange() {
    const received = parseFloat(document.getElementById('amount-received').value) || 0;
    const due = parseFloat(document.getElementById('amount-to-pay')?.value) || 0;
    const change   = Math.max(0, received - due);
    const display  = document.getElementById('change-display');
    if (!display) return;
    display.textContent = '₱' + change.toFixed(2);
    display.className = 'font-bold text-base font-mono ' + (received >= due ? 'text-heim-700' : 'text-red-600');
}

function stageCheckoutPayment() {
    const errorEl = document.getElementById('checkout-error');
    const amountPaid = Math.round((parseFloat(document.getElementById('amount-to-pay')?.value) || 0) * 100) / 100;
    const received = Math.round((parseFloat(document.getElementById('amount-received')?.value) || 0) * 100) / 100;
    const payerName = document.getElementById('payment-person-name')?.value.trim() || '';
    const referenceNumber = document.getElementById('reference-number-input')?.value.trim() || '';
    const comment = document.getElementById('payment-comment')?.value.trim() || '';
    const totals = checkoutPayerTotals();
    const alreadyStaged = stagedCheckoutPayments.reduce((sum, payment) => sum + payment.amount_paid, 0);
    const remainingTotal = Math.round((orderTotal - alreadyStaged) * 100) / 100;
    const personPaid = stagedCheckoutPayments
        .filter(payment => payment.person_name === payerName)
        .reduce((sum, payment) => sum + payment.amount_paid, 0);

    if (amountPaid <= 0 || amountPaid > remainingTotal) {
        errorEl.textContent = `Enter an amount from ₱0.01 up to the remaining ₱${remainingTotal.toFixed(2)}.`;
        errorEl.classList.remove('hidden');
        return;
    }
    if (totals.size && !payerName && !(stagedCheckoutPayments.length === 0 && amountPaid === orderTotal)) {
        errorEl.textContent = 'Select the person whose assigned products this payment covers.';
        errorEl.classList.remove('hidden');
        document.getElementById('payment-person-name')?.focus();
        return;
    }
    if (payerName && amountPaid + personPaid > (totals.get(payerName) || 0)) {
        errorEl.textContent = `${payerName}’s recorded payments cannot exceed their assigned total of ₱${(totals.get(payerName) || 0).toFixed(2)}.`;
        errorEl.classList.remove('hidden');
        return;
    }
    if (currentMethod === 'cash' && received < amountPaid) {
        errorEl.textContent = `Cash received (₱${received.toFixed(2)}) is less than the payment amount (₱${amountPaid.toFixed(2)}).`;
        errorEl.classList.remove('hidden');
        return;
    }
    if (currentMethod === 'online' && !referenceNumber) {
        errorEl.textContent = 'Please enter a reference number for this online payment.';
        errorEl.classList.remove('hidden');
        document.getElementById('reference-number-input')?.focus();
        return;
    }

    const methodLabels = { cash: 'Cash', online: 'Online', grabfood: 'GrabFood' };
    stagedCheckoutPayments.push({
        method: currentMethod,
        method_label: methodLabels[currentMethod] || currentMethod,
        amount_paid: amountPaid,
        amount_received: currentMethod === 'cash' ? received : amountPaid,
        person_name: payerName,
        reference_number: referenceNumber,
        comment,
    });

    errorEl.classList.add('hidden');
    document.getElementById('payment-comment').value = '';
    if (document.getElementById('reference-number-input')) {
        document.getElementById('reference-number-input').value = '';
    }
    updateCheckoutPayers();
    selectCheckoutPayer();
}

function removeStagedPayment(index) {
    stagedCheckoutPayments.splice(index, 1);
    updateCheckoutPayers();
    selectCheckoutPayer();
}

function completeOrder() {
    const cashierInput = document.getElementById('cashier-name');
    const cashier  = cashierInput.value.trim();
    const received = parseFloat(document.getElementById('amount-received').value) || 0;
    const amountPaid = parseFloat(document.getElementById('amount-to-pay').value) || 0;
    const hasStagedPayments = stagedCheckoutPayments.length > 0;
    const stagedPaidTotal = Math.round(stagedCheckoutPayments.reduce((sum, payment) => sum + payment.amount_paid, 0) * 100) / 100;
    const payerName = document.getElementById('payment-person-name')?.value.trim() || '';
    const payerTotals = checkoutPayerTotals();
    const refInput = document.getElementById('reference-number-input');
    const refNum   = refInput ? refInput.value.trim() : '';
    const errorEl  = document.getElementById('checkout-error');
    const btn      = document.getElementById('complete-btn');

    if (!cashier) {
        errorEl.textContent = 'Cashier name is required.';
        errorEl.classList.remove('hidden');
        cashierInput.focus();
        return;
    }
    if (!hasStagedPayments && (amountPaid <= 0 || amountPaid > orderTotal)) {
        errorEl.textContent = `Payment amount must be between ₱0.01 and the order total of ₱${orderTotal.toFixed(2)}.`;
        errorEl.classList.remove('hidden');
        return;
    }
    if (hasStagedPayments && (stagedPaidTotal <= 0 || stagedPaidTotal > orderTotal)) {
        errorEl.textContent = 'The staged payments total is invalid.';
        errorEl.classList.remove('hidden');
        return;
    }
    if (!hasStagedPayments && payerTotals.size > 0 && amountPaid < orderTotal && !payerName) {
        errorEl.textContent = 'Select the person whose assigned products this split payment covers.';
        errorEl.classList.remove('hidden');
        document.getElementById('payment-person-name')?.focus();
        return;
    }
    if (!hasStagedPayments && payerName && amountPaid > (payerTotals.get(payerName) || 0)) {
        errorEl.textContent = `${payerName}’s payment cannot exceed their assigned total of ₱${(payerTotals.get(payerName) || 0).toFixed(2)}.`;
        errorEl.classList.remove('hidden');
        return;
    }
    if (!hasStagedPayments && currentMethod === 'cash' && received < amountPaid) {
        errorEl.textContent = `Cash received (₱${received.toFixed(2)}) is less than the payment amount (₱${amountPaid.toFixed(2)}).`;
        errorEl.classList.remove('hidden');
        return;
    }
    if (!hasStagedPayments && currentMethod === 'online' && !refNum) {
        errorEl.textContent = 'Please enter the reference number for this online payment.';
        errorEl.classList.remove('hidden');
        if (refInput) refInput.focus();
        return;
    }
    // Grab order validation
    if (currentOrderType === 'grab') {
        const grabCode = document.getElementById('grab-order-code')?.value.trim();
        const riderCode = document.getElementById('grab-rider-code')?.value.trim();
        if (!grabCode) {
            errorEl.textContent = 'Grab Order Code is required for Grab orders.';
            errorEl.classList.remove('hidden');
            return;
        }
        if (!riderCode) {
            errorEl.textContent = 'Rider Code is required for Grab orders.';
            errorEl.classList.remove('hidden');
            return;
        }
    }

    const discountType = document.getElementById('discount-type')?.value || 'none';
    const discountIdNumber = document.getElementById('discount-id-number')?.value.trim() || '';
    if ((discountType === 'senior' || discountType === 'pwd') && !discountIdNumber) {
        errorEl.textContent = 'Enter the customer ID number for the statutory discount.';
        errorEl.classList.remove('hidden');
        document.getElementById('discount-id-number')?.focus();
        return;
    }

    btn.textContent = 'Processing...';
    btn.disabled = true;
    errorEl.classList.add('hidden');

    // Populate hidden form
    document.getElementById('f-cashier').value   = cashier;
    const primaryPayment = hasStagedPayments ? stagedCheckoutPayments[0] : null;
    const submittedMethod = primaryPayment?.method || currentMethod;
    const submittedAmountPaid = hasStagedPayments ? stagedPaidTotal : amountPaid;
    const submittedReceived = hasStagedPayments
        ? stagedCheckoutPayments.filter(payment => payment.method === 'cash').reduce((sum, payment) => sum + payment.amount_received, 0)
        : received;
    document.getElementById('f-method').value = submittedMethod;
    document.getElementById('f-amount').value = submittedMethod === 'cash'
        ? (hasStagedPayments ? submittedReceived : received)
        : submittedAmountPaid;
    document.getElementById('f-amount-paid').value = submittedAmountPaid;
    document.getElementById('f-person-name').value = hasStagedPayments ? (primaryPayment.person_name || '') : payerName;
    document.getElementById('f-payment-comment').value = hasStagedPayments
        ? (primaryPayment.comment || '')
        : (document.getElementById('payment-comment')?.value.trim() || '');
    document.getElementById('f-discount').value = discountType === 'custom' ? currentDiscountAmount : 0;
    document.getElementById('f-discount-type').value = discountType;
    document.getElementById('f-discount-label').value = document.getElementById('discount-type')?.selectedOptions[0]?.text || '';
    document.getElementById('f-discount-id-number').value = discountIdNumber;
    document.getElementById('f-reference').value = hasStagedPayments
        ? (primaryPayment.reference_number || '')
        : (currentMethod !== 'cash' && currentMethod !== 'grabfood' ? refNum : (currentMethod === 'grabfood' ? refNum : ''));
    document.getElementById('f-held-order-id').value = currentHeldOrderId || '';
    const paymentsContainer = document.getElementById('f-payments');
    paymentsContainer.innerHTML = '';
    if (hasStagedPayments) {
        stagedCheckoutPayments.forEach((payment, index) => {
            paymentsContainer.innerHTML += `
                <input type="hidden" name="payments[${index}][method]" value="${escapeHtml(payment.method)}">
                <input type="hidden" name="payments[${index}][amount_paid]" value="${payment.amount_paid.toFixed(2)}">
                <input type="hidden" name="payments[${index}][amount_received]" value="${payment.amount_received.toFixed(2)}">
                <input type="hidden" name="payments[${index}][person_name]" value="${escapeHtml(payment.person_name)}">
                <input type="hidden" name="payments[${index}][reference_number]" value="${escapeHtml(payment.reference_number)}">
                <input type="hidden" name="payments[${index}][comment]" value="${escapeHtml(payment.comment)}">
            `;
        });
    }
    // Grab fields
    document.getElementById('f-order-type').value = currentOrderType;
    document.getElementById('f-grab-order-code').value = document.getElementById('grab-order-code')?.value.trim() || '';
    document.getElementById('f-rider-code').value = document.getElementById('grab-rider-code')?.value.trim() || '';
    document.getElementById('f-customer-name').value = document.getElementById('order-customer-name')?.value.trim() || '';

    const container = document.getElementById('f-items');
    container.innerHTML = '';
    cart.forEach((item, idx) => {
        container.innerHTML += `
            <input type="hidden" name="items[${idx}][product_size_id]" value="${item.product_size_id}">
            <input type="hidden" name="items[${idx}][quantity]" value="${item.qty}">
            <input type="hidden" name="items[${idx}][comment]" value="${escapeHtml(item.comment || '')}">
            <input type="hidden" name="items[${idx}][assigned_to]" value="${escapeHtml(item.assigned_to || '')}">
        `;
        if (item.addons && item.addons.length) {
            item.addons.forEach((addon) => {
                container.innerHTML += `<input type="hidden" name="items[${idx}][addon_ids][]" value="${addon.id}">`;
            });
        }
    });

    document.getElementById('order-form').submit();
}

// ── Shift In / Out ─────────────────────────────────────────────────────────
const shiftClockServerTime = new Date(@json(now('UTC')->toIso8601String())).getTime();
const shiftClockClientAnchor = Date.now();
const businessTimeZone = @json(config('app.business_timezone', 'Asia/Manila'));

function updateShiftInDateTime() {
    const dateTimeElement = document.getElementById('si-datetime');
    if (!dateTimeElement) return;

    const currentTime = new Date(shiftClockServerTime + (Date.now() - shiftClockClientAnchor));
    const parts = new Intl.DateTimeFormat('en-US', {
        timeZone: businessTimeZone,
        month: 'short',
        day: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    }).formatToParts(currentTime);
    const value = (type) => parts.find((part) => part.type === type)?.value || '';

    dateTimeElement.textContent = `${value('month')} ${value('day')}, ${value('year')} ${value('hour')}:${value('minute')} ${value('dayPeriod')}`;
}

updateShiftInDateTime();
setInterval(updateShiftInDateTime, 15000);

function openShiftInModal() {
    document.getElementById('si-cashier-name').textContent = @json(auth()->user()->name);
    updateShiftInDateTime();
    const errEl = document.getElementById('si-error');
    if (errEl) errEl.classList.add('hidden');
    document.getElementById('si-beginning-cash').value = '';
    document.getElementById('shift-in-modal').style.display = 'flex';
    setTimeout(() => document.getElementById('si-beginning-cash').focus(), 100);
}

function closeShiftInModal() {
    document.getElementById('shift-in-modal').style.display = 'none';
}

async function submitShiftIn() {
    const beginningCash = parseFloat(document.getElementById('si-beginning-cash').value) || 0;
    const errEl = document.getElementById('si-error');
    const btn = document.getElementById('si-submit-btn');

    if (beginningCash < 0) {
        errEl.textContent = 'Beginning cash cannot be negative.';
        errEl.classList.remove('hidden');
        return;
    }

    btn.textContent = 'Starting...';
    btn.disabled = true;
    errEl.classList.add('hidden');

    try {
        const response = await fetch('/shifts/start', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ beginning_cash: beginningCash })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Failed to start shift.');
        closeShiftInModal();
        showPosFeedback('Shift started successfully. Beginning cash: ₱' + beginningCash.toFixed(2), 'success');
        setTimeout(() => window.location.reload(), 1200);
    } catch (err) {
        errEl.textContent = err.message || 'Failed to start shift.';
        errEl.classList.remove('hidden');
        btn.textContent = '▶ Start Shift';
        btn.disabled = false;
    }
}

async function openShiftOutModal() {
    const errEl = document.getElementById('so-error');
    if (errEl) errEl.classList.add('hidden');
    document.getElementById('so-actual-cash').value = '';
    document.getElementById('so-comment').value = '';
    document.getElementById('so-difference').textContent = '-';
    document.getElementById('so-expected').textContent = 'Enter count to reveal';
    document.getElementById('so-cash-summary').classList.add('hidden');
    document.getElementById('so-open-orders-warning').classList.add('hidden');
    document.getElementById('so-use-denominations').checked = false;
    document.querySelectorAll('[data-denomination]').forEach(input => { input.value = '0'; input.disabled = true; });
    document.getElementById('so-actual-cash').disabled = false;
    document.getElementById('so-denomination-panel').classList.add('hidden');
    document.getElementById('so-override-reason').value = '';
    document.getElementById('so-authorizer-email').value = '';
    document.getElementById('so-authorizer-password').value = '';
    const diffRow = document.getElementById('so-diff-row');
    if (diffRow) diffRow.className = 'flex justify-between text-sm font-bold p-3 rounded-xl bg-gray-50 border border-gray-100';

    try {
        const response = await fetch(@json(route('shifts.current')), { headers: { 'Accept': 'application/json' } });
        const data = await response.json();
        if (!response.ok || !data.active || !data.shift) {
            throw new Error(data.message || 'No active shift was found. Start a shift before closing.');
        }
        if (data.shift) {
            const s = data.shift;
            document.getElementById('so-cashier').textContent = s.cashier_name || '-';
            document.getElementById('so-beginning').textContent = '₱' + parseFloat(s.beginning_cash || 0).toFixed(2);
            if (s.unresolved_orders > 0) {
                document.getElementById('so-open-orders-warning').classList.remove('hidden');
                document.getElementById('so-open-orders-text').textContent = `${s.unresolved_orders} held or unpaid ticket(s) remain attached to this shift.`;
            }
            const nonCash = s.non_cash_summary || {};
            document.getElementById('so-online-sales').textContent = `₱${Number(nonCash.online_sales || 0).toFixed(2)}`;
            document.getElementById('so-grab-sales').textContent = `₱${Number(nonCash.grab_sales || 0).toFixed(2)} / ₱${Number(nonCash.grab_settlements || 0).toFixed(2)}`;
            document.getElementById('so-order-type-sales').textContent = `₱${Number(nonCash.dine_in_sales || 0).toFixed(2)} / ₱${Number(nonCash.take_out_sales || 0).toFixed(2)}`;
            document.getElementById('so-voids').textContent = `${Number(nonCash.void_count || 0)} · ₱${Number(nonCash.void_amount || 0).toFixed(2)}`;
        }
    } catch (error) {
        if (errEl) {
            errEl.textContent = error.message || 'Failed to load shift details.';
            errEl.classList.remove('hidden');
        }
    }

    document.getElementById('shift-out-modal').style.display = 'flex';
    setTimeout(() => document.getElementById('so-actual-cash').focus(), 100);
}

function updateShiftOutDifference() {
    const useDenominations = document.getElementById('so-use-denominations').checked;
    const denominationCount = getShiftDenominationCount();
    const actualCash = useDenominations
        ? Object.entries(denominationCount).reduce((sum, [denomination, count]) => sum + Number(denomination) * count, 0)
        : (parseFloat(document.getElementById('so-actual-cash').value) || 0);
    document.getElementById('so-denomination-total').textContent = `₱${actualCash.toFixed(2)}`;
    const hasCount = useDenominations || document.getElementById('so-actual-cash').value !== '';
    const summary = document.getElementById('so-cash-summary');
    if (!hasCount) {
        summary.classList.add('hidden');
        document.getElementById('so-expected').textContent = 'Enter count to reveal';
        document.getElementById('so-difference').textContent = '-';
        return;
    }

    clearTimeout(shiftPreviewTimer);
    shiftPreviewTimer = setTimeout(async () => {
        const payload = useDenominations
            ? { denomination_count: denominationCount }
            : { actual_cash: actualCash };
        try {
            const response = await fetch(@json(route('shifts.preview-end')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                },
                body: JSON.stringify(payload),
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Could not calculate the shift count.');
            const cash = data.summary;
            summary.classList.remove('hidden');
            document.getElementById('so-cash-sales').textContent = `₱${Number(cash.cash_sales).toFixed(2)}`;
            document.getElementById('so-cash-refunds').textContent = `−₱${Number(cash.cash_refunds).toFixed(2)}`;
            document.getElementById('so-cash-voids').textContent = `−₱${Number(cash.cash_voids).toFixed(2)}`;
            document.getElementById('so-expected').textContent = `₱${Number(data.expected_cash).toFixed(2)}`;
            document.getElementById('so-difference').textContent = `${data.difference >= 0 ? '+' : ''}₱${Number(data.difference).toFixed(2)}`;
            const diffRow = document.getElementById('so-diff-row');
            diffRow.className = 'flex justify-between text-sm font-bold p-3 rounded-xl ' + (data.difference < 0
                ? 'bg-rose-50 border border-rose-200 text-rose-800'
                : data.difference > 0
                    ? 'bg-amber-50 border border-amber-200 text-amber-800'
                    : 'bg-emerald-50 border border-emerald-200 text-emerald-800');
        } catch (error) {
            const errEl = document.getElementById('so-error');
            errEl.textContent = error.message || 'Could not calculate the shift count.';
            errEl.classList.remove('hidden');
        }
    }, 250);
}

function toggleShiftDenominations() {
    const enabled = document.getElementById('so-use-denominations').checked;
    document.getElementById('so-denomination-panel').classList.toggle('hidden', !enabled);
    document.getElementById('so-actual-cash').disabled = enabled;
    document.querySelectorAll('[data-denomination]').forEach(input => { input.disabled = !enabled; });
    updateShiftOutDifference();
}

function getShiftDenominationCount() {
    const counts = {};
    document.querySelectorAll('[data-denomination]').forEach(input => {
        counts[input.dataset.denomination] = Math.max(0, parseInt(input.value, 10) || 0);
    });
    return counts;
}

function closeShiftOutModal() {
    document.getElementById('shift-out-modal').style.display = 'none';
}

async function submitShiftOut() {
    const actualCash = parseFloat(document.getElementById('so-actual-cash').value);
    const useDenominations = document.getElementById('so-use-denominations').checked;
    const comment = document.getElementById('so-comment').value.trim();
    const errEl = document.getElementById('so-error');
    const btn = document.getElementById('so-submit-btn');

    if (!useDenominations && (isNaN(actualCash) || actualCash < 0)) {
        errEl.textContent = 'Please enter the actual cash amount.';
        errEl.classList.remove('hidden');
        return;
    }

    btn.textContent = 'Ending shift...';
    btn.disabled = true;
    errEl.classList.add('hidden');

    try {
        const response = await fetch('/shifts/end', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                ...(useDenominations ? { denomination_count: getShiftDenominationCount() } : { actual_cash: actualCash }),
                comment,
                override_reason: document.getElementById('so-override-reason').value.trim(),
                authorizer_email: document.getElementById('so-authorizer-email').value.trim(),
                authorizer_password: document.getElementById('so-authorizer-password').value,
            })
        });
        const data = await response.json();
        if (!response.ok && data.open_orders > 0) {
            document.getElementById('so-open-orders-warning').classList.remove('hidden');
            document.getElementById('so-open-orders-text').textContent = `${data.open_orders} held or unpaid ticket(s) remain attached to this shift.`;
        }
        if (!response.ok) throw new Error(data.message || 'Failed to end shift.');
        closeShiftOutModal();
        showPosFeedback('Shift ended. Difference: ' + (data.shift?.difference >= 0 ? '+' : '') + '₱' + parseFloat(data.shift?.difference || 0).toFixed(2), 'success');
        setTimeout(() => window.location.reload(), 1500);
    } catch (err) {
        errEl.textContent = err.message || 'Failed to end shift.';
        errEl.classList.remove('hidden');
        btn.textContent = 'End Shift';
        btn.disabled = false;
    }
}

// ── Hold & Resume Orders ─────────────────────────────────────────────────────
async function holdCurrentOrder() {
    const cashierInput = document.getElementById('cashier-name');
    const cashier = cashierInput ? cashierInput.value.trim() : '';
    if (!cashier) {
        showPosFeedback('Please enter the cashier name before holding this order.', 'error');
        if (cashierInput) cashierInput.focus();
        return;
    }
    if (cart.length === 0) {
        showPosFeedback('Cannot hold an empty cart.', 'error');
        return;
    }

    const holdBtn = document.getElementById('hold-btn');
    if (holdBtn) {
        holdBtn.disabled = true;
        holdBtn.innerHTML = '<span>⏳</span> Holding...';
    }

    try {
        const payload = {
            cashier_name: cashier,
            order_type: currentOrderType,
            grab_order_code: document.getElementById('grab-order-code')?.value.trim() || null,
            rider_code: document.getElementById('grab-rider-code')?.value.trim() || null,
            customer_name: document.getElementById('order-customer-name')?.value.trim() || null,
            items: cart.map(i => ({
                product_size_id: i.product_size_id,
                quantity: i.qty,
                comment: i.comment || null,
                assigned_to: i.assigned_to || null,
                addon_ids: (i.addons || []).map(a => a.id)
            })),
            discount: parseFloat(document.getElementById('discount')?.value) || 0,
            discount_type: document.getElementById('discount-type')?.value || 'none',
            discount_label: document.getElementById('discount-type')?.selectedOptions[0]?.text || null,
            discount_id_number: document.getElementById('discount-id-number')?.value || null,
        };

        const response = await fetch('{{ route('pos.hold') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || 'Failed to hold order.');
        }

        clearCart();
        currentHeldOrderId = null;
        closeCheckout();
        showPosFeedback(data.message || 'Order held successfully.', 'success');
        loadHeldOrdersCount();
    } catch (err) {
        const checkoutError = document.getElementById('checkout-error');
        if (checkoutError && document.getElementById('checkout-modal')?.style.display === 'flex') {
            checkoutError.textContent = err.message || 'An error occurred while holding the order.';
            checkoutError.classList.remove('hidden');
        } else {
            showPosFeedback(err.message || 'An error occurred while holding the order.', 'error');
        }
    } finally {
        if (holdBtn) {
            holdBtn.disabled = cart.length === 0;
            holdBtn.innerHTML = '<span>📌</span> Save / Hold Order';
        }
    }
}

async function loadHeldOrdersCount() {
    try {
        const response = await fetch('{{ route('pos.held-orders') }}', {
            headers: { 'Accept': 'application/json' }
        });
        if (!response.ok) return;
        const orders = await response.json();
        const count = orders.length;
        const badge = document.getElementById('held-count-badge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-block' : 'none';
        }
        const totalCountEl = document.getElementById('held-modal-total-count');
        if (totalCountEl) {
            totalCountEl.textContent = `${count} order${count === 1 ? '' : 's'} on hold`;
        }
    } catch (e) {
        console.error('Failed to load held orders count:', e);
    }
}

async function openHeldOrdersModal() {
    const modal = document.getElementById('held-orders-modal');
    const list = document.getElementById('held-orders-list');
    if (!modal || !list) return;

    list.innerHTML = `
        <div class="flex flex-col items-center justify-center py-12 text-gray-400">
            <svg class="animate-spin h-8 w-8 text-heim-600 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <p class="text-xs">Loading held tickets...</p>
        </div>
    `;
    modal.style.display = 'flex';

    try {
        const response = await fetch('{{ route('pos.held-orders') }}', {
            headers: { 'Accept': 'application/json' }
        });
        const orders = await response.json();
        renderHeldOrders(orders);
    } catch (err) {
        list.innerHTML = `
            <div class="text-center py-10 text-red-500 text-xs">
                Failed to load held orders. Please try again.
            </div>
        `;
    }
}

function closeHeldOrdersModal() {
    const modal = document.getElementById('held-orders-modal');
    if (modal) modal.style.display = 'none';
}

function renderHeldOrders(orders) {
    const list = document.getElementById('held-orders-list');
    const totalCountEl = document.getElementById('held-modal-total-count');
    if (!list) return;

    if (totalCountEl) {
        totalCountEl.textContent = `${orders.length} order${orders.length === 1 ? '' : 's'} on hold`;
    }

    if (!orders || orders.length === 0) {
        list.innerHTML = `
            <div class="flex flex-col items-center justify-center py-12 text-center text-gray-400">
                <span class="text-3xl mb-2">📋</span>
                <p class="text-sm font-semibold text-gray-600">No held tickets</p>
                <p class="text-xs text-gray-400 mt-1">Orders saved with "Save / Hold Order" will appear here.</p>
            </div>
        `;
        return;
    }

    list.innerHTML = orders.map(order => {
        const items = order.order_items || [];
        const isPinned = !!order.is_pinned;
        const formattedDate = new Date(order.held_at || order.created_at).toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
            timeZone: @json(config('app.business_timezone', 'Asia/Manila'))
        });

        return `
            <div class="p-4 rounded-xl border ${isPinned ? 'border-amber-300 bg-amber-50/40 shadow-xs' : 'border-gray-200 bg-white hover:border-gray-300'} transition-all">
                <div class="flex items-start justify-between gap-3 mb-2.5">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-sm text-gray-900">${order.order_number}</span>
                            ${isPinned ? '<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-300 flex items-center gap-1">📌 Pinned</span>' : ''}
                        </div>
                        <div class="flex items-center gap-2 text-xs text-gray-500 mt-0.5">
                            <span>Cashier: <strong class="text-gray-700">${escapeHtml(order.cashier_name)}</strong></span>
                            <span>•</span>
                            <span>Held at: <strong>${formattedDate}</strong></span>
                        </div>
                        <div class="mt-1 text-[11px] font-semibold text-slate-600">${escapeHtml(({ dine_in: 'Dine-in', take_out: 'Take-out', grab: 'GrabFood' })[order.order_type] || 'Dine-in')}${order.customer_name ? ` · ${escapeHtml(order.customer_name)}` : ''}${order.order_type === 'grab' ? ` · ${escapeHtml(order.grab_order_code || 'Code not entered')}` : ''}</div>
                    </div>
                    <div class="text-right">
                        <span class="font-extrabold text-base text-heim-700 font-mono">₱${parseFloat(order.total).toFixed(2)}</span>
                        <p class="text-[10px] text-gray-400">${items.length} item${items.length === 1 ? '' : 's'}</p>
                    </div>
                </div>

                <div class="bg-gray-50/80 rounded-lg p-2.5 text-xs text-gray-600 space-y-1 mb-3 border border-gray-100">
                    ${items.map(i => `
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="font-semibold text-gray-800">${i.quantity}x ${escapeHtml(i.product ? i.product.name : 'Item')}</span>
                                ${i.size ? `<span class="text-[11px] text-heim-600">(${escapeHtml(i.size.size_name)})</span>` : ''}
                                ${i.assigned_to ? `<p class="text-[10px] text-indigo-700 pl-3">↳ For: ${escapeHtml(i.assigned_to)}</p>` : ''}
                                ${i.comment ? `<p class="text-[10px] text-amber-700 italic pl-3">↳ Note: ${escapeHtml(i.comment)}</p>` : ''}
                            </div>
                            <span class="font-mono text-gray-700">₱${parseFloat(i.subtotal).toFixed(2)}</span>
                        </div>
                    `).join('')}
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                    <div class="flex items-center gap-1.5">
                        <button type="button" onclick="togglePinOrder(${order.id})"
                            class="px-2.5 py-1.5 rounded-lg border text-xs font-semibold transition-colors flex items-center gap-1 ${isPinned ? 'border-amber-300 bg-amber-100 text-amber-900 hover:bg-amber-200' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-700'}">
                            <span>📌</span>
                            <span>${isPinned ? 'Unpin' : 'Pin Ticket'}</span>
                        </button>
                        <button type="button" onclick="voidHeldOrder(${order.id}, '${order.order_number}')"
                            class="px-2.5 py-1.5 rounded-lg border border-rose-200 bg-white hover:bg-rose-50 text-rose-600 text-xs font-semibold transition-colors">
                            Void
                        </button>
                    </div>
                    <button type="button" onclick="resumeHeldOrder(${order.id})"
                        class="brand-button px-4 py-1.5 text-xs font-bold shadow-xs">
                        Open →
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

async function togglePinOrder(orderId) {
    try {
        const response = await fetch(`/pos/saved-orders/${orderId}/pin`, {
            method: 'PATCH',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        const data = await response.json();
        if (data.success) {
            openHeldOrdersModal();
        }
    } catch (e) {
        console.error('Failed to toggle pin:', e);
    }
}

async function resumeHeldOrder(orderId) {
    if (cart.length > 0) {
        if (!confirm('Loading this held order will replace the current items in your cart. Proceed?')) {
            return;
        }
    }

    try {
        const response = await fetch(`/pos/saved-orders/${orderId}/resume`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result.error || 'Failed to resume held order.');
        }

        const data = result.data;
        cart = data.cart.map(item => ({
            key: item.key,
            product_id: item.product_id,
            product_size_id: item.product_size_id,
            name: item.name,
            size: item.size,
            price: item.price,
            regular_price: item.regular_price ?? item.price,
            grab_price: item.grab_price ?? item.price,
            addons: item.addons || [],
            addon_total: (item.addons || []).reduce((s, a) => s + (a.price || 0), 0),
            unit_price: item.unit_price,
            qty: item.qty,
            comment: item.comment || '',
            assigned_to: item.assigned_to || '',
            expanded: false,
            recipe: []
        }));

        currentHeldOrderId = null;
        currentHeldOrderNumber = data.order_number || null;
        setOrderType(data.order_type || 'dine_in');
        const grabOrderCodeInput = document.getElementById('grab-order-code');
        const riderCodeInput = document.getElementById('grab-rider-code');
        const grabCustomerInput = document.getElementById('order-customer-name');
        if (grabOrderCodeInput) grabOrderCodeInput.value = data.grab_order_code || '';
        if (riderCodeInput) riderCodeInput.value = data.rider_code || '';
        if (grabCustomerInput) grabCustomerInput.value = data.customer_name || '';
        updateOrderSummary();
        splitPeople = [...new Set(data.cart.map(item => item.assigned_to).filter(Boolean))];
        activeSplitPerson = splitPeople[0] || '';
        splitEnabled = splitPeople.length > 0;
        const splitToggle = document.getElementById('split-toggle');
        if (splitToggle) splitToggle.checked = splitEnabled;
        document.getElementById('split-people-panel')?.classList.toggle('hidden', !splitEnabled);
        renderSplitPeople();

        // Restore discount
        const discSelect = document.getElementById('discount-type');
        if (discSelect && data.discount_type) {
            discSelect.value = data.discount_type;
            if (typeof onDiscountTypeChange === 'function') {
                onDiscountTypeChange();
            }
        }
        const discInput = document.getElementById('discount');
        if (discInput && data.discount) {
            discInput.value = data.discount;
        }
        const discIdInput = document.getElementById('discount-id-number');
        if (discIdInput && data.discount_id_number) {
            discIdInput.value = data.discount_id_number;
        }

        renderCart();
        closeHeldOrdersModal();
        loadHeldOrdersCount();
        showPosFeedback(`Resumed held order #${data.order_number}.`, 'success');
    } catch (err) {
        alert(err.message || 'Failed to resume held order.');
    }
}

async function voidHeldOrder(orderId, orderNumber) {
    // Show void reason modal
    const modal = document.getElementById('void-ticket-modal');
    const orderNumEl = document.getElementById('void-ticket-order-number');
    const reasonInput = document.getElementById('void-ticket-reason');
    const errorEl = document.getElementById('void-ticket-error');
    if (!modal) return;

    orderNumEl.textContent = orderNumber;
    reasonInput.value = '';
    errorEl.classList.add('hidden');
    modal.style.display = 'flex';

    // Store pending action
    modal._pendingOrderId = orderId;
    modal._pendingOrderNumber = orderNumber;
}

async function confirmVoidHeldOrder() {
    const modal = document.getElementById('void-ticket-modal');
    const reasonInput = document.getElementById('void-ticket-reason');
    const errorEl = document.getElementById('void-ticket-error');
    const confirmBtn = document.getElementById('void-ticket-confirm-btn');
    const orderId = modal._pendingOrderId;
    const orderNumber = modal._pendingOrderNumber;

    const reason = reasonInput.value.trim();
    if (!reason) {
        errorEl.textContent = 'Please enter a reason before voiding.';
        errorEl.classList.remove('hidden');
        reasonInput.focus();
        return;
    }

    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Voiding...';

    try {
        const response = await fetch(`/pos/saved-orders/${orderId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ reason })
        });

        const data = await response.json();
        modal.style.display = 'none';
        if (data.success) {
            openHeldOrdersModal();
            loadHeldOrdersCount();
            showPosFeedback(data.message || 'Saved ticket voided.', 'success');
        } else {
            showPosFeedback(data.message || 'Failed to void ticket.', 'error');
        }
    } catch (e) {
        modal.style.display = 'none';
        showPosFeedback('Failed to void saved ticket.', 'error');
    } finally {
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Void Ticket';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    loadHeldOrdersCount();

    document.getElementById('main-content')?.addEventListener('scroll', updateMobileOrderShortcut, { passive: true });
    window.addEventListener('resize', updateMobileOrderShortcut, { passive: true });
    updateMobileOrderShortcut();
});

window.addEventListener('pageshow', function () {
    const btn = document.getElementById('complete-btn');
    if (btn) {
        btn.textContent = 'Complete Order';
        btn.disabled = false;
    }
    const coBtn = document.getElementById('checkout-btn');
    if (coBtn) {
        coBtn.disabled = cart.length === 0;
    }
    loadHeldOrdersCount();
});
</script>
@endpush
@endsection
