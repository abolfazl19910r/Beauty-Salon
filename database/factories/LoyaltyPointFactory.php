<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\LoyaltyPoint;

class LoyaltyPointFactory extends Factory
{
    protected $model = LoyaltyPoint::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'booking_id' => Booking::factory(),
            'points' => fake()->numberBetween(10, 100),
            'type' => 'earned',
            'description' => 'امتیاز از رزرو',
            'expires_at' => now()->addYear(),
        ];
    }

    public function spent(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'spent',
            'points' => -1 * fake()->numberBetween(10, 100),
            'description' => 'استفاده از پاداش',
        ]);
    }

    public function expiring(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addDays(5),
        ]);
    }
}
