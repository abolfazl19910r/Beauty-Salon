<?php

namespace App\Repositories\Contracts;

use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use Illuminate\Database\Eloquent\Collection;

interface SpecialistScheduleRepositoryInterface extends RepositoryInterface
{
    public function getGroupedBySpecialist(int $specialistId): Collection;

    public function findActiveForDay(int $specialistId, int $dayOfWeek): ?SpecialistSchedule;

    public function replaceForSpecialist(Specialist $specialist, array $schedules): void;
}
