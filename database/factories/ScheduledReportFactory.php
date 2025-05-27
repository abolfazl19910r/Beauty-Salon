<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ScheduledReport;

class ScheduledReportFactory extends Factory
{
    protected $model = ScheduledReport::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->admin(),
            'report_type' => fake()->randomElement(['daily', 'weekly', 'monthly']),
            'parameters' => [
                'type' => fake()->randomElement(['revenue', 'bookings', 'specialists']),
                'start_date' => now()->subDays(30)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d')
            ],
            'frequency' => fake()->randomElement(['daily', 'weekly', 'monthly']),
            'next_run' => fake()->dateTimeBetween('now', '+1 week'),
            'recipients' => [fake()->email()],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function daily(): static
    {
        return $this->state(fn (array $attributes) => [
            'report_type' => 'daily',
            'frequency' => 'daily',
        ]);
    }

    public function weekly(): static
    {
        return $this->state(fn (array $attributes) => [
            'report_type' => 'weekly',
            'frequency' => 'weekly',
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'report_type' => 'monthly',
            'frequency' => 'monthly',
        ]);
    }
}
