<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';

    protected $fillable = ['ingredient_id', 'current_stock'];

    protected function casts(): array
    {
        return ['current_stock' => 'decimal:3'];
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
