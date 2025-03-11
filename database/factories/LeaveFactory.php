<?php

namespace Database\Factories;

use App\Models\Specialist;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Leave;

class LeaveFactory extends Factory
{
    protected $model = Leave::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+1 month');
        $endDate = fake()->dateTimeBetween($startDate, '+2 months');

        return [
            'specialist_id' => Specialist::factory(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'reason' => fake()->sentence(),
            'reject_reason' => null,
            'approved_at' => null,
            'rejected_at' => null
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'rejected_at' => now(),
            'reject_reason' => fake()->sentence(),
        ]);
    }
}
