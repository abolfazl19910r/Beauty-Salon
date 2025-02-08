<?php

namespace Database\Factories;

use App\Models\BeautyService;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
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
            'review' => fake()->optional()->sentence()
        ];
    }
}
