<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use BelongsToBranch, HasFactory;

    protected $table = 'inventory';

    protected $fillable = ['ingredient_id', 'current_stock', 'branch_id'];

    protected function casts(): array
    {
        return ['current_stock' => 'decimal:3'];
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
