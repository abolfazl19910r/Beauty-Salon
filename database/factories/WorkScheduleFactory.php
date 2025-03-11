<?php

namespace Database\Factories;

use App\Models\Specialist;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\WorkSchedule;

class WorkScheduleFactory extends Factory
{
    protected $model = WorkSchedule::class;

    public function definition(): array
    {
        return [
            'specialist_id' => Specialist::factory(),
            'work_days' => [0, 1, 2, 3, 4],
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => true
        ];
    }
}
