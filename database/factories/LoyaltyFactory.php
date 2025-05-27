<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Loyalty;

class LoyaltyFactory extends Factory
{
    protected $model = Loyalty::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'description' => fake()->paragraph(),
            'points_required' => fake()->numberBetween(100, 1000),
            'discount_percentage' => fake()->randomFloat(2, 5, 25),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
