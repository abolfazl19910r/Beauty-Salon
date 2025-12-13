<?php

namespace Database\Factories;

use App\Models\BeautyService;
use App\Models\Specialist;
use App\Models\User;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $service = BeautyService::inRandomOrder()->first() ?? BeautyService::factory()->create();
        $specialist = Specialist::inRandomOrder()->first() ?? Specialist::factory()->create();
        $user = User::inRandomOrder()->first() ?? User::factory()->create();

        $bookingTime = fake()->dateTimeBetween('+1 day', '+2 months');
        $bookingTime->setTime(fake()->numberBetween(9, 17), 0, 0);

        return [
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'user_id' => $user->id,
            'booking_time' => $bookingTime,
            'status' => fake()->randomElement(['pending', 'confirmed', 'cancelled']),
            'prepayment_amount' => 50000,
            'payment_status' => 'unpaid',
            'rating' => fake()->optional(0.3)->numberBetween(1, 5),
            'review' => fake()->optional(0.3)->sentence(),
            'reminder_sent' => false,
            'discount_code' => null,
            'discount_amount' => null,
            'cancelled_by' => null,
            'cancellation_reason' => null,
            'cancelled_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    public function cancelledByUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_by' => 'customer',
            'cancellation_reason' => fake()->randomElement(['تغییر برنامه', 'لغو اضطراری']),
            'cancelled_at' => now(),
        ]);
    }
}
