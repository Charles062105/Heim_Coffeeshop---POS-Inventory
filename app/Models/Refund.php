<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'payment_id', 'debt_payment_id', 'shift_id', 'amount', 'method', 'status', 'reason', 'authorized_by', 'authorized_role',
        'authorized_user_id', 'stock_restored', 'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'stock_restored' => 'boolean',
            'refunded_at' => 'datetime',
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

    public function authorizedUser()
    {
        return $this->belongsTo(User::class, 'authorized_user_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function debtPayment()
    {
        return $this->belongsTo(DebtPayment::class);
    }
}
