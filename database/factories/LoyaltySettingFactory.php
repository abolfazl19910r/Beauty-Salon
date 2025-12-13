<?php

namespace Database\Factories;

use App\Models\LoyaltySetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoyaltySettingFactory extends Factory
{
    protected $model = LoyaltySetting::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(),
            'value' => fake()->word(),
            'description' => fake()->sentence(),
        ];
    }
}
