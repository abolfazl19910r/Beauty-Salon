<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\LoyaltyPoint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoyaltyPointFactory extends Factory
{
    protected $model = LoyaltyPoint::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // ایجاد یوزر جدید یا استفاده از موجود
            'booking_id' => Booking::factory(), // ایجاد رزرو جدید
            'points' => fake()->numberBetween(50, 500),
            'type' => 'earned',
            'description' => 'امتیاز دریافت شده',
            'expires_at' => now()->addYear(),
        ];
    }

    // حالتی برای امتیاز خرج شده
    public function spent(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'spent',
            'points' => -1 * fake()->numberBetween(50, 500),
            'description' => 'خرج امتیاز برای پاداش',
            'booking_id' => null,
        ]);
    }
}
