<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSize extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'size_name', 'price', 'grab_price', 'status'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'grab_price' => 'decimal:2',
        ];
    }

    public function getGrabPrice(): float
    {
        return (float) ($this->grab_price ?? $this->price);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function recipe()
    {
        return $this->hasOne(Recipe::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
