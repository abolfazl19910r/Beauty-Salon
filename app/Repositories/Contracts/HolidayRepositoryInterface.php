<?php

namespace App\Repositories\Contracts;

use App\Models\Holiday;
use App\Models\Specialist;
use Illuminate\Database\Eloquent\Collection;

interface HolidayRepositoryInterface extends RepositoryInterface
{
    public function getForSpecialist(int $specialistId): Collection;

    public function getUpcomingForSpecialist(int $specialistId): Collection;

    public function findOnDate(int $specialistId, string $date): ?Holiday;

    public function existsOnDate(int $specialistId, string $date): bool;

    public function createForSpecialist(Specialist $specialist, array $data): Holiday;
}
