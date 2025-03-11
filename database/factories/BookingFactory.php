<?php

namespace Database\Factories;

use App\Models\BeautyService;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Booking;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'service_id' => BeautyService::factory(),
            'specialist_id' => Specialist::factory(),
            'user_id' => User::factory(),
            'booking_time' => fake()->dateTimeBetween('now', '+2 months'),
            'status' => fake()->randomElement(['pending', 'confirmed', 'cancelled']),
            'prepayment_amount' => 50000,
            'payment_status' => fake()->randomElement(['unpaid', 'paid']),
            'rating' => fake()->optional()->numberBetween(1, 5),
            'review' => fake()->optional()->sentence(),
            'reminder_sent' => false,
            'discount_code' => null,
            'discount_amount' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }
}
