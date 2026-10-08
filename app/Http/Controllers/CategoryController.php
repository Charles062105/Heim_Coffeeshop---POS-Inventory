<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\AuditService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')->orderBy('name')->get();

        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
            'status' => 'required|in:active,inactive',
        ]);

        $category = Category::create($data);

        AuditService::logFromUser($request->user(), 'created_category', 'Products', [
            'category' => $category->name,
        ], $category);

        return redirect()->route('categories.index')->with('success', "Category \"{$category->name}\" created.");
    }

    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name,'.$category->id,
            'status' => 'required|in:active,inactive',
        ]);

        $category->update($data);

        AuditService::logFromUser($request->user(), 'updated_category', 'Products', [
            'category' => $category->name,
        ], $category);

        return redirect()->route('categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->count() > 0) {
            return back()->with('error', 'Cannot delete a category that has products. Archive it instead.');
        }

        AuditService::logFromUser(request()->user(), 'deleted_category', 'Products', ['category' => $category->name]);
        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Category deleted.');
    }
}
