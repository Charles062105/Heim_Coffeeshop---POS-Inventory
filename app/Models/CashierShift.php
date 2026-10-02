<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CashierShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'open_user_id',
        'cashier_name',
        'shift_date',
        'start_time',
        'end_time',
        'beginning_cash',
        'cash_sales',
        'cash_refunds',
        'expected_cash',
        'actual_cash',
        'difference',
        'notes',
        'closed_by',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'denomination_count',
        'cash_voids',
        'debt_cash_collections',
        'online_sales',
        'debt_online_collections',
        'grab_sales',
        'grab_settlements',
        'pay_later_charged',
        'void_count',
        'void_amount',
        'dine_in_sales',
        'take_out_sales',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'shift_date' => 'date',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'beginning_cash' => 'decimal:2',
            'cash_sales' => 'decimal:2',
            'cash_refunds' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'actual_cash' => 'decimal:2',
            'difference' => 'decimal:2',
            'reviewed_at' => 'datetime',
            'denomination_count' => 'array',
            'cash_voids' => 'decimal:2',
            'debt_cash_collections' => 'decimal:2',
            'online_sales' => 'decimal:2',
            'debt_online_collections' => 'decimal:2',
            'grab_sales' => 'decimal:2',
            'grab_settlements' => 'decimal:2',
            'pay_later_charged' => 'decimal:2',
            'void_amount' => 'decimal:2',
            'dine_in_sales' => 'decimal:2',
            'take_out_sales' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'shift_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'shift_id');
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class, 'shift_id');
    }

    public function cashMovements()
    {
        return $this->hasMany(ShiftCashMovement::class, 'shift_id');
    }

    public function localStartTime()
    {
        return $this->start_time?->copy()->timezone(config('app.business_timezone', 'Asia/Manila'));
    }

    public function localEndTime()
    {
        return $this->end_time?->copy()->timezone(config('app.business_timezone', 'Asia/Manila'));
    }

    public function cashSummary(): array
    {
        $cashSales = (float) $this->payments()
            ->where('method', 'cash')
            ->whereIn('status', ['paid', 'refunded', 'voided'])
            ->sum('amount_paid');
        $debtCashCollections = (float) DebtPayment::where('shift_id', $this->id)
            ->where('payment_method', 'cash')
            ->sum('amount');
        $cashVoidQuery = $this->refunds()
            ->where('method', 'cash')
            ->where('status', 'completed')
            ->where(fn ($query) => $query
                ->where('reason', 'like', 'Item void:%')
                ->orWhere('reason', 'like', 'Order void:%'));
        $cashVoids = (float) (clone $cashVoidQuery)->sum('amount');
        $cashRefunds = (float) $this->refunds()
            ->where('method', 'cash')
            ->where('status', 'completed')
            ->where('reason', 'not like', 'Item void:%')
            ->where('reason', 'not like', 'Order void:%')
            ->sum('amount');
        $voids = VoidLog::where('shift_id', $this->id);
        $orders = $this->orders()->whereNotIn('status', ['voided', 'cancelled', 'refunded']);

        return [
            'cash_sales' => $cashSales,
            'debt_cash_collections' => $debtCashCollections,
            'cash_refunds' => $cashRefunds,
            'cash_voids' => $cashVoids,
            'online_sales' => (float) $this->payments()
                ->whereIn('method', ['online', 'other'])
                ->whereIn('status', ['paid', 'refunded', 'voided'])
                ->sum('amount_paid'),
            'debt_online_collections' => (float) DebtPayment::where('shift_id', $this->id)
                ->whereIn('payment_method', ['online', 'other'])
                ->sum('amount'),
            'grab_sales' => (float) (clone $orders)->where('order_type', 'grab')->sum('total'),
            'grab_settlements' => (float) $this->payments()
                ->whereIn('method', ['grab', 'grabfood'])
                ->whereIn('status', ['paid', 'refunded', 'voided'])
                ->sum('amount_paid'),
            'pay_later_charged' => (float) Debt::whereHas('order', fn ($query) => $query->where('shift_id', $this->id))
                ->sum('original_amount'),
            'void_count' => $voids->count(),
            'void_amount' => (float) $voids->sum('amount'),
            'dine_in_sales' => (float) (clone $orders)->where('order_type', 'dine_in')->sum('total'),
            'take_out_sales' => (float) (clone $orders)->where('order_type', 'take_out')->sum('total'),
        ];
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['closed', 'reviewed'], true);
    }

    public static function activeForUser(int $userId): ?self
    {
        return static::where('user_id', $userId)->where('status', 'open')->latest('start_time')->first();
    }

    public static function lockActiveForUser(int $userId): self
    {
        $shift = static::where('user_id', $userId)
            ->where('status', 'open')
            ->latest('start_time')
            ->lockForUpdate()
            ->first();

        if (! $shift) {
            throw ValidationException::withMessages([
                'shift' => 'The active shift has ended. Start a new shift before recording this transaction.',
            ]);
        }

        return $shift;
    }
}
