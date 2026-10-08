@extends('layouts.app')
@section('title', 'Edit Recipe')
@section('header', 'Edit Recipe Formulation')
@section('subheader', 'Configure raw ingredients and deducted quantities per cup serving')

@section('header-actions')
    <a href="{{ route('recipes.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 shadow-xs transition-all">
        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Recipes
    </a>
@endsection

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    {{-- Product & Size Info Card --}}
    <div class="brand-card rounded-2xl p-5 shadow-sm flex items-center justify-between">
        <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-heim-100 text-heim-800 flex items-center justify-center font-bold text-base shadow-xs">
                {{ strtoupper(substr($productSize->product->name, 0, 1)) }}
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="font-bold text-gray-900 text-base leading-tight">{{ $productSize->product->name }}</h2>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-heim-50 text-heim-700 border border-heim-100">
                        {{ $productSize->size_name }}
                    </span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5">
                    Category: <span class="font-medium text-gray-700">{{ $productSize->product->category?->name ?? 'Uncategorized' }}</span> · Menu Price: <strong class="text-heim-800 font-bold">₱{{ number_format($productSize->price, 2) }}</strong>
                </p>
            </div>
        </div>
    </div>

    {{-- Recipe Form --}}
    <div class="brand-card rounded-2xl shadow-sm p-6">
        <form method="POST" action="{{ route('recipes.update', $productSize) }}" class="space-y-6">
            @csrf @method('PUT')

            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Recipe Formulation Title</label>
                <input type="text" name="name" value="{{ old('name', $recipe?->name ?? $productSize->product->name . ' (' . $productSize->size_name . ')') }}"
                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 focus:border-transparent bg-white shadow-sm" placeholder="e.g., Iced Latte (16oz)">
            </div>

            <div class="border-t border-gray-100 pt-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <label class="text-xs font-bold text-gray-600 uppercase tracking-wider">Ingredients & Quantities</label>
                        <p class="text-xs text-gray-400 mt-0.5">Deducted automatically from stock whenever this item is ordered.</p>
                    </div>
                    <button type="button" onclick="addIngredientRow()" class="inline-flex items-center gap-1.5 text-xs bg-heim-50 text-heim-700 hover:bg-heim-100 px-3 py-1.5 rounded-xl font-semibold transition-colors border border-heim-200/60">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Add Ingredient</span>
                    </button>
                </div>

                {{-- Column Headers --}}
                <div class="grid grid-cols-12 gap-2.5 px-3 py-2 bg-gray-50/80 rounded-xl text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2">
                    <div class="col-span-7">Ingredient</div>
                    <div class="col-span-4">Qty per Serving</div>
                    <div class="col-span-1 text-center">Action</div>
                </div>

                <div id="ingredients-container" class="space-y-2">
                    @php $existing = $recipe?->recipeIngredients ?? collect(); @endphp
                    @forelse($existing as $i => $ri)
                    <div class="ingredient-row grid grid-cols-12 gap-2.5 items-center p-2 rounded-xl bg-gray-50/40 border border-gray-100 hover:bg-gray-50/80 transition-colors">
                        <div class="col-span-7">
                            <select name="ingredients[{{ $i }}][ingredient_id]" class="w-full border border-gray-200 bg-white rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 shadow-sm" required>
                                <option value="">Select ingredient...</option>
                                @foreach($ingredients as $ing)
                                <option value="{{ $ing->id }}" {{ $ri->ingredient_id == $ing->id ? 'selected' : '' }}>{{ $ing->name }} ({{ $ing->unit }}){{ $ing->status === 'inactive' ? ' — Archived' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-4">
                            <input type="number" name="ingredients[{{ $i }}][quantity]" value="{{ $ri->quantity }}" min="0.001" step="0.001"
                                placeholder="Qty (e.g. 18)" class="w-full border border-gray-200 bg-white rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 shadow-sm font-medium" required>
                        </div>
                        <div class="col-span-1 flex justify-center">
                            <button type="button" onclick="this.closest('.ingredient-row').remove()" class="text-gray-400 hover:text-red-600 p-1.5 rounded-lg hover:bg-red-50 transition-colors" title="Remove ingredient">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                    @empty
                    @endforelse
                </div>

                @if($existing->isEmpty())
                <div id="no-ingredients" class="text-center py-8 border-2 border-dashed border-gray-200 rounded-xl my-2">
                    <p class="text-xs text-gray-500 font-medium">No ingredients added yet.</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">Click "Add Ingredient" above to specify ingredients used in this drink.</p>
                </div>
                @endif
            </div>

            <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
                <a href="{{ route('recipes.index') }}" class="brand-btn-cancel flex-1 text-center">
                    Cancel
                </a>
                <button type="submit" class="brand-button flex-1">
                    Save Recipe Formulation
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const ingredientOptions = @js($ingredients->map(fn ($ing) => [
    'id' => $ing->id,
    'name' => $ing->name,
    'unit' => $ing->unit,
])->values());
let rowIdx = {{ max(1, count($recipe?->recipeIngredients ?? [])) }};

function addIngredientRow() {
    document.getElementById('no-ingredients')?.remove();
    const container = document.getElementById('ingredients-container');
    const opts = ingredientOptions.map(i => `<option value="${i.id}">${i.name} (${i.unit})</option>`).join('');
    const row = document.createElement('div');
    row.className = 'ingredient-row grid grid-cols-12 gap-2.5 items-center p-2 rounded-xl bg-gray-50/40 border border-gray-100 hover:bg-gray-50/80 transition-colors';
    row.innerHTML = `
        <div class="col-span-7">
            <select name="ingredients[${rowIdx}][ingredient_id]" class="w-full border border-gray-200 bg-white rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 shadow-sm" required>
                <option value="">Select ingredient...</option>${opts}
            </select>
        </div>
        <div class="col-span-4">
            <input type="number" name="ingredients[${rowIdx}][quantity]" min="0.001" step="0.001" placeholder="Qty"
                class="w-full border border-gray-200 bg-white rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-heim-500 shadow-sm font-medium" required>
        </div>
        <div class="col-span-1 flex justify-center">
            <button type="button" onclick="this.closest('.ingredient-row').remove()" class="text-gray-400 hover:text-red-600 p-1.5 rounded-lg hover:bg-red-50 transition-colors" title="Remove ingredient">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </div>`;
    container.appendChild(row);
    rowIdx++;
}
</script>
@endpush
@endsection
