@extends('layouts.app')
@section('title', 'Add Category')
@section('header', 'Add Category')
@section('subheader', 'Create a new category for grouping beverages and menu items')

@section('header-actions')
    <a href="{{ route('categories.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 shadow-xs transition-all">
        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Categories
    </a>
@endsection

@section('content')
<div class="max-w-lg mx-auto">
    <div class="brand-card rounded-2xl p-6 shadow-sm">
        <div class="flex items-center gap-3 pb-4 mb-5 border-b border-gray-100">
            <div class="w-10 h-10 rounded-xl bg-heim-50 border border-heim-100 flex items-center justify-center text-heim-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
            <div>
                <h2 class="font-bold text-gray-900 text-base">Category Details</h2>
                <p class="text-xs text-gray-400">Configure menu category properties</p>
            </div>
        </div>

        <form method="POST" action="{{ route('categories.store') }}" class="space-y-5">
            @csrf
            <div>
                <label class="brand-label mb-1.5">Category Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g., Cold Brew, Pastries" class="brand-input" required autofocus>
            </div>

            <div>
                <label class="brand-label mb-1.5">Catalog Status</label>
                <select name="status" class="brand-input">
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Unarchived (Visible at POS)</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Archived (Hidden)</option>
                </select>
            </div>

            <div class="flex items-center gap-3 pt-3 border-t border-gray-100">
                <a href="{{ route('categories.index') }}" class="brand-btn-cancel flex-1 justify-center">
                    Cancel
                </a>
                <button type="submit" class="brand-button flex-1">
                    Save Category
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
