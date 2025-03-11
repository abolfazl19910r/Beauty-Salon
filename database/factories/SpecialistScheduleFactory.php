<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\SpecialistSchedule;
use App\Models\Specialist;

class SpecialistScheduleFactory extends Factory
{
    protected $model = SpecialistSchedule::class;

    public function definition(): array
    {
        return [
            'specialist_id' => Specialist::factory(),
            'day_of_week' => fake()->numberBetween(0, 6),
            'start_time' => fake()->randomElement(['08:00', '09:00', '10:00']),
            'end_time' => fake()->randomElement(['16:00', '17:00', '18:00']),
            'is_active' => true,
        ];
    }
}
