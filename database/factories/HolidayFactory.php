<?php

namespace Database\Factories;

use App\Models\Specialist;
use Illuminate\Database\Eloquent\Factories\Factory;

class HolidayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'specialist_id' => Specialist::factory(),
            'date' => fake()->dateTimeBetween('now', '+2 months'),
            'description' => fake()->optional()->sentence()
        ];
    }
}
