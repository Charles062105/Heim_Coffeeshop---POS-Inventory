<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoidLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'shift_id',
        'order_item_id',
        'amount',
        'void_type',
        'reason',
        'cashier_name',
        'requested_by_user_id',
        'requested_by',
        'requested_role',
        'authorized_user_id',
        'authorized_by',
        'authorized_role',
        'stock_restored',
        'voided_at',
    ];

    protected function casts(): array
    {
        return [
            'stock_restored' => 'boolean',
            'voided_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function shift()
    {
        return $this->belongsTo(CashierShift::class, 'shift_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function authorizedUser()
    {
        return $this->belongsTo(User::class, 'authorized_user_id');
    }

    public function requestedByUser()
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }
}
