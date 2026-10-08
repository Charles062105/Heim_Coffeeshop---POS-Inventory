@extends('layouts.app')
@section('title', 'Add Product')
@section('header', 'Add Product')
@section('subheader', 'Add a new beverage or menu offering with custom sizes and prices')

@section('header-actions')
    <a href="{{ route('products.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 shadow-xs transition-all">
        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Products
    </a>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="brand-card rounded-2xl p-6 shadow-sm">
        <div class="flex items-center gap-3 pb-4 mb-5 border-b border-gray-100">
            <div class="w-10 h-10 rounded-xl bg-heim-50 border border-heim-100 flex items-center justify-center text-heim-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </div>
            <div>
                <h2 class="font-bold text-gray-900 text-base">Product Details</h2>
                <p class="text-xs text-gray-400">Set drink identity, category, and sizing options</p>
            </div>
        </div>

        <form method="POST" action="{{ route('products.store') }}" class="space-y-5">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="col-span-1 sm:col-span-2">
                    <label class="brand-label mb-1.5">Product Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g., Spanish Latte, Sea Salt Cold Brew" class="brand-input" required autofocus>
                </div>
                <div>
                    <label class="brand-label mb-1.5">Category <span class="text-rose-500">*</span></label>
                    <select name="category_id" required class="brand-input">
                        <option value="">Select category...</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="brand-label mb-1.5">Status</label>
                    <select name="status" class="brand-input">
                        <option value="active">Unarchived (Available at POS)</option>
                        <option value="inactive">Archived (Unavailable at POS)</option>
                    </select>
                </div>
                <div class="col-span-1 sm:col-span-2">
                    <label class="brand-label mb-1.5">Description</label>
                    <textarea name="description" rows="2" placeholder="Brief tasting notes or preparation description..." class="brand-input resize-none">{{ old('description') }}</textarea>
                </div>
            </div>

            {{-- Sizes --}}
            <div class="border-t border-gray-100 pt-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <label class="brand-label">Sizes & Pricing <span class="text-rose-500">*</span></label>
                        <p class="text-xs text-gray-400 mt-0.5">Set a separate optional GrabFood price for each size. Leave it blank to use the regular price.</p>
                    </div>
                    <button type="button" onclick="addSizeRow()" class="inline-flex items-center gap-1 text-xs bg-heim-50 text-heim-700 hover:bg-heim-100 border border-heim-200/60 px-3 py-1.5 rounded-xl font-semibold transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        <span>Add Size</span>
                    </button>
                </div>

                <div id="sizes-container" class="space-y-2.5">
                    <div class="size-row flex items-center gap-2.5 p-2 rounded-xl bg-gray-50/60 border border-gray-100 flex-wrap sm:flex-nowrap">
                        <input type="text" name="sizes[0][size_name]" placeholder="e.g., Hot 8oz, Iced 16oz" class="flex-1 min-w-[120px] brand-input" required>
                        <div class="relative w-28" title="Regular Dine-In / Take-Out price">
                            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-xs font-bold text-gray-400">₱</span>
                            <input type="number" name="sizes[0][price]" placeholder="Reg 0.00" min="0" step="0.01" class="brand-input pl-6 font-bold text-gray-800 text-xs" required>
                        </div>
                        <div class="relative w-28" title="Optional GrabFood price. Leave blank to use the regular price.">
                            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-xs font-bold text-emerald-600">₱</span>
                            <input type="number" name="sizes[0][grab_price]" placeholder="GrabFood" aria-label="Optional GrabFood price" min="0" step="0.01" class="brand-input pl-6 font-bold text-emerald-700 text-xs">
                        </div>
                        <select name="sizes[0][status]" class="w-24 brand-input text-xs font-medium">
                            <option value="active">Unarchived</option>
                            <option value="inactive">Archived</option>
                        </select>
                        <button type="button" onclick="this.closest('.size-row').remove()" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 flex-shrink-0 transition-colors" title="Remove size">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-3 border-t border-gray-100">
                <a href="{{ route('products.index') }}" class="brand-btn-cancel flex-1 justify-center">
                    Cancel
                </a>
                <button type="submit" class="brand-button flex-1">
                    Save Product
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
let sizeIdx = 1;
function addSizeRow() {
    const container = document.getElementById('sizes-container');
    const row = document.createElement('div');
    row.className = 'size-row flex items-center gap-2.5 p-2 rounded-xl bg-gray-50/60 border border-gray-100 flex-wrap sm:flex-nowrap';
    row.innerHTML = `
        <input type="text" name="sizes[${sizeIdx}][size_name]" placeholder="e.g., Iced 22oz" class="flex-1 min-w-[120px] brand-input" required>
        <div class="relative w-28" title="Regular Dine-In / Take-Out price">
            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-xs font-bold text-gray-400">₱</span>
            <input type="number" name="sizes[${sizeIdx}][price]" placeholder="Reg 0.00" min="0" step="0.01" class="brand-input pl-6 font-bold text-gray-800 text-xs" required>
        </div>
        <div class="relative w-28" title="Optional GrabFood price. Leave blank to use the regular price.">
            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-xs font-bold text-emerald-600">₱</span>
            <input type="number" name="sizes[${sizeIdx}][grab_price]" placeholder="GrabFood" aria-label="Optional GrabFood price" min="0" step="0.01" class="brand-input pl-6 font-bold text-emerald-700 text-xs">
        </div>
        <select name="sizes[${sizeIdx}][status]" class="w-24 brand-input text-xs font-medium">
            <option value="active">Unarchived</option>
            <option value="inactive">Archived</option>
        </select>
        <button type="button" onclick="this.closest('.size-row').remove()" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 flex-shrink-0 transition-colors" title="Remove size">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>`;
    container.appendChild(row);
    sizeIdx++;
}
</script>
@endpush
@endsection
