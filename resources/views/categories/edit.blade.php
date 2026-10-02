@extends('layouts.app')
@section('title', 'Edit Category')
@section('header', 'Edit Category')
@section('subheader', 'Update menu group name and catalog visibility status')

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
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div>
                <h2 class="font-bold text-gray-900 text-base">Edit Category</h2>
                <p class="text-xs text-gray-400">Modify properties for {{ $category->name }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('categories.update', $category) }}" class="space-y-5">
            @csrf @method('PUT')
            <div>
                <label class="brand-label mb-1.5">Category Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $category->name) }}" class="brand-input" required autofocus>
            </div>

            <div>
                <label class="brand-label mb-1.5">Catalog Status</label>
                <select name="status" class="brand-input">
                    <option value="active" {{ old('status', $category->status) === 'active' ? 'selected' : '' }}>Unarchived (Visible at POS)</option>
                    <option value="inactive" {{ old('status', $category->status) === 'inactive' ? 'selected' : '' }}>Archived (Hidden)</option>
                </select>
            </div>

            <div class="flex items-center gap-3 pt-3 border-t border-gray-100">
                <a href="{{ route('categories.index') }}" class="brand-btn-cancel flex-1 justify-center">
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
