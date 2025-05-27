<?php

namespace Database\Factories;

use App\Models\ScheduledReport;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ScheduledReportRun;

class ScheduledReportRunFactory extends Factory
{
    protected $model = ScheduledReportRun::class;

    public function definition(): array
    {
        return [
            'scheduled_report_id' => ScheduledReport::factory(),
            'status' => fake()->randomElement(['success', 'failed', 'running']),
            'result_file' => fake()->optional()->filePath(),
            'error_message' => null,
        ];
    }

    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'result_file' => 'reports/report_' . now()->format('Y_m_d_H_i_s') . '.xlsx',
            'error_message' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'result_file' => null,
            'error_message' => fake()->sentence(),
        ]);
    }

    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'running',
            'result_file' => null,
            'error_message' => null,
        ]);
    }
}
