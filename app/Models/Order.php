<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'order_type',
        'grab_order_code',
        'rider_code',
        'customer_name',
        'shift_id',
        'cashier_name',
        'subtotal',
        'discount',
        'discount_type',
        'discount_label',
        'discount_id_number',
        'total',
        'tax_name',
        'tax_rate',
        'tax_amount',
        'vatable_sales',
        'vat_exempt_sales',
        'zero_rated_sales',
        'status',
        'held_at',
        'is_pinned',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'vatable_sales' => 'decimal:2',
            'vat_exempt_sales' => 'decimal:2',
            'zero_rated_sales' => 'decimal:2',
            'held_at' => 'datetime',
            'is_pinned' => 'boolean',
        ];
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function items()
    {
        return $this->orderItems();
    }

    public function payment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function paidAmount(): float
    {
        return (float) $this->payments()->where('status', 'paid')->sum('amount_paid');
    }

    public function remainingBalance(): float
    {
        return max(0, round((float) $this->total - $this->paidAmount(), 2));
    }

    public function refund()
    {
        return $this->hasOne(Refund::class)->latestOfMany();
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    public function voidLogs()
    {
        return $this->hasMany(VoidLog::class);
    }

    public function adjustments()
    {
        return $this->hasMany(OrderAdjustment::class);
    }

    public function shift()
    {
        return $this->belongsTo(CashierShift::class, 'shift_id');
    }

    public function isGrab(): bool
    {
        return $this->order_type === 'grab';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isHeld(): bool
    {
        return $this->status === 'held';
    }

    public function isVoided(): bool
    {
        return $this->status === 'voided';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function isPayLater(): bool
    {
        return $this->status === 'pay_later';
    }

    public function debt()
    {
        return $this->hasOne(Debt::class);
    }

    // Generate a unique order number without relying on a single incremented counter.
    public static function generateOrderNumber(string $prefix = 'ORD-'): string
    {
        $prefix = strtoupper(trim($prefix));
        if ($prefix === '') {
            $prefix = 'ORD-';
        }

        $datePrefix = $prefix.now()->format('Ymd').'-';

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $candidate = $datePrefix.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            if (! static::where('order_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Unable to generate a unique order number.');
    }
}
