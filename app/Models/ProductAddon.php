<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAddon extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'price', 'status'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function orderItemAddons()
    {
        return $this->hasMany(OrderItemAddon::class);
    }

    /**
     * Ingredients consumed when this addon is selected.
     * Used by InventoryService::deductFromSale to track addon consumption.
     */
    public function addonIngredients()
    {
        return $this->hasMany(AddonIngredient::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
