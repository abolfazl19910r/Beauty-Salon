<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Reward;

class RewardFactory extends Factory
{
    protected $model = Reward::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'required_points' => fake()->numberBetween(100, 1000),
            'discount_type' => fake()->randomElement(['fixed', 'percentage']),
            'discount_amount' => fake()->numberBetween(10, 50),
            'is_active' => true,
            'max_uses' => fake()->numberBetween(50, 100),
            'used_count' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
