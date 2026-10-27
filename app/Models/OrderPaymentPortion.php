<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPaymentPortion extends Model
{
    protected $fillable = [
        'order_id',
        'payer_name',
        'due_amount',
        'amount_paid',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'due_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'portion_id');
    }
}
