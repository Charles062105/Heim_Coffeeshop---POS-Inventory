<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a single ingredient consumed when a ProductAddon is selected.
 *
 * Each row says: "When addon X is ordered, deduct [quantity] of [ingredient]."
 *
 * @property int $id
 * @property int $product_addon_id
 * @property int $ingredient_id
 * @property float $quantity Amount consumed per 1 addon ordered
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read ProductAddon $addon
 * @property-read Ingredient   $ingredient
 */
class AddonIngredient extends Model
{
    use HasFactory;

    protected $table = 'product_addon_ingredients';

    protected $fillable = [
        'product_addon_id',
        'ingredient_id',
        'replaces_ingredient_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function addon(): BelongsTo
    {
        return $this->belongsTo(ProductAddon::class, 'product_addon_id');
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function replacesIngredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'replaces_ingredient_id');
    }
}
