<?php

namespace Database\Factories;

use App\Models\Reward;
use Illuminate\Database\Eloquent\Factories\Factory;

class RewardFactory extends Factory
{
    protected $model = Reward::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['fixed', 'percentage']);

        return [
            'title' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'required_points' => fake()->numberBetween(100, 2000),
            'discount_type' => $type,
            'discount_amount' => $type === 'percentage'
                ? fake()->numberBetween(5, 50)
                : fake()->numberBetween(50000, 500000),
            'is_active' => true,
            'max_uses' => fake()->optional()->numberBetween(10, 100),
            'used_count' => 0,
        ];
    }
}
