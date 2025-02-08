<?php

namespace Database\Factories;

use App\Models\Specialist;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveFactory extends Factory
{
    public function definition(): array
    {
        return [
            'specialist_id' => Specialist::factory(),
            'start_date' => fake()->dateTimeBetween('now', '+1 month'),
            'end_date' => fake()->dateTimeBetween('+1 month', '+2 months'),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'reason' => fake()->sentence()
        ];
    }
}
