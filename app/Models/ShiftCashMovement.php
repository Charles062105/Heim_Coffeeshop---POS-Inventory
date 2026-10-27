<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftCashMovement extends Model
{
    use BelongsToBranch, HasFactory;

    protected $fillable = [
        'shift_id',
        'branch_id',
        'cashier_id',
        'recorded_by',
        'authorized_by',
        'type',
        'amount',
        'reason',
        'status',
        'request_id',
        'reversed_at',
        'reversed_by',
        'reversal_reason',
        'reversal_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'reversed_at' => 'datetime',
        ];
    }

    public function shift()
    {
        return $this->belongsTo(CashierShift::class, 'shift_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function authorizedBy()
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    public function reversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function reversal()
    {
        return $this->belongsTo(self::class, 'reversal_id');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isReversed(): bool
    {
        return $this->status === 'reversed';
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeCashIn($query)
    {
        return $query->where('type', 'cash_in');
    }

    public function scopeCashOut($query)
    {
        return $query->where('type', 'cash_out');
    }
}
