<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'unit', 'minimum_stock', 'reorder_level', 'cost', 'supplier_id', 'expiration_date', 'status',
    ];

    protected function casts(): array
    {
        return [
            'minimum_stock' => 'decimal:3',
            'reorder_level' => 'decimal:3',
            'cost' => 'decimal:2',
            'expiration_date' => 'date',
        ];
    }

    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function recipeIngredients()
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    // Convenience: current stock level
    public function getCurrentStock(): float
    {
        return (float) ($this->inventory?->current_stock ?? 0);
    }

    public function getStockStatus(): string
    {
        $stock = $this->getCurrentStock();
        if ($stock <= 0) {
            return 'out_of_stock';
        }
        if ($stock <= $this->getReorderThreshold()) {
            return 'low_stock';
        }

        return 'good';
    }

    public function getReorderThreshold(): float
    {
        return (float) $this->reorder_level > 0
            ? (float) $this->reorder_level
            : (float) $this->minimum_stock;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
