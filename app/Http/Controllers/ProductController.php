<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSize;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);
        $query = Product::with(['category', 'sizes'])
            ->orderBy('name');

        if ($search = $filters['search'] ?? null) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($categoryId = $filters['category_id'] ?? null) {
            $query->where('category_id', $categoryId);
        }
        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }

        $products = $query->paginate(20)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = Category::where('status', 'active')->orderBy('name')->get();

        return view('products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => ['required', Rule::exists('categories', 'id')->where('status', 'active')],
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'sizes' => 'required|array|min:1',
            'sizes.*.size_name' => 'required|string|max:50',
            'sizes.*.price' => 'required|numeric|decimal:0,2|min:0',
            'sizes.*.grab_price' => 'nullable|numeric|decimal:0,2|min:0',
            'sizes.*.status' => 'required|in:active,inactive',
        ]);

        $product = Product::create($request->only('category_id', 'name', 'description', 'status'));

        foreach ($request->sizes as $sizeData) {
            $product->sizes()->create([
                'size_name' => $sizeData['size_name'],
                'price' => $sizeData['price'],
                'grab_price' => $sizeData['grab_price'] ?? null,
                'status' => $sizeData['status'],
            ]);
        }

        AuditService::logFromUser($request->user(), 'created_product', 'Products', [
            'product' => $product->name,
            'sizes' => count($request->sizes),
            'category' => $product->category->name,
        ], $product);

        return redirect()->route('products.index')->with('success', "Product \"{$product->name}\" created.");
    }

    public function edit(Product $product)
    {
        $product->load(['sizes', 'category']);
        $categories = Category::where('status', 'active')
            ->orWhere('id', $product->category_id)
            ->orderBy('name')
            ->get();

        return view('products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where(fn ($query) => $query
                    ->where('status', 'active')
                    ->orWhere('id', $product->category_id)),
            ],
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'sizes' => 'array',
            'sizes.*.id' => 'nullable|exists:product_sizes,id',
            'sizes.*.size_name' => 'required|string|max:50',
            'sizes.*.price' => 'required|numeric|decimal:0,2|min:0',
            'sizes.*.grab_price' => 'nullable|numeric|decimal:0,2|min:0',
            'sizes.*.status' => 'required|in:active,inactive',
        ]);

        foreach ($request->input('sizes', []) as $index => $sizeData) {
            if (! empty($sizeData['id']) && ! $product->sizes()->whereKey($sizeData['id'])->exists()) {
                throw ValidationException::withMessages([
                    "sizes.$index.id" => 'The selected size does not belong to this product.',
                ]);
            }
            if (! empty($sizeData['id']) && $product->sizes()->whereKey($sizeData['id'])->value('size_name') !== $sizeData['size_name']) {
                throw ValidationException::withMessages([
                    "sizes.$index.size_name" => 'Existing size labels cannot be changed. Add a new size instead.',
                ]);
            }
        }

        $product->update($request->only('category_id', 'name', 'description', 'status'));

        if ($request->has('sizes')) {
            foreach ($request->sizes as $sizeData) {
                $payload = [
                    'size_name' => $sizeData['size_name'],
                    'price' => $sizeData['price'],
                    'grab_price' => $sizeData['grab_price'] ?? null,
                    'status' => $sizeData['status'],
                ];
                if (! empty($sizeData['id'])) {
                    $product->sizes()->find($sizeData['id'])?->update($payload);
                } else {
                    $product->sizes()->create($payload);
                }
            }
        }

        AuditService::logFromUser($request->user(), 'updated_product', 'Products', ['product' => $product->name], $product);

        return redirect()->route('products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        // Never hard-delete products with order history — archive instead
        $hasOrders = OrderItem::where('product_id', $product->id)->exists();
        if ($hasOrders) {
            $product->update(['status' => 'inactive']);
            AuditService::logFromUser(request()->user(), 'archived_product', 'Products', ['product' => $product->name], $product);

            return redirect()->route('products.index')->with('success', 'Product archived because it has order history and cannot be permanently deleted.');
        }

        AuditService::logFromUser(request()->user(), 'deleted_product', 'Products', ['product' => $product->name]);
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted.');
    }

    public function toggle(Product $product)
    {
        $product->update(['status' => $product->status === 'active' ? 'inactive' : 'active']);
        $status = $product->status === 'active' ? 'unarchived' : 'archived';
        AuditService::logFromUser(request()->user(), 'toggled_product_status', 'Products', [
            'product' => $product->name, 'status' => $product->status,
        ], $product);

        return back()->with('success', "Product \"{$product->name}\" is now {$status}.");
    }

    public function destroySize(Product $product, ProductSize $size)
    {
        abort_unless($size->product_id === $product->id, 404);

        $hasOrders = OrderItem::where('product_size_id', $size->id)->exists();
        if ($hasOrders) {
            return back()->with('error', 'Cannot delete a size with existing orders. Archive it instead.');
        }
        $size->delete();

        return back()->with('success', 'Size removed.');
    }
}
