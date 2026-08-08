<?php

namespace App\Services\Discount;

use App\Models\DiscountCode;

/**
 * The only source of discount calculation in the entire project (R-DiscountLogic).
 *
 * Previously, the same formula (percentage/fixed + max_amount limit) was repeated in 6 independent places:
 * BookingService (3 methods), DiscountCode::calculateDiscount() (model, unused),
 * DiscountCodeService::calculateDiscount() and User\DiscountCodeController::applyToBooking()
 * (both dead code). These implementations diverged from each other — for example, the max_amount limit in the
 * queue creation path (calculatePrepayment) was not applied at all.
 */
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

        // The discount should never exceed the base amount itself.
        $discount = min($discount, $baseAmount);

        return [
            'discount_amount' => $discount,
            'final_amount' => max(0, $baseAmount - $discount),
        ];
    }
}
