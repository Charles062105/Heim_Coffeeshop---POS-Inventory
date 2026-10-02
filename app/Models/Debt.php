<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Debt extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'customer_name',
        'customer_phone',
        'original_amount',
        'amount_paid',
        'balance',
        'due_date',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'original_amount' => 'decimal:2',
            'amount_paid'     => 'decimal:2',
            'balance'         => 'decimal:2',
            'due_date'        => 'date',
        ];
    }

    // ── Relationships ───────────────────────────────────────────────────────

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function payments()
    {
        return $this->hasMany(DebtPayment::class);
    }

    // ── Status helpers ──────────────────────────────────────────────────────

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending'        => 'Pending',
            'partially_paid' => 'Partially Paid',
            'paid'           => 'Paid',
            'overdue'        => 'Overdue',
            default          => ucfirst($this->status),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'paid'           => 'bg-emerald-100 text-emerald-800',
            'partially_paid' => 'bg-amber-100 text-amber-800',
            'overdue'        => 'bg-rose-100 text-rose-800',
            default          => 'bg-gray-100 text-gray-700',
        };
    }

    /**
     * Recalculate amount_paid, balance and status after a payment is recorded.
     */
    public function recalculate(): void
    {
        $paid = (float) $this->payments()->sum('amount');
        $balance = max(0, (float) $this->original_amount - $paid);
        $isOverdue = $this->due_date && now()->startOfDay()->gt($this->due_date);

        $status = 'pending';
        if ($balance <= 0) {
            $status = 'paid';
        } elseif ($paid > 0 && $isOverdue) {
            $status = 'overdue';
        } elseif ($paid > 0) {
            $status = 'partially_paid';
        } elseif ($isOverdue) {
            $status = 'overdue';
        }

        $this->update([
            'amount_paid' => $paid,
            'balance' => $balance,
            'status' => $status,
        ]);
    }

    /**
     * Refresh overdue statuses for all unpaid debts past their due date.
     */
    public static function refreshOverdueStatuses(): void
    {
        static::whereIn('status', ['pending', 'partially_paid'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);
    }
}
