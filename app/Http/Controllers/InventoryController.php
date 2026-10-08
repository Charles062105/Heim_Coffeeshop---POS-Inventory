<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Services\ExportService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'stock_status' => ['nullable', 'in:good,low_stock,out_of_stock'],
        ]);
        $query = Ingredient::with(['inventory', 'supplier'])->orderBy('name');

        if ($search = $filters['search'] ?? null) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Stock status filter
        $stockFilter = $filters['stock_status'] ?? null;
        $allIngredients = $query->get();

        $good = $allIngredients->filter(fn ($i) => $i->getStockStatus() === 'good')->count();
        $low = $allIngredients->filter(fn ($i) => $i->getStockStatus() === 'low_stock')->count();
        $outOfStock = $allIngredients->filter(fn ($i) => $i->getStockStatus() === 'out_of_stock')->count();

        $ingredients = $stockFilter
            ? $allIngredients->filter(fn ($i) => $i->getStockStatus() === $stockFilter)->values()
            : $allIngredients;

        if ($request->get('export') === 'excel') {
            abort_if(! $request->user()?->canExportOrPrint(), 403, 'Only managers and owners can export reports.');
            $filename = 'stock-overview-'.now(config('app.business_timezone', 'Asia/Manila'))->format('Y-m-d').'.csv';
            $columns = [
                'Ingredient ID',
                'Ingredient Name',
                'Current Stock',
                'Unit',
                'Reorder Threshold',
                'Minimum Stock',
                'Unit Cost',
                'Supplier',
                'Expiration Date',
                'Stock Status',
                'Status',
            ];

            $rows = $ingredients->map(function ($ing) {
                $status = match ($ing->getStockStatus()) {
                    'out_of_stock' => 'Out of Stock',
                    'low_stock' => 'Low Stock',
                    default => 'Good Stock',
                };
                $stock = (float) $ing->getCurrentStock();
                $reorderLevel = (float) ($ing->reorder_level ?: $ing->minimum_stock);

                return [
                    $ing->id,
                    $ing->name,
                    number_format($stock, 3, '.', ''),
                    $ing->unit,
                    number_format($reorderLevel, 3, '.', ''),
                    number_format((float) $ing->minimum_stock, 3, '.', ''),
                    number_format((float) $ing->cost, 2, '.', ''),
                    $ing->supplier?->name ?? '',
                    $ing->expiration_date?->format('Y-m-d') ?? '',
                    $status,
                    $ing->status === 'active' ? 'Active' : 'Inactive',
                ];
            });

            return ExportService::streamCsv($filename, $columns, $rows);
        }

        return view('inventory.index', compact('ingredients', 'good', 'low', 'outOfStock', 'stockFilter'));
    }

    public function transactions(Request $request)
    {
        return app(StockAdjustmentController::class)->index($request);
    }
}
