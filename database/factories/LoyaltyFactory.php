<?php

namespace Database\Factories;

use App\Models\Loyalty;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoyaltyFactory extends Factory
{
    protected $model = Loyalty::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'description' => fake()->sentence(),
            'points_required' => fake()->numberBetween(100, 5000),
            'discount_percentage' => fake()->randomFloat(2, 5, 30),
            'is_active' => true,
        ];
    }
}
