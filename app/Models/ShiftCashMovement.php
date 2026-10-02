<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftCashMovement extends Model
{
    protected $fillable = [
        'shift_id',
        'recorded_by',
        'type',
        'amount',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function shift()
    {
        return $this->belongsTo(CashierShift::class, 'shift_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
