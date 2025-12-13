<?php

namespace Database\Factories;

use App\Models\DiscountUsage;
use App\Models\DiscountCode;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiscountUsageFactory extends Factory
{
    protected $model = DiscountUsage::class;

    public function definition(): array
    {
        $discountCode = DiscountCode::factory()->percentage(25)->create(['max_uses' => 5]);
        $booking = Booking::factory()->create();

        $amountSaved = $discountCode->type === 'percentage'
            ? ($booking->prepayment_amount * $discountCode->amount / 100)
            : $discountCode->amount;

        $finalAmountSaved = min($amountSaved, $booking->prepayment_amount);

        $booking->update([
            'discount_code' => $discountCode->code,
            'discount_amount' => $finalAmountSaved,
        ]);

        $discountCode->increment('used_count');

        return [
            'discount_code_id' => $discountCode->id,
            'user_id' => $booking->user_id,
            'booking_id' => $booking->id,
            'amount_saved' => $finalAmountSaved,
        ];
    }
}
