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
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'is_inclusive' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get or create the singleton tax configuration.
     */
    public static function current(): self
    {
        $setting = static::first();

        if (! $setting) {
            $setting = static::create([
                'name' => 'VAT',
                'rate' => 12.00,
                'is_inclusive' => true,
                'is_active' => true,
            ]);
        }

        return $setting;
    }

    /**
     * Compute tax, discounts, and totals for an order given the current tax configuration.
     */
    public function computeOrder(float $subtotal, ?string $discountType = 'none', float $customDiscount = 0): array
    {
        $subtotal = round(max(0, $subtotal), 2);
        $taxRate = $this->is_active ? (float) $this->rate : 0.00;
        $isInclusive = (bool) $this->is_inclusive;

        $discountType = strtolower(trim($discountType ?? 'none'));
        $discountLabel = null;
        $discountAmount = 0.0;
        $vatableSales = 0.0;
        $vatExemptSales = 0.0;
        $zeroRatedSales = 0.0;
        $taxAmount = 0.0;

        if ($discountType === 'senior') {
            $discountLabel = 'Senior Citizen (20% Off)';
            $discountAmount = round($subtotal * 0.20, 2);
            // Senior Citizen is VAT Exempt
            $total = round(max(0, $subtotal - $discountAmount), 2);
            $vatExemptSales = $total;
            $vatableSales = 0.0;
            $taxAmount = 0.0;
        } elseif ($discountType === 'pwd') {
            $discountLabel = 'PWD (20% Off)';
            $discountAmount = round($subtotal * 0.20, 2);
            // PWD is VAT Exempt
            $total = round(max(0, $subtotal - $discountAmount), 2);
            $vatExemptSales = $total;
            $vatableSales = 0.0;
            $taxAmount = 0.0;
        } elseif ($discountType === 'custom') {
            $discountLabel = 'Custom Discount';
            $discountAmount = round(min($subtotal, max(0, $customDiscount)), 2);
            $net = round(max(0, $subtotal - $discountAmount), 2);

            if ($this->is_active && $taxRate > 0) {
                if ($isInclusive) {
                    $vatableSales = round($net / (1 + ($taxRate / 100)), 2);
                    $taxAmount = round($net - $vatableSales, 2);
                    $total = $net;
                } else {
                    $vatableSales = $net;
                    $taxAmount = round($net * ($taxRate / 100), 2);
                    $total = round($net + $taxAmount, 2);
                }
            } else {
                $vatableSales = $net;
                $taxAmount = 0.0;
                $total = $net;
            }
        } else {
            // No discount ('none' or unspecified)
            $discountType = 'none';
            if ($customDiscount > 0) {
                // Backward compatibility if raw discount amount provided
                $discountAmount = round(min($subtotal, max(0, $customDiscount)), 2);
                $discountLabel = 'Discount';
            }
            $net = round(max(0, $subtotal - $discountAmount), 2);

            if ($this->is_active && $taxRate > 0) {
                if ($isInclusive) {
                    $vatableSales = round($net / (1 + ($taxRate / 100)), 2);
                    $taxAmount = round($net - $vatableSales, 2);
                    $total = $net;
                } else {
                    $vatableSales = $net;
                    $taxAmount = round($net * ($taxRate / 100), 2);
                    $total = round($net + $taxAmount, 2);
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
