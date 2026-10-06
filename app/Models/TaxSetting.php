<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rate',
        'is_inclusive',
        'is_active',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'rate'        => 'decimal:2',
            'is_inclusive' => 'boolean',
            'is_active'   => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    /** Whether this tax configuration is currently archived. */
    public function isArchived(): bool
    {
        return ! is_null($this->archived_at);
    }

    /** Archive the tax configuration. */
    public function archive(): void
    {
        $this->update(['archived_at' => now()]);
    }

    /** Restore (unarchive) the tax configuration. */
    public function unarchive(): void
    {
        $this->update(['archived_at' => null]);
    }

    /**
     * Get or create the singleton tax configuration.
     * Prefers a non-archived record; falls back to any record (archived included).
     */
    public static function current(): self
    {
        $setting = static::whereNull('archived_at')->first()
            ?? static::first();

        if (! $setting) {
            $setting = static::create([
                'name'         => 'VAT',
                'rate'         => 12.00,
                'is_inclusive' => true,
                'is_active'    => true,
            ]);
        }

        return $setting;
    }

    private static function roundMoney(float $value): float
    {
        return round((float) $value + 1e-12, 2);
    }

    /**
     * Compute tax, discounts, and totals for an order given the current tax configuration.
     */
    public function computeOrder(float $subtotal, ?string $discountType = 'none', float $customDiscount = 0): array
    {
        $subtotal = self::roundMoney(max(0, $subtotal));
        $taxRate = $this->is_active ? (float) $this->rate : 0.00;
        $isInclusive = (bool) $this->is_inclusive;

        $discountType = strtolower(trim($discountType ?? 'none'));
        $discountLabel = null;
        $discountAmount = 0.0;
        $vatableSales = 0.0;
        $vatExemptSales = 0.0;
        $zeroRatedSales = 0.0;
        $taxAmount = 0.0;

        if (in_array($discountType, ['senior', 'pwd'], true)) {
            $discountLabel = $discountType === 'senior'
                ? 'Senior Citizen (20% Off)'
                : 'PWD (20% Off)';
            $vatExclusiveSales = $this->is_active && $taxRate > 0 && $isInclusive
                ? self::roundMoney($subtotal / (1 + ($taxRate / 100)))
                : $subtotal;
            $discountAmount = self::roundMoney($vatExclusiveSales * 0.20);
            $total = self::roundMoney(max(0, $vatExclusiveSales - $discountAmount));
            $vatExemptSales = $total;
            $vatableSales = 0.0;
            $taxAmount = 0.0;
        } elseif ($discountType === 'custom') {
            $discountLabel = 'Custom Discount';
            $discountAmount = self::roundMoney(min($subtotal, max(0, $customDiscount)));
            $net = self::roundMoney(max(0, $subtotal - $discountAmount));

            if ($this->is_active && $taxRate > 0) {
                if ($isInclusive) {
                    $vatableSales = self::roundMoney($net / (1 + ($taxRate / 100)));
                    $taxAmount = self::roundMoney($net - $vatableSales);
                    $total = $net;
                } else {
                    $vatableSales = $net;
                    $taxAmount = self::roundMoney($net * ($taxRate / 100));
                    $total = self::roundMoney($net + $taxAmount);
                }
            } else {
                $vatableSales = $net;
                $taxAmount = 0.0;
                $total = $net;
            }
        } else {
            $discountType = 'none';
            if ($customDiscount > 0) {
                $discountAmount = self::roundMoney(min($subtotal, max(0, $customDiscount)));
                $discountLabel = 'Discount';
            }
            $net = self::roundMoney(max(0, $subtotal - $discountAmount));

            if ($this->is_active && $taxRate > 0) {
                if ($isInclusive) {
                    $vatableSales = self::roundMoney($net / (1 + ($taxRate / 100)));
                    $taxAmount = self::roundMoney($net - $vatableSales);
                    $total = $net;
                } else {
                    $vatableSales = $net;
                    $taxAmount = self::roundMoney($net * ($taxRate / 100));
                    $total = self::roundMoney($net + $taxAmount);
                }
            } else {
                $vatableSales = $net;
                $taxAmount = 0.0;
                $total = $net;
            }
        }

        return [
            'subtotal' => $subtotal,
            'discount_type' => $discountType,
            'discount_label' => $discountLabel,
            'discount' => $discountAmount,
            'tax_name' => $this->name ?? 'VAT',
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'vatable_sales' => $vatableSales,
            'vat_exempt_sales' => $vatExemptSales,
            'zero_rated_sales' => $zeroRatedSales,
            'total' => $total,
            'is_inclusive' => $isInclusive,
        ];
    }
}
