<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\LoyaltySetting;

class LoyaltySettingFactory extends Factory
{
    protected $model = LoyaltySetting::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->word(),
            'value' => fake()->numberBetween(1, 100),
            'description' => fake()->sentence(),
        ];
    }
}
