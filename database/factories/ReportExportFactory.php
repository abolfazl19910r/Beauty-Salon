<?php

namespace Database\Factories;

use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportExportFactory extends Factory
{
    protected $model = ReportExport::class;

    public function definition(): array
    {
        return [
            'admin_user_id' => User::factory(),
            'format' => $this->faker->randomElement(['pdf', 'excel']),
            'report_type' => $this->faker->randomElement(['daily', 'weekly', 'monthly']),
            'filters' => ['start_date' => now()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')],
            'status' => 'pending',
        ];
    }

    public function ready(): static
    {
        return $this->state(fn () => [
            'status' => 'ready',
            'file_path' => 'report-exports/test-'.uniqid().'.pdf',
            'ready_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'error_message' => 'خطای تست',
        ]);
    }
}
