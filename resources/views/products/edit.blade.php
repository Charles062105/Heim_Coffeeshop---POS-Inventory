@extends('layouts.app')
@section('title', 'Edit Product')
@section('header', 'Edit Product')
@section('subheader', 'Update beverage information, category assignment, and pricing tiers')

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
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div>
                <h2 class="font-bold text-gray-900 text-base">Edit Product</h2>
                <p class="text-xs text-gray-400">Modify properties for {{ $product->name }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('products.update', $product) }}" class="space-y-5">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="col-span-1 sm:col-span-2">
                    <label class="brand-label mb-1.5">Product Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" class="brand-input" required autofocus>
                </div>
                <div>
                    <label class="brand-label mb-1.5">Category <span class="text-rose-500">*</span></label>
                    <select name="category_id" required class="brand-input">
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>                        {{ $cat->name }}{{ $cat->status === 'inactive' ? ' (Archived - choose an unarchived category)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="brand-label mb-1.5">Status</label>
                    <select name="status" class="brand-input">
                        <option value="active" {{ old('status', $product->status) === 'active' ? 'selected' : '' }}>Unarchived (Available at POS)</option>
                        <option value="inactive" {{ old('status', $product->status) === 'inactive' ? 'selected' : '' }}>Archived (Unavailable at POS)</option>
                    </select>
                </div>
                <div class="col-span-1 sm:col-span-2">
                    <label class="brand-label mb-1.5">Description</label>
                    <textarea name="description" rows="2" class="brand-input resize-none">{{ old('description', $product->description) }}</textarea>
                </div>
            </div>

            {{-- Existing sizes --}}
            <div class="border-t border-gray-100 pt-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <label class="brand-label">Configured Sizes & Prices</label>
                        <p class="text-xs text-gray-400 mt-0.5">Existing size labels are fixed; use Add Size for another size or temperature. Prices remain editable.</p>
                    </div>
                    <button type="button" onclick="addSizeRow()" class="inline-flex items-center gap-1 text-xs bg-heim-50 text-heim-700 hover:bg-heim-100 border border-heim-200/60 px-3 py-1.5 rounded-xl font-semibold transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        <span>Add Size</span>
                    </button>
                </div>

                <div id="sizes-container" class="space-y-2.5">
                    @foreach($product->sizes as $i => $size)
                    <div class="size-row flex items-center gap-2.5 p-2 rounded-xl bg-gray-50/70 border border-gray-100">
                        <input type="hidden" name="sizes[{{ $i }}][id]" value="{{ $size->id }}">
                        <input type="text" name="sizes[{{ $i }}][size_name]" value="{{ $size->size_name }}" class="flex-1 brand-input bg-gray-100 text-gray-500" required readonly aria-label="Fixed size label: {{ $size->size_name }}" title="Existing size labels are fixed. Use Add Size to create another size or temperature.">
                        <div class="relative w-28">
                            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-xs font-bold text-gray-400">₱</span>
                            <input type="number" name="sizes[{{ $i }}][price]" value="{{ $size->price }}" min="0" step="0.01" class="brand-input pl-6 font-bold text-gray-800 text-xs" required placeholder="Reg Price">
                        </div>
                        <div class="relative w-28" title="Optional GrabFood price. Leave blank to use the regular price.">
                            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-xs font-bold text-emerald-600">₱</span>
                            <input type="number" name="sizes[{{ $i }}][grab_price]" value="{{ $size->grab_price }}" aria-label="GrabFood price for {{ $size->size_name }}" min="0" step="0.01" class="brand-input pl-6 font-bold text-emerald-800 text-xs" placeholder="GrabFood">
                        </div>
                        <select name="sizes[{{ $i }}][status]" class="w-24 brand-input text-xs font-medium">
                            <option value="active" {{ $size->status === 'active' ? 'selected' : '' }}>Unarchived</option>
                            <option value="inactive" {{ $size->status === 'inactive' ? 'selected' : '' }}>Archived</option>
                        </select>
                        <form method="POST" action="{{ route('products.sizes.destroy', [$product, $size]) }}"
                            data-confirm="Delete this size? This cannot be undone if it has no orders."
                            data-confirm-title="Delete product size">
                            @csrf @method('DELETE')
                            <button type="submit"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 flex-shrink-0 transition-colors"
                                title="Delete size">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                    @endforeach
                </div>

                <div id="new-sizes" class="mt-2.5 space-y-2.5"></div>
            </div>

            <div class="flex items-center gap-3 pt-3 border-t border-gray-100">
                <a href="{{ route('products.index') }}" class="brand-btn-cancel flex-1 justify-center">
                    Cancel
                </a>
                <button type="submit" class="brand-button flex-1">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
let newIdx = {{ $product->sizes->count() }};
function addSizeRow() {
    const row = document.createElement('div');
    row.className = 'size-row flex items-center gap-2.5 p-2 rounded-xl bg-gray-50/70 border border-gray-100';
    row.innerHTML = `
        <input type="text" name="sizes[${newIdx}][size_name]" placeholder="New size name" class="flex-1 brand-input" required>
        <div class="relative w-28">
            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-xs font-bold text-gray-400">₱</span>
            <input type="number" name="sizes[${newIdx}][price]" placeholder="Reg 0.00" min="0" step="0.01" class="brand-input pl-6 font-bold text-gray-800 text-xs" required>
        </div>
        <div class="relative w-28" title="Optional GrabFood price. Leave blank to use the regular price.">
            <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-xs font-bold text-emerald-600">₱</span>
            <input type="number" name="sizes[${newIdx}][grab_price]" placeholder="GrabFood" aria-label="Optional GrabFood price" min="0" step="0.01" class="brand-input pl-6 font-bold text-emerald-800 text-xs">
        </div>
        <select name="sizes[${newIdx}][status]" class="w-24 brand-input text-xs font-medium">
            <option value="active">Unarchived</option>
            <option value="inactive">Archived</option>
        </select>
        <button type="button" onclick="this.closest('.size-row').remove()" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 flex-shrink-0 transition-colors" title="Remove">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>`;
    document.getElementById('new-sizes').appendChild(row);
    newIdx++;
}
</script>
@endpush
@endsection
