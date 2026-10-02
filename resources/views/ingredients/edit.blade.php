@extends('layouts.app')
@section('title', 'Edit Ingredient')
@section('header', 'Edit Ingredient')
@section('subheader', 'Update raw ingredient specifications, measurement units, and safety thresholds')

@section('header-actions')
    <a href="{{ route('ingredients.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 shadow-xs transition-all">
        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Ingredients
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
                <h2 class="font-bold text-gray-900 text-base">Edit Ingredient</h2>
                <p class="text-xs text-gray-400">Modify properties for {{ $ingredient->name }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('ingredients.update', $ingredient) }}" class="space-y-5">
            @csrf @method('PUT')
            <div>
                <label class="brand-label mb-1.5">Ingredient Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $ingredient->name) }}" class="brand-input" required autofocus>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="brand-label mb-1.5">Measurement Unit</label>
                    <select name="unit" class="brand-input">
                        @foreach(['ml'=>'ml (Milliliters)','g'=>'g (Grams)','kg'=>'kg (Kilograms)','L'=>'L (Liters)','pc'=>'pc (Pieces)','tbsp'=>'tbsp (Tablespoons)','tsp'=>'tsp (Teaspoons)'] as $key => $label)
                        <option value="{{ $key }}" {{ old('unit', $ingredient->unit) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Once used in a recipe or inventory movement, the unit is locked to keep stock and consumption history consistent.</p>
                </div>
                <div>
                    <label class="brand-label mb-1.5">Minimum Threshold</label>
                    <input type="number" name="minimum_stock" value="{{ old('minimum_stock', $ingredient->minimum_stock) }}" min="0" step="0.01" class="brand-input font-semibold text-gray-800">
                </div>
                <div>
                    <label class="brand-label mb-1.5">Reorder Level</label>
                    <input type="number" name="reorder_level" value="{{ old('reorder_level', $ingredient->reorder_level) }}" min="0" step="0.001" class="brand-input">
                </div>
                <div>
                    <label class="brand-label mb-1.5">Unit Cost (₱)</label>
                    <input type="number" name="cost" value="{{ old('cost', $ingredient->cost) }}" min="0" step="0.01" class="brand-input">
                </div>
                <div>
                    <label class="brand-label mb-1.5">Supplier</label>
                    <select name="supplier_id" class="brand-input">
                        <option value="">No default supplier</option>
                        @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(old('supplier_id', $ingredient->supplier_id) == $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="brand-label mb-1.5">Expiration Date</label>
                    <input type="date" name="expiration_date" value="{{ old('expiration_date', $ingredient->expiration_date?->format('Y-m-d')) }}" class="brand-input">
                </div>
            </div>

            <div>
                <label class="brand-label mb-1.5">Catalog Status</label>
                <select name="status" class="brand-input">
                    <option value="active" {{ old('status', $ingredient->status) === 'active' ? 'selected' : '' }}>Unarchived (Available for Recipes & Deliveries)</option>
                    <option value="inactive" {{ old('status', $ingredient->status) === 'inactive' ? 'selected' : '' }}>Archived (Unavailable for Recipes & Deliveries)</option>
                </select>
            </div>

            {{-- Current stock info card --}}
            <div class="rounded-xl border border-heim-100 bg-heim-50/50 p-4 text-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-heim-800">Current Stock On Hand</p>
                        <p class="font-black text-heim-900 text-xl mt-0.5">
                            {{ number_format($ingredient->getCurrentStock(), 3) }} <span class="text-sm font-semibold text-heim-700">{{ $ingredient->unit }}</span>
                        </p>
                    </div>
                    <a href="{{ route('stock-in.index', ['ingredient_id' => $ingredient->id]) }}" class="inline-flex items-center gap-1 text-xs font-bold text-heim-700 bg-white border border-heim-200/80 hover:bg-heim-50 px-3 py-1.5 rounded-lg shadow-xs transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        Deliveries
                    </a>
                </div>
                <p class="text-xs text-gray-500 mt-2">Physical counts are modified through deliveries, waste write-offs, or stock adjustments.</p>
            </div>

            <div class="flex items-center gap-3 pt-3 border-t border-gray-100">
                <a href="{{ route('ingredients.index') }}" class="brand-btn-cancel flex-1 justify-center">
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
