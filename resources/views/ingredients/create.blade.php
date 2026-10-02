@extends('layouts.app')
@section('title', 'Add Ingredient')
@section('header', 'Add Ingredient')
@section('subheader', 'Configure a new raw ingredient, measurement unit, and low stock threshold')

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
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
            </div>
            <div>
                <h2 class="font-bold text-gray-900 text-base">Ingredient Master Record</h2>
                <p class="text-xs text-gray-400">Add an inventory item tracked by recipes and deliveries</p>
            </div>
        </div>

        <form method="POST" action="{{ route('ingredients.store') }}" class="space-y-5">
            @csrf
            <div>
                <label class="brand-label mb-1.5">Ingredient Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g., Whole Milk, Espresso Beans, Vanilla Syrup" class="brand-input" required autofocus>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="brand-label mb-1.5">Measurement Unit <span class="text-rose-500">*</span></label>
                    <select name="unit" class="brand-input" required>
                        <option value="">Select unit...</option>
                        @foreach(['ml'=>'ml (Milliliters)','g'=>'g (Grams)','kg'=>'kg (Kilograms)','L'=>'L (Liters)','pc'=>'pc (Pieces)','tbsp'=>'tbsp (Tablespoons)','tsp'=>'tsp (Teaspoons)'] as $key => $label)
                        <option value="{{ $key }}" {{ old('unit') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="brand-label mb-1.5">Minimum Threshold <span class="text-rose-500">*</span></label>
                    <input type="number" name="minimum_stock" value="{{ old('minimum_stock', 0) }}" min="0" step="0.01" class="brand-input font-semibold text-gray-800" required>
                </div>
                <div>
                    <label class="brand-label mb-1.5">Reorder Level</label>
                    <input type="number" name="reorder_level" value="{{ old('reorder_level', 0) }}" min="0" step="0.001" class="brand-input">
                </div>
                <div>
                    <label class="brand-label mb-1.5">Unit Cost (₱)</label>
                    <input type="number" name="cost" value="{{ old('cost', 0) }}" min="0" step="0.01" class="brand-input">
                </div>
                <div>
                    <label class="brand-label mb-1.5">Supplier</label>
                    <select name="supplier_id" class="brand-input">
                        <option value="">No default supplier</option>
                        @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="brand-label mb-1.5">Expiration Date</label>
                    <input type="date" name="expiration_date" value="{{ old('expiration_date') }}" class="brand-input">
                </div>
            </div>

            <div>
                <label class="brand-label mb-1.5">Catalog Status</label>
                <select name="status" class="brand-input">
                    <option value="active" {{ old('status','active') === 'active' ? 'selected' : '' }}>Unarchived (Available for Recipes & Deliveries)</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Archived (Unavailable for Recipes & Deliveries)</option>
                </select>
            </div>

            <div class="flex items-center gap-3 pt-3 border-t border-gray-100">
                <a href="{{ route('ingredients.index') }}" class="brand-btn-cancel flex-1 justify-center">
                    Cancel
                </a>
                <button type="submit" class="brand-button flex-1">
                    Save Ingredient
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
