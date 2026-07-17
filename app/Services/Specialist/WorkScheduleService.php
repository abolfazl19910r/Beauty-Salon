<?php

namespace App\Services\Specialist;

use App\Models\Specialist;
use App\Models\WorkSchedule;

class WorkScheduleService
{
    /**
     * Create or update a specialist's work schedule (one unique record per specialist).
 */
    public function upsert(Specialist $specialist, array $validated): WorkSchedule
    {
        return WorkSchedule::updateOrCreate(
            ['specialist_id' => $specialist->id],
            [
                'work_days'  => $validated['work_days'],
                'start_time' => $validated['start_time'],
                'end_time'   => $validated['end_time'],
                'is_active'  => $validated['is_active'] ?? true,
            ]
        );
    }

    public function delete(Specialist $specialist): bool
    {
        return (bool) $specialist->workSchedule()->delete();
    }
}
