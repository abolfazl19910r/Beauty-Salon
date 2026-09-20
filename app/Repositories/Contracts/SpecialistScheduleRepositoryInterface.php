<?php

namespace App\Repositories\Contracts;

use App\Models\Specialist;
use Illuminate\Database\Eloquent\Collection;

interface SpecialistScheduleRepositoryInterface extends RepositoryInterface
{
    public function getGroupedBySpecialist(int $specialistId): Collection;

    public function replaceForSpecialist(Specialist $specialist, array $schedules): void;
}
