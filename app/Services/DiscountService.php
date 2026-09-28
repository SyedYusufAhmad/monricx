<?php

namespace App\Services;

use App\Models\DiscountCode;

class DiscountService
{
    public const SESSION_KEY = 'monricx_checkout_discount_code';

    /**
     * Find a discount code by its code string and validate it against the
     * given merchandise subtotal. Returns [DiscountCode|null, error|null].
     */
    public function validate(string $code, int $subtotalPaise): array
    {
        $code = strtoupper(trim($code));

        if ($code === '') {
            return [null, 'Please enter a discount code.'];
        }

        $discount = DiscountCode::query()->where('code', $code)->first();

        if (! $discount) {
            return [null, 'This discount code is not valid.'];
        }

        if (! $discount->is_active) {
            return [null, 'This discount code is no longer active.'];
        }

        if (! $discount->isCurrentlyValid()) {
            return [null, 'This discount code has expired or reached its usage limit.'];
        }

        if ($subtotalPaise < $discount->minimum_order_paise) {
            $minimum = number_format($discount->minimum_order_paise / 100, 2);

            return [null, "This code needs a minimum order of ₹{$minimum}."];
        }

        return [$discount, null];
    }

    /**
     * Calculate the discount in paise for a validated code.
     * Percentage values are whole numbers (10 = 10%); fixed values are paise.
     */
    public function calculateDiscountPaise(DiscountCode $discount, int $subtotalPaise): int
    {
        $amount = $discount->type === 'percentage'
            ? (int) floor($subtotalPaise * $discount->value / 100)
            : (int) $discount->value;

        if ($discount->type === 'percentage' && $discount->maximum_discount_paise !== null) {
            $amount = min($amount, (int) $discount->maximum_discount_paise);
        }

        return max(0, min($amount, $subtotalPaise));
    }
}
