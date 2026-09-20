<?php

namespace App\Repositories\Eloquent;

use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Repositories\Contracts\SpecialistScheduleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SpecialistScheduleRepository extends BaseRepository implements SpecialistScheduleRepositoryInterface
{
    public function __construct(SpecialistSchedule $model)
    {
        parent::__construct($model);
    }

    public function getGroupedBySpecialist(int $specialistId): Collection
    {
        return $this->model->where('specialist_id', $specialistId)->get()->groupBy('day_of_week');
    }

    public function findActiveForDay(int $specialistId, int $dayOfWeek): ?SpecialistSchedule
    {
        return $this->model->where('specialist_id', $specialistId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();
    }

    public function replaceForSpecialist(Specialist $specialist, array $schedules): void
    {
        $specialist->schedules()->delete();

        foreach ($schedules as $schedule) {
            if (! empty($schedule['is_active'])) {
                $specialist->schedules()->create([
                    'day_of_week' => $schedule['day_of_week'],
                    'start_time' => $schedule['start_time'],
                    'end_time' => $schedule['end_time'],
                    'break_start' => $schedule['break_start'] ?? null,
                    'break_end' => $schedule['break_end'] ?? null,
                    'is_active' => true,
                ]);
            }
        }
    }
}
