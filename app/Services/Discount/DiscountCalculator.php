<?php

namespace App\Services\Discount;

use App\Models\DiscountCode;

final class DiscountCalculator
{
    /**
     * @return array{discount_amount: float, final_amount: float}
     */
    public function calculate(DiscountCode $code, float $baseAmount): array
    {
        $discount = $code->type === 'percentage'
            ? ($baseAmount * (float) $code->amount / 100)
            : (float) $code->amount;

        if (! is_null($code->max_amount)) {
            $discount = min($discount, (float) $code->max_amount);
        }

        $discount = min($discount, $baseAmount);

        return [
            'discount_amount' => $discount,
            'final_amount' => max(0, $baseAmount - $discount),
        ];
    }
}
