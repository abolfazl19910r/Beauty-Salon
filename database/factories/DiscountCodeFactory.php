<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DiscountCodeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(8)),
            'type' => fake()->randomElement(['fixed', 'percentage']),
            'amount' => fake()->numberBetween(10000, 100000),
            'max_uses' => fake()->numberBetween(10, 100),
            'expires_at' => fake()->dateTimeBetween('+1 month', '+3 months'),
            'is_active' => true
        ];
    }
}
