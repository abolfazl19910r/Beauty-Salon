<?php

namespace App\Services;

use App\Models\DiscountCode;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;

class DiscountCodeService
{
    public function create(array $data): DiscountCode
    {
        return DiscountCode::create($data);
    }

    public function update(DiscountCode $discountCode, array $data): bool
    {
        return $discountCode->update($data);
    }

    /**
     * @throws \Exception
     */
    public function applyToBooking(string $code, int $bookingId): array
    {
        $code = DiscountCode::where('code', $code)->first();
        if (!$code || !$code->isValid()) {
            throw new \Exception('کد تخفیف نامعتبر است.');
        }

        $booking = Booking::findOrFail($bookingId);
        if ($booking->discount_code) {
            throw new \Exception('کد تخفیف قبلاً اعمال شده است.');
        }

        DB::beginTransaction();
        try {
            $discountAmount = $this->calculateDiscount($code, $booking->prepayment_amount);

            $booking->update([
                'discount_code' => $code->code,
                'discount_amount' => $discountAmount,
                'prepayment_amount' => $booking->prepayment_amount - $discountAmount
            ]);

            $code->increment('used_count');

            DB::commit();

            return [
                'discount_amount' => $discountAmount,
                'final_price' => $booking->prepayment_amount
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function calculateDiscount(DiscountCode $code, float $amount): float
    {
        $discount = $code->type === 'percentage'
            ? ($amount * $code->amount / 100)
            : $code->amount;

        if ($code->max_amount) {
            $discount = min($discount, $code->max_amount);
        }

        return $discount;
    }
}
