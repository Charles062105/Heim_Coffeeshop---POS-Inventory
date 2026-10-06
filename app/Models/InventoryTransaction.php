<?php

namespace App\Models;

use App\Support\BusinessDateRange;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'ingredient_id', 'type', 'quantity', 'previous_stock', 'new_stock',
        'reference_id', 'reference_type', 'reason', 'performed_by', 'performed_role',
        'supplier_id', 'reference_number', 'unit_cost', 'transaction_date', 'expiration_date',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'previous_stock' => 'decimal:3',
            'new_stock' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'transaction_date' => 'date',
            'expiration_date' => 'date',
        ];
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function scopeEffectiveDateFrom(Builder $query, string $date): Builder
    {
        return $query->where(function (Builder $query) use ($date) {
            $query->whereDate('inventory_transactions.transaction_date', '>=', $date)
                ->orWhere(function (Builder $query) use ($date) {
                    $query->whereNull('inventory_transactions.transaction_date')
                        ->where('inventory_transactions.created_at', '>=', BusinessDateRange::startUtc($date));
                });
        });
    }

    public function scopeEffectiveDateTo(Builder $query, string $date): Builder
    {
        return $query->where(function (Builder $query) use ($date) {
            $query->whereDate('inventory_transactions.transaction_date', '<=', $date)
                ->orWhere(function (Builder $query) use ($date) {
                    $query->whereNull('inventory_transactions.transaction_date')
                        ->where('inventory_transactions.created_at', '<', BusinessDateRange::endExclusiveUtc($date));
                });
        });
    }

    // Human-readable type labels
    public function getTypeLabel(): string
    {
        return static::typeLabel($this->type);
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'stock_in' => 'Stock In',
            'adjustment_deduct' => 'Stock-Out',
            'sales_consumption' => 'Sales',
            'sales_return' => 'Sales Return',
            'waste' => 'Waste/Spoilage',
            'adjustment_add' => 'Adjustment (+)',
            default => ucfirst($type),
        };
    }
}
