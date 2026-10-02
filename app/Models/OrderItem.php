<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_size_id',
        'quantity',
        'unit_price',
        'subtotal',
        'comment',
        'assigned_to',
        'payable_total',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'payable_total' => 'decimal:2',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productSize()
    {
        return $this->belongsTo(ProductSize::class);
    }

    public function size()
    {
        return $this->productSize();
    }

    public function addons()
    {
        return $this->hasMany(OrderItemAddon::class);
    }

    public function voidLog()
    {
        return $this->hasOne(VoidLog::class);
    }

    public function isVoided(): bool
    {
        return $this->status === 'voided';
    }
}
