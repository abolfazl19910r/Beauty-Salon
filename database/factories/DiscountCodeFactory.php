<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\DiscountCode;
use Illuminate\Support\Str;

class DiscountCodeFactory extends Factory
{
    protected $model = DiscountCode::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(8)),
            'type' => fake()->randomElement(['fixed', 'percentage']),
            'amount' => fake()->numberBetween(10000, 100000),
            'max_uses' => fake()->numberBetween(10, 100),
            'used_count' => 0,
            'is_active' => true,
            'expires_at' => fake()->dateTimeBetween('+1 month', '+3 months'),
        ];
    }

    public function percentage(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'percentage',
            'amount' => fake()->numberBetween(5, 50),
        ]);
    }

    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'fixed',
            'amount' => fake()->numberBetween(10000, 100000),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }
}
