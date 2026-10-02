<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['category_id', 'name', 'description', 'image', 'status'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function sizes()
    {
        return $this->hasMany(ProductSize::class);
    }

    public function activeSizes()
    {
        return $this->hasMany(ProductSize::class)->where('status', 'active');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function hasOrders(): bool
    {
        return $this->orderItems()->exists();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
