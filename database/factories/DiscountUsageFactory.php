<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\DiscountCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\DiscountUsage;

class DiscountUsageFactory extends Factory
{
    protected $model = DiscountUsage::class;

    public function definition(): array
    {
        $discountCode = DiscountCode::factory()->create();
        $booking = Booking::factory()->create();

        $amountSaved = $discountCode->type === 'percentage'
            ? ($booking->prepayment_amount * $discountCode->amount / 100)
            : $discountCode->amount;

        return [
            'discount_code_id' => $discountCode->id,
            'user_id' => $booking->user_id,
            'booking_id' => $booking->id,
            'amount_saved' => $amountSaved,
        ];
    }
}
