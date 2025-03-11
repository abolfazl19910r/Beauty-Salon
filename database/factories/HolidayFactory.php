<?php

namespace Database\Factories;

use App\Models\Specialist;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Holiday;

class HolidayFactory extends Factory
{
    protected $model = Holiday::class;

    public function definition(): array
    {
        return [
            'specialist_id' => Specialist::factory(),
            'date' => fake()->dateTimeBetween('now', '+2 months'),
            'description' => fake()->optional()->sentence()
        ];
    }
}
