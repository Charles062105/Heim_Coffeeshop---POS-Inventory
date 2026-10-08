@extends('layouts.app')
@section('title', 'Add-ons')
@section('header', 'Add-ons')
@section('subheader', 'Create, update, archive, or restore POS add-ons')

@section('header-actions')
    <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800">
        Archived add-ons are hidden from the POS
    </span>
@endsection

@section('content')
<div class="space-y-6">
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            <p class="font-semibold">Please correct the add-on or ingredient consumption details.</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif
    <section class="brand-card rounded-2xl p-5 shadow-sm">
        <h2 class="text-base font-bold text-gray-900">Add a new add-on</h2>
        <p class="mt-1 text-sm text-gray-500">New add-ons are immediately available on the POS.</p>
        <form method="POST" action="{{ route('addons.store') }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-[minmax(0,1fr)_12rem_auto] sm:items-end">
            @csrf
            <div>
                <label for="new-addon-name" class="mb-1.5 block text-xs font-bold text-gray-600">Name</label>
                <input id="new-addon-name" name="name" value="{{ old('name') }}" required maxlength="150"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-heim-500"
                    placeholder="e.g. Extra espresso shot">
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="new-addon-price" class="mb-1.5 block text-xs font-bold text-gray-600">Price (₱)</label>
                <input id="new-addon-price" name="price" type="number" min="0" step="0.01" value="{{ old('price', '0.00') }}" required
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-heim-500">
                @error('price')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="brand-button justify-center">Add add-on</button>
        </form>
    </section>

    <section class="brand-card overflow-hidden rounded-2xl shadow-sm">
        <form method="GET" class="flex flex-col gap-3 border-b border-gray-100 p-4 sm:flex-row sm:items-center">
            <input type="search" name="search" value="{{ request('search') }}" maxlength="255" placeholder="Search add-ons..."
                class="min-w-0 flex-1 rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-heim-500">
            <select name="status" class="rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-heim-500">
                <option value="">All add-ons</option>
                <option value="active" @selected(request('status') === 'active')>Unarchived</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Archived</option>
            </select>
            <button type="submit" class="brand-btn-filter justify-center">Filter</button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('addons.index') }}" class="brand-btn-reset justify-center">Clear</a>
            @endif
        </form>

        @if($addons->isEmpty())
            <div class="p-10 text-center text-sm text-gray-500">No add-ons match this filter.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Add-on</th>
                            <th class="px-5 py-3 font-semibold">Price</th>
                            <th class="px-5 py-3 font-semibold">Order history</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 text-right font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($addons as $addon)
                            <tr class="{{ $addon->status === 'inactive' ? 'bg-gray-50/70' : '' }}">
                                <td class="px-5 py-4">
                                    <form id="addon-update-{{ $addon->id }}" method="POST" action="{{ route('addons.update', $addon) }}">
                                        @csrf
                                        @method('PUT')
                                        <label class="sr-only" for="addon-name-{{ $addon->id }}">Name</label>
                                        <input id="addon-name-{{ $addon->id }}" name="name" value="{{ $addon->name }}" required maxlength="150"
                                            class="w-full max-w-sm rounded-lg border border-gray-200 bg-white px-3 py-2 font-semibold text-gray-800 focus:outline-none focus:ring-2 focus:ring-heim-500">
                                    </form>
                                </td>
                                <td class="px-5 py-4">
                                    <label class="sr-only" for="addon-price-{{ $addon->id }}">Price</label>
                                    <input form="addon-update-{{ $addon->id }}" id="addon-price-{{ $addon->id }}" name="price" type="number" min="0" step="0.01" value="{{ number_format((float) $addon->price, 2, '.', '') }}" required
                                        class="w-32 rounded-lg border border-gray-200 bg-white px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-heim-500">
                                </td>
                                <td class="px-5 py-4 text-gray-600">{{ $addon->order_item_addons_count }}</td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $addon->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-200 text-gray-600' }}">
                                        {{ $addon->status === 'active' ? 'Available' : 'Archived' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <button form="addon-update-{{ $addon->id }}" type="submit" class="rounded-lg border border-heim-200 px-3 py-2 text-xs font-semibold text-heim-800 hover:bg-heim-50">Save</button>
                                        <form method="POST" action="{{ route('addons.toggle', $addon) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-lg border px-3 py-2 text-xs font-semibold {{ $addon->status === 'active' ? 'border-amber-200 text-amber-800 hover:bg-amber-50' : 'border-emerald-200 text-emerald-800 hover:bg-emerald-50' }}">
                                                {{ $addon->status === 'active' ? 'Archive' : 'Unarchive' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr class="{{ $addon->status === 'inactive' ? 'bg-gray-50/70' : '' }}">
                                <td colspan="5" class="px-5 pb-4">
                                    <details class="rounded-xl border border-gray-200 bg-white">
                                        <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-heim-800">
                                            Ingredient consumption
                                            <span class="ml-1 text-xs font-normal text-gray-500">
                                                ({{ $addon->addonIngredients->count() }} mapped)
                                            </span>
                                        </summary>
                                        <form method="POST" action="{{ route('addons.ingredients.update', $addon) }}" class="space-y-3 border-t border-gray-100 p-4">
                                            @csrf
                                            @method('PUT')
                                            <p class="text-xs text-gray-500">Additive ingredients are deducted in addition to the recipe. A substitute deducts its mapped ingredient instead of the selected base-recipe ingredient. Use quantities in the ingredient's stock unit. Archived ingredients can remain on existing mappings but cannot be added to new mappings.</p>
                                            <div class="addon-ingredient-rows space-y-2">
                                                @foreach($addon->addonIngredients as $index => $mapping)
                                                    <div class="grid grid-cols-1 gap-2 rounded-lg bg-gray-50 p-3 sm:grid-cols-[minmax(0,1fr)_8rem_10rem_minmax(0,1fr)_auto] sm:items-center">
                                                        <select name="ingredients[{{ $index }}][ingredient_id]" required class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm">
                                                            <option value="">Consumed ingredient</option>
                                                            @foreach($ingredients as $ingredient)
                                                                <option value="{{ $ingredient->id }}" @selected($ingredient->id === $mapping->ingredient_id)>{{ $ingredient->name }} ({{ $ingredient->unit }}){{ $ingredient->status === 'inactive' ? ' — archived' : '' }}</option>
                                                            @endforeach
                                                        </select>
                                                        <input name="ingredients[{{ $index }}][quantity]" type="number" min="0.001" step="0.001" value="{{ number_format((float) $mapping->quantity, 3, '.', '') }}" required aria-label="Quantity" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm">
                                                        <select name="ingredients[{{ $index }}][mode]" onchange="toggleAddonReplacement(this)" class="addon-ingredient-mode rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm">
                                                            <option value="additive" @selected(! $mapping->replaces_ingredient_id)>Additive</option>
                                                            <option value="substitute" @selected((bool) $mapping->replaces_ingredient_id)>Substitute</option>
                                                        </select>
                                                        <select name="ingredients[{{ $index }}][replaces_ingredient_id]" class="addon-replacement-select rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm {{ $mapping->replaces_ingredient_id ? '' : 'hidden' }}">
                                                            <option value="">Replaces base ingredient</option>
                                                            @foreach($ingredients as $ingredient)
                                                                <option value="{{ $ingredient->id }}" @selected($ingredient->id === $mapping->replaces_ingredient_id)>{{ $ingredient->name }} ({{ $ingredient->unit }}){{ $ingredient->status === 'inactive' ? ' — archived' : '' }}</option>
                                                            @endforeach
                                                        </select>
                                                        <button type="button" onclick="this.closest('.addon-ingredient-rows > div').remove()" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Remove</button>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                <button type="button" onclick="addAddonIngredientRow(this)" class="rounded-lg border border-heim-200 px-3 py-2 text-xs font-semibold text-heim-800 hover:bg-heim-50">Add ingredient</button>
                                                <button type="submit" class="brand-button">Save consumption</button>
                                            </div>
                                        </form>
                                    </details>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-100 px-5 py-4">{{ $addons->links() }}</div>
        @endif
    </section>
</div>
<script>
    const addonIngredientOptions = @json($ingredients->map(fn ($ingredient) => [
        'id' => $ingredient->id,
        'name' => $ingredient->name,
        'unit' => $ingredient->unit,
        'inactive' => $ingredient->status === 'inactive',
    ])->values());

    function toggleAddonReplacement(modeSelect) {
        const replacement = modeSelect.closest('div').querySelector('.addon-replacement-select');
        const isSubstitute = modeSelect.value === 'substitute';
        replacement.classList.toggle('hidden', !isSubstitute);
        replacement.required = isSubstitute;
    }

    function addAddonIngredientRow(button) {
        const rows = button.closest('form').querySelector('.addon-ingredient-rows');
        const index = rows.children.length ? Math.max(...Array.from(rows.children, row => Number(row.dataset.index))) + 1 : 0;
        const options = addonIngredientOptions.map(ingredient =>
            `<option value="${ingredient.id}">${escapeAddonHtml(ingredient.name)} (${escapeAddonHtml(ingredient.unit)})${ingredient.inactive ? ' — archived' : ''}</option>`
        ).join('');
        const row = document.createElement('div');
        row.dataset.index = index;
        row.className = 'grid grid-cols-1 gap-2 rounded-lg bg-gray-50 p-3 sm:grid-cols-[minmax(0,1fr)_8rem_10rem_minmax(0,1fr)_auto] sm:items-center';
        row.innerHTML = `
            <select name="ingredients[${index}][ingredient_id]" required class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm"><option value="">Consumed ingredient</option>${options}</select>
            <input name="ingredients[${index}][quantity]" type="number" min="0.001" step="0.001" value="0.001" required aria-label="Quantity" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm">
            <select name="ingredients[${index}][mode]" onchange="toggleAddonReplacement(this)" class="addon-ingredient-mode rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm"><option value="additive">Additive</option><option value="substitute">Substitute</option></select>
            <select name="ingredients[${index}][replaces_ingredient_id]" class="addon-replacement-select hidden rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm"><option value="">Replaces base ingredient</option>${options}</select>
            <button type="button" onclick="this.closest('.addon-ingredient-rows > div').remove()" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Remove</button>
        `;
        rows.appendChild(row);
    }

    function escapeAddonHtml(value) {
        return String(value).replace(/[&<>"']/g, character => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
        })[character]);
    }

    document.querySelectorAll('.addon-ingredient-rows > div').forEach((row, index) => {
        row.dataset.index = index;
        const mode = row.querySelector('.addon-ingredient-mode');
        const replacement = row.querySelector('.addon-replacement-select');
        replacement.required = mode.value === 'substitute';
    });
</script>
@endsection
