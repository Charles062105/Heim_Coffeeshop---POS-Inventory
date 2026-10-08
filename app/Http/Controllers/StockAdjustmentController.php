<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\Supplier;
use App\Services\AuditService;
use App\Services\ExportService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $types = ['stock_in', 'sales_consumption', 'sales_return', 'waste', 'adjustment_add', 'adjustment_deduct'];
        $filters = $request->validate([
            'type' => ['nullable', 'in:'.implode(',', $types)],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'ingredient_id' => ['nullable', 'integer', 'exists:ingredients,id'],
        ]);

        $query = InventoryTransaction::with(['ingredient', 'supplier'])
            ->orderByRaw('COALESCE(transaction_date, created_at) DESC')
            ->orderByDesc('created_at');

        if ($type = $filters['type'] ?? null) {
            $query->where('type', $type);
        }
        if ($ingredientId = $filters['ingredient_id'] ?? null) {
            $query->where('ingredient_id', $ingredientId);
        }
        if ($from = $filters['from'] ?? null) {
            $query->effectiveDateFrom($from);
        }
        if ($to = $filters['to'] ?? null) {
            $query->effectiveDateTo($to);
        }

        if ($request->get('export') === 'excel') {
            abort_if(! $request->user()?->canExportOrPrint(), 403, 'Only managers and owners can export reports.');
            $idLabel = 'Transaction ID';
            $filename = 'stock-movements-'.now(config('app.business_timezone', 'Asia/Manila'))->format('Y-m-d').'.csv';
            $columns = [
                $idLabel,
                'Movement Date',
                'Recorded At',
                'Unit Cost',
                'Expiration Date',
                'Ingredient',
                'Transaction Type',
                'Quantity',
                'Unit',
                'Supplier / Vendor',
                'Ref / Invoice #',
                'Previous Stock',
                'New Stock',
                'Reason / Notes',
                'Performed By',
                'Role',
            ];

            $rows = $query->get()->map(function ($tx) {
                $isAdd = in_array($tx->type, ['adjustment_add', 'stock_in', 'sales_return']);

                return [
                    $tx->id,
                    $tx->transaction_date?->format('Y-m-d')
                        ?? $tx->created_at?->copy()->timezone(config('app.business_timezone', 'Asia/Manila'))->format('Y-m-d')
                        ?? '',
                    $tx->created_at?->copy()->timezone(config('app.business_timezone', 'Asia/Manila'))->format('Y-m-d H:i:s') ?? '',
                    $tx->unit_cost !== null ? number_format((float) $tx->unit_cost, 2, '.', '') : '',
                    $tx->expiration_date?->format('Y-m-d') ?? '',
                    $tx->ingredient->name ?? 'Deleted Item',
                    $tx->getTypeLabel(),
                    ($isAdd ? '+' : '-').number_format((float) $tx->quantity, 3, '.', ''),
                    $tx->ingredient->unit ?? '',
                    $tx->supplier->name ?? '',
                    $tx->reference_number ?? '',
                    number_format((float) $tx->previous_stock, 3, '.', ''),
                    number_format((float) $tx->new_stock, 3, '.', ''),
                    $tx->reason ?? '',
                    $tx->performed_by ?? 'System',
                    ucfirst($tx->performed_role ?? 'Staff'),
                ];
            });

            return ExportService::streamCsv($filename, $columns, $rows);
        }

        $summaryQuery = (clone $query)->reorder();
        $summary = [
            'records' => (clone $summaryQuery)->count(),
            'additions' => (clone $summaryQuery)
                ->join('ingredients', 'inventory_transactions.ingredient_id', '=', 'ingredients.id')
                ->whereIn('inventory_transactions.type', ['stock_in', 'adjustment_add', 'sales_return'])
                ->select('ingredients.unit', DB::raw('SUM(inventory_transactions.quantity) as total'))
                ->groupBy('ingredients.unit')
                ->orderBy('ingredients.unit')
                ->get(),
            'deductions' => (clone $summaryQuery)
                ->join('ingredients', 'inventory_transactions.ingredient_id', '=', 'ingredients.id')
                ->whereIn('inventory_transactions.type', ['sales_consumption', 'waste', 'adjustment_deduct'])
                ->select('ingredients.unit', DB::raw('SUM(inventory_transactions.quantity) as total'))
                ->groupBy('ingredients.unit')
                ->orderBy('ingredients.unit')
                ->get(),
        ];

        $transactions = $request->get('print') === 'all'
            ? (abort_if(! $request->user()?->canExportOrPrint(), 403, 'Only managers and owners can print reports.') ?: $query->get())
            : $query->paginate(20)->withQueryString();

        $ingredients = Ingredient::with('inventory')->orderBy('name')->get();
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();

        // $reportType is passed to the view so the template can detect it even
        // when delegated from WasteController / StockInController (where the
        // 'type' param is merged into the request object, not the query string).
        $reportType = $request->get('type');

        return view('inventory.adjustments', compact('transactions', 'ingredients', 'suppliers', 'reportType', 'summary', 'types'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->canManageInventory(), 403, 'Inventory changes require manager or owner authorization.');

        $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'type' => 'required|in:adjustment_add,adjustment_deduct,stock_in,waste',
            'quantity' => 'required|numeric|decimal:0,3|min:0.001',
            'reason' => 'nullable|string|max:500',
            'waste_reason' => 'required_if:type,waste|in:Waste,Spoilage,Expired,Damaged,Adjustment,Other',
            'notes' => 'nullable|string|max:500',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'reference_number' => 'nullable|string|max:100',
            'unit_cost' => 'required_if:type,stock_in|nullable|numeric|decimal:0,2|min:0',
            'transaction_date' => 'required_if:type,stock_in|nullable|date',
            'expiration_date' => 'nullable|date|after_or_equal:transaction_date',
        ]);

        if ($request->type === 'stock_in' && Ingredient::whereKey($request->ingredient_id)->where('status', 'inactive')->exists()) {
            throw ValidationException::withMessages([
                'ingredient_id' => 'Unarchive this ingredient before recording a new stock-in delivery.',
            ]);
        }

        if (in_array($request->type, ['adjustment_add', 'adjustment_deduct']) && empty($request->reason)) {
            return back()->withErrors(['reason' => 'Reason / Notes is required for this transaction type.'])->withInput();
        }

        $reason = $request->type === 'waste' ? $request->waste_reason : $request->reason;
        if ($request->filled('notes')) {
            $reason = trim(($reason ? $reason.': ' : '').$request->notes);
        }

        return DB::transaction(function () use ($request, $reason) {
            $ingredient = Ingredient::with('inventory')->findOrFail($request->ingredient_id);
            $user = $request->user();
            $quantity = (float) $request->quantity;

            if ($request->type === 'stock_in') {
                $ingredient->update(array_filter([
                    'supplier_id' => $request->supplier_id,
                    'cost' => $request->unit_cost,
                    'expiration_date' => $request->expiration_date,
                ], fn ($value) => $value !== null));

                $tx = InventoryService::addStock(
                    ingredient: $ingredient,
                    quantity: $quantity,
                    performedBy: $user->name,
                    performedRole: $user->role,
                    reason: $reason ?? 'Stock-in delivery',
                    supplierId: $request->supplier_id,
                    referenceNumber: $request->reference_number,
                    unitCost: (float) $request->unit_cost,
                    transactionDate: $request->transaction_date,
                    expirationDate: $request->expiration_date
                );

                AuditService::logFromUser($user, 'stock_in', 'Inventory', [
                    'ingredient' => $ingredient->name,
                    'quantity' => $quantity,
                    'unit' => $ingredient->unit,
                    'previous_stock' => $tx->previous_stock,
                    'new_stock' => $tx->new_stock,
                ], $tx);

                return $this->movementRedirect($request, 'stock_in')
                    ->with('success', "Stock-In recorded successfully: +{$request->quantity} {$ingredient->unit} for {$ingredient->name}.");
            }

            if ($request->type === 'waste') {
                $tx = InventoryService::recordWaste(
                    ingredient: $ingredient,
                    quantity: $quantity,
                    reason: $reason,
                    performedBy: $user->name,
                    performedRole: $user->role
                );

                AuditService::logFromUser($user, 'waste_recorded', 'Inventory', [
                    'ingredient' => $ingredient->name,
                    'quantity' => $quantity,
                    'unit' => $ingredient->unit,
                    'reason' => $reason,
                    'previous_stock' => $tx->previous_stock,
                    'new_stock' => $tx->new_stock,
                ], $tx);

                return $this->movementRedirect($request, 'waste')
                    ->with('success', "Waste recorded successfully: −{$request->quantity} {$ingredient->unit} for {$ingredient->name}.");
            }

            $tx = InventoryService::adjust(
                ingredient: $ingredient,
                type: $request->type,
                quantity: $quantity,
                reason: $reason,
                performedBy: $user->name,
                performedRole: $user->role
            );

            AuditService::logFromUser($user, 'stock_adjustment', 'Inventory', [
                'ingredient' => $ingredient->name,
                'type' => $request->type,
                'quantity' => $quantity,
                'unit' => $ingredient->unit,
                'reason' => $reason,
                'previous_stock' => $tx->previous_stock,
                'new_stock' => $tx->new_stock,
            ], $tx);

            $direction = $request->type === 'adjustment_add' ? '+' : '−';

            return $this->movementRedirect($request)
                ->with('success', "Adjustment recorded successfully: {$direction}{$request->quantity} {$ingredient->unit} for {$ingredient->name}.");
        }, 3);
    }

    private function movementRedirect(Request $request, ?string $type = null)
    {
        $route = match ($request->route()?->getName()) {
            'stock-in.store' => 'stock-in.index',
            'waste.store' => 'waste.index',
            default => 'adjustments.index',
        };

        return redirect()->route($route, $type && $route === 'adjustments.index' ? ['type' => $type] : []);
    }
}
