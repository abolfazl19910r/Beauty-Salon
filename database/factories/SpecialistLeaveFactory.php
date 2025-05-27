<?php

namespace Database\Factories;

use App\Models\Specialist;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\SpecialistLeave;

class SpecialistLeaveFactory extends Factory
{
    protected $model = SpecialistLeave::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+1 month');
        $endDate = fake()->dateTimeBetween($startDate, '+2 months');

        return [
            'specialist_id' => Specialist::factory(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => fake()->sentence(),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }
}
