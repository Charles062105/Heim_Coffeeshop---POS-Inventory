<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Recipe;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /**
     * Deduct ingredients for all items in a completed order.
     * Wrapped in a DB transaction by the caller (PosController).
     */
    public static function deductFromSale(Order $order, string $performedBy, string $performedRole): void
    {
        foreach ($order->orderItems->where('status', 'active') as $item) {
            // ── 1. Recipe ingredients (base product) ─────────────────────────
            $recipe = Recipe::where('product_size_id', $item->product_size_id)
                ->with('recipeIngredients.ingredient.inventory')
                ->first();

            if ($recipe) {
                foreach ($recipe->recipeIngredients as $ri) {
                    $totalQty = $ri->quantity * $item->quantity;
                    $ingredient = $ri->ingredient;

                    static::ensureIngredientInventory($ingredient);

                    static::deductIngredient(
                        $ingredient,
                        $totalQty,
                        $order,
                        $item,
                        "Sale: {$order->order_number}",
                        $performedBy,
                        $performedRole
                    );
                }
            }

            // ── 2. Addon ingredients ──────────────────────────────────────────
            // Load the addons for this order item, then find each addon's
            // ingredient mappings and deduct them × item quantity.
            $item->loadMissing(['addons.addon.addonIngredients.ingredient.inventory']);

            foreach ($item->addons as $orderItemAddon) {
                $addon = $orderItemAddon->addon;

                if (! $addon) {
                    continue;
                }

                foreach ($addon->addonIngredients as $ai) {
                    $totalQty = $ai->quantity * $item->quantity;
                    $ingredient = $ai->ingredient;

                    static::ensureIngredientInventory($ingredient);

                    static::deductIngredient(
                        $ingredient,
                        $totalQty,
                        $order,
                        $item,
                        "Addon '{$addon->name}' — Sale: {$order->order_number}",
                        $performedBy,
                        $performedRole
                    );
                }
            }
        }
    }

    private static function ensureIngredientInventory(?Ingredient $ingredient): void
    {
        if (! $ingredient) {
            throw ValidationException::withMessages([
                'items' => 'A recipe or add-on refers to an ingredient that no longer exists.',
            ]);
        }

        if (! $ingredient->inventory) {
            throw ValidationException::withMessages([
                'items' => "Inventory is not configured for {$ingredient->name}. Ask a manager to set up its stock before completing this sale.",
            ]);
        }
    }

    /**
     * Internal helper: deduct a quantity from one ingredient and write the
     * inventory transaction + low-stock notification check.
     */
    private static function deductIngredient(
        Ingredient $ingredient,
        float $qty,
        Order $order,
        OrderItem $item,
        string $reason,
        string $performedBy,
        string $performedRole
    ): void {
        $inventory = static::lockedInventory($ingredient);
        $previousStock = (float) $inventory->current_stock;
        $stockInMilliunits = (int) round($previousStock * 1000);
        $quantityInMilliunits = (int) round($qty * 1000);
        if ($quantityInMilliunits > $stockInMilliunits) {
            throw ValidationException::withMessages([
                'items' => "Insufficient {$ingredient->unit} of {$ingredient->name} in stock. Available: {$previousStock}; required: {$qty}.",
            ]);
        }

        $newStock = $previousStock - $qty;

        $inventory->current_stock = $newStock;
        $inventory->save();

        InventoryTransaction::create([
            'ingredient_id' => $ingredient->id,
            'type' => 'sales_consumption',
            'quantity' => $qty,
            'previous_stock' => $previousStock,
            'new_stock' => $newStock,
            'reference_type' => OrderItem::class,
            'reference_id' => $item->id,
            'reason' => $reason,
            'performed_by' => $performedBy,
            'performed_role' => $performedRole,
        ]);

        static::checkAndNotify($ingredient->fresh(['inventory']));
    }

    /**
     * Reverse ingredient deduction (for refunds, if business policy restores stock).
     */
    public static function restoreFromRefund(Order $order, string $performedBy, string $performedRole): void
    {
        static::restoreRecordedOrderConsumption($order, $performedBy, $performedRole, 'Refund');

        foreach ($order->orderItems->where('status', 'active') as $item) {
            if (static::restoreRecordedConsumption($item, $order, $performedBy, $performedRole, 'Refund')) {
                continue;
            }

            // ── 1. Recipe ingredients ─────────────────────────────────────────
            $recipe = Recipe::where('product_size_id', $item->product_size_id)
                ->with('recipeIngredients.ingredient.inventory')
                ->first();

            if ($recipe) {
                foreach ($recipe->recipeIngredients as $ri) {
                    $totalQty = $ri->quantity * $item->quantity;
                    $ingredient = $ri->ingredient;

                    if (! $ingredient || ! $ingredient->inventory) {
                        continue;
                    }

                    static::restoreIngredient(
                        $ingredient,
                        $totalQty,
                        $order,
                        "Refund stock restore: {$order->order_number}",
                        $performedBy,
                        $performedRole
                    );
                }
            }

            // ── 2. Addon ingredients ──────────────────────────────────────────
            $item->loadMissing(['addons.addon.addonIngredients.ingredient.inventory']);

            foreach ($item->addons as $orderItemAddon) {
                $addon = $orderItemAddon->addon;
                if (! $addon) {
                    continue;
                }

                foreach ($addon->addonIngredients as $ai) {
                    $totalQty = $ai->quantity * $item->quantity;
                    $ingredient = $ai->ingredient;

                    if (! $ingredient || ! $ingredient->inventory) {
                        continue;
                    }

                    static::restoreIngredient(
                        $ingredient,
                        $totalQty,
                        $order,
                        "Addon '{$addon->name}' refund restore: {$order->order_number}",
                        $performedBy,
                        $performedRole
                    );
                }
            }
        }
    }

    /**
     * Reverse ingredient deduction for voided transactions.
     */
    public static function restoreFromVoid(Order $order, string $performedBy, string $performedRole, ?OrderItem $specificItem = null): void
    {
        $hasOrderLevelConsumption = InventoryTransaction::query()
            ->where('type', 'sales_consumption')
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->exists();

        if ($specificItem) {
            $hasItemLevelConsumption = InventoryTransaction::query()
                ->where('type', 'sales_consumption')
                ->where('reference_type', OrderItem::class)
                ->where('reference_id', $specificItem->id)
                ->exists();

            if (! $hasItemLevelConsumption && $hasOrderLevelConsumption) {
                throw ValidationException::withMessages([
                    'item' => 'This order only has order-level ingredient records, so an individual item cannot be restored precisely. Void the entire order instead.',
                ]);
            }
        } else {
            static::restoreRecordedOrderConsumption($order, $performedBy, $performedRole, 'Void');
        }

        $items = $specificItem
            ? collect([$specificItem])
            : $order->orderItems->where('status', 'active');

        foreach ($items as $item) {
            if (static::restoreRecordedConsumption($item, $order, $performedBy, $performedRole, 'Void')) {
                continue;
            }

            // ── 1. Recipe ingredients ─────────────────────────────────────────
            $recipe = Recipe::where('product_size_id', $item->product_size_id)
                ->with('recipeIngredients.ingredient.inventory')
                ->first();

            if ($recipe) {
                foreach ($recipe->recipeIngredients as $ri) {
                    $totalQty = $ri->quantity * $item->quantity;
                    $ingredient = $ri->ingredient;

                    if (! $ingredient || ! $ingredient->inventory) {
                        continue;
                    }

                    static::restoreIngredient(
                        $ingredient,
                        $totalQty,
                        $order,
                        "Void stock restore: {$order->order_number}",
                        $performedBy,
                        $performedRole
                    );
                }
            }

            // ── 2. Addon ingredients ──────────────────────────────────────────
            $item->loadMissing(['addons.addon.addonIngredients.ingredient.inventory']);

            foreach ($item->addons as $orderItemAddon) {
                $addon = $orderItemAddon->addon;
                if (! $addon) {
                    continue;
                }

                foreach ($addon->addonIngredients as $ai) {
                    $totalQty = $ai->quantity * $item->quantity;
                    $ingredient = $ai->ingredient;

                    if (! $ingredient || ! $ingredient->inventory) {
                        continue;
                    }

                    static::restoreIngredient(
                        $ingredient,
                        $totalQty,
                        $order,
                        "Addon '{$addon->name}' void restore: {$order->order_number}",
                        $performedBy,
                        $performedRole
                    );
                }
            }
        }
    }

    private static function restoreRecordedConsumption(
        OrderItem $item,
        Order $order,
        string $performedBy,
        string $performedRole,
        string $action
    ): bool {
        $consumption = InventoryTransaction::query()
            ->where('type', 'sales_consumption')
            ->where('reference_type', OrderItem::class)
            ->where('reference_id', $item->id)
            ->orderBy('id')
            ->get();

        if ($consumption->isEmpty()) {
            return InventoryTransaction::query()
                ->where('type', 'sales_consumption')
                ->where('reference_type', Order::class)
                ->where('reference_id', $order->id)
                ->exists();
        }

        $alreadyReturned = InventoryTransaction::query()
            ->where('type', 'sales_return')
            ->where('reference_type', OrderItem::class)
            ->where('reference_id', $item->id)
            ->exists();

        if ($alreadyReturned) {
            return true;
        }

        foreach ($consumption as $movement) {
            $ingredient = Ingredient::find($movement->ingredient_id);
            if (! $ingredient) {
                throw new \RuntimeException("Cannot restore missing ingredient for consumption movement #{$movement->id}.");
            }

            $inventory = static::lockedInventory($ingredient);
            $previousStock = (float) $inventory->current_stock;
            $quantity = (float) $movement->quantity;
            $newStock = $previousStock + $quantity;

            $inventory->current_stock = $newStock;
            $inventory->save();

            InventoryTransaction::create([
                'ingredient_id' => $ingredient->id,
                'type' => 'sales_return',
                'quantity' => $quantity,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'reference_type' => OrderItem::class,
                'reference_id' => $item->id,
                'reason' => "{$action} return: {$movement->reason}",
                'performed_by' => $performedBy,
                'performed_role' => $performedRole,
            ]);

            static::checkAndNotify($ingredient->fresh(['inventory']));
        }

        return true;
    }

    private static function restoreRecordedOrderConsumption(
        Order $order,
        string $performedBy,
        string $performedRole,
        string $action
    ): bool {
        $consumption = InventoryTransaction::query()
            ->where('type', 'sales_consumption')
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->orderBy('id')
            ->get();

        if ($consumption->isEmpty()) {
            return false;
        }

        $alreadyReturned = InventoryTransaction::query()
            ->where('type', 'sales_return')
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->exists();

        if ($alreadyReturned) {
            return true;
        }

        foreach ($consumption as $movement) {
            $ingredient = Ingredient::find($movement->ingredient_id);
            if (! $ingredient) {
                throw new \RuntimeException("Cannot restore missing ingredient for consumption movement #{$movement->id}.");
            }

            $inventory = static::lockedInventory($ingredient);
            $previousStock = (float) $inventory->current_stock;
            $quantity = (float) $movement->quantity;
            $newStock = $previousStock + $quantity;

            $inventory->current_stock = $newStock;
            $inventory->save();

            InventoryTransaction::create([
                'ingredient_id' => $ingredient->id,
                'type' => 'sales_return',
                'quantity' => $quantity,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'reason' => "{$action} return: {$movement->reason}",
                'performed_by' => $performedBy,
                'performed_role' => $performedRole,
            ]);

            static::checkAndNotify($ingredient->fresh(['inventory']));
        }

        return true;
    }

    /**
     * Internal helper: restore a quantity to one ingredient and write the
     * inventory transaction record.
     */
    private static function restoreIngredient(
        Ingredient $ingredient,
        float $qty,
        Order $order,
        string $reason,
        string $performedBy,
        string $performedRole
    ): void {
        $inventory = static::lockedInventory($ingredient);
        $previousStock = (float) $inventory->current_stock;
        $newStock = $previousStock + $qty;

        $inventory->current_stock = $newStock;
        $inventory->save();

        InventoryTransaction::create([
            'ingredient_id' => $ingredient->id,
            'type' => 'sales_return',
            'quantity' => $qty,
            'previous_stock' => $previousStock,
            'new_stock' => $newStock,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'reason' => 'Sales return: '.$reason,
            'performed_by' => $performedBy,
            'performed_role' => $performedRole,
        ]);
    }

    /**
     * Record a stock-in transaction.
     */
    public static function addStock(
        Ingredient $ingredient,
        float $quantity,
        string $performedBy,
        string $performedRole,
        string $reason = '',
        ?int $supplierId = null,
        ?string $referenceNumber = null,
        ?float $unitCost = null,
        ?string $transactionDate = null,
        ?string $expirationDate = null
    ): InventoryTransaction {
        return DB::transaction(function () use ($ingredient, $quantity, $performedBy, $performedRole, $reason, $supplierId, $referenceNumber, $unitCost, $transactionDate, $expirationDate) {
            $inventory = static::lockedInventory($ingredient);
            $previousStock = (float) $inventory->current_stock;
            $newStock = $previousStock + $quantity;

            $inventory->current_stock = $newStock;
            $inventory->save();

            $tx = InventoryTransaction::create([
                'ingredient_id' => $ingredient->id,
                'type' => 'stock_in',
                'quantity' => $quantity,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'reason' => $reason,
                'performed_by' => $performedBy,
                'performed_role' => $performedRole,
                'supplier_id' => $supplierId,
                'reference_number' => $referenceNumber,
                'unit_cost' => $unitCost,
                'transaction_date' => $transactionDate,
                'expiration_date' => $expirationDate,
            ]);

            static::checkAndNotify($ingredient->fresh(['inventory']));

            return $tx;
        });
    }

    /**
     * Record a waste / spoilage transaction.
     */
    public static function recordWaste(
        Ingredient $ingredient,
        float $quantity,
        string $reason,
        string $performedBy,
        string $performedRole
    ): InventoryTransaction {
        return DB::transaction(function () use ($ingredient, $quantity, $reason, $performedBy, $performedRole) {
            $inventory = static::lockedInventory($ingredient);
            $previousStock = (float) $inventory->current_stock;
            static::ensureStockAvailable($previousStock, $quantity);
            $newStock = $previousStock - $quantity;

            $inventory->current_stock = $newStock;
            $inventory->save();

            $tx = InventoryTransaction::create([
                'ingredient_id' => $ingredient->id,
                'type' => 'waste',
                'quantity' => $quantity,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'reason' => $reason,
                'performed_by' => $performedBy,
                'performed_role' => $performedRole,
            ]);

            static::checkAndNotify($ingredient->fresh(['inventory']));

            return $tx;
        });
    }

    /**
     * Record a stock adjustment (add or deduct).
     */
    public static function adjust(
        Ingredient $ingredient,
        string $type, // 'adjustment_add' or 'adjustment_deduct'
        float $quantity,
        string $reason,
        string $performedBy,
        string $performedRole
    ): InventoryTransaction {
        if (! in_array($type, ['adjustment_add', 'adjustment_deduct'], true)) {
            throw new \InvalidArgumentException('Unsupported inventory adjustment type.');
        }

        return DB::transaction(function () use ($ingredient, $type, $quantity, $reason, $performedBy, $performedRole) {
            $inventory = static::lockedInventory($ingredient);
            $previousStock = (float) $inventory->current_stock;
            if ($type === 'adjustment_deduct') {
                static::ensureStockAvailable($previousStock, $quantity);
            }
            $newStock = $type === 'adjustment_add'
                ? $previousStock + $quantity
                : $previousStock - $quantity;

            $inventory->current_stock = $newStock;
            $inventory->save();

            $tx = InventoryTransaction::create([
                'ingredient_id' => $ingredient->id,
                'type' => $type,
                'quantity' => $quantity,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'reason' => $reason,
                'performed_by' => $performedBy,
                'performed_role' => $performedRole,
            ]);

            static::checkAndNotify($ingredient->fresh(['inventory']));

            return $tx;
        });
    }

    private static function lockedInventory(Ingredient $ingredient): Inventory
    {
        Ingredient::query()->whereKey($ingredient->id)->lockForUpdate()->firstOrFail();

        $inventory = Inventory::query()
            ->where('ingredient_id', $ingredient->id)
            ->lockForUpdate()
            ->first();

        if (! $inventory) {
            $inventory = Inventory::create([
                'ingredient_id' => $ingredient->id,
                'current_stock' => 0,
            ]);
        }

        return $inventory;
    }

    private static function ensureStockAvailable(float $stock, float $quantity): void
    {
        if ($quantity > $stock) {
            throw ValidationException::withMessages([
                'quantity' => 'The quantity cannot exceed the ingredient’s current stock.',
            ]);
        }
    }

    /**
     * Check ingredient stock and create notification if low or out of stock.
     */
    public static function checkAndNotify(Ingredient $ingredient): void
    {
        $status = $ingredient->getStockStatus();
        $notificationType = match ($status) {
            'out_of_stock' => 'out_of_stock',
            'low_stock' => 'low_stock',
            default => null,
        };

        $staleNotifications = Notification::where('ingredient_id', $ingredient->id)
            ->where('is_resolved', false)
            ->whereIn('type', ['low_stock', 'out_of_stock']);

        if ($notificationType === null) {
            $staleNotifications->update(['is_resolved' => true, 'resolved_at' => now()]);

            return;
        }

        $staleNotifications->where('type', '!=', $notificationType)
            ->update(['is_resolved' => true, 'resolved_at' => now()]);

        $existing = Notification::where('ingredient_id', $ingredient->id)
            ->where('type', $notificationType)
            ->where('is_resolved', false)
            ->first();

        if ($existing) {
            return;
        }

        $stock = $ingredient->getCurrentStock();

        if ($notificationType === 'out_of_stock') {
            Notification::create([
                'type' => 'out_of_stock',
                'title' => "⚠️ Out of Stock: {$ingredient->name}",
                'message' => "{$ingredient->name} is out of stock (current: {$stock} {$ingredient->unit}).",
                'target_role' => 'manager',
                'ingredient_id' => $ingredient->id,
            ]);
        } else {
            Notification::create([
                'type' => 'low_stock',
                'title' => "🔔 Low Stock: {$ingredient->name}",
                'message' => "{$ingredient->name} is running low ({$stock} {$ingredient->unit} remaining, reorder threshold: {$ingredient->getReorderThreshold()} {$ingredient->unit}).",
                'target_role' => 'manager',
                'ingredient_id' => $ingredient->id,
            ]);
        }
    }
}
