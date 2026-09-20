<?php

namespace App\Repositories\Eloquent;

use App\Models\Holiday;
use App\Models\Specialist;
use App\Repositories\Contracts\HolidayRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class HolidayRepository extends BaseRepository implements HolidayRepositoryInterface
{
    public function __construct(Holiday $model)
    {
        parent::__construct($model);
    }

    public function getForSpecialist(int $specialistId): Collection
    {
        return $this->model->where('specialist_id', $specialistId)->orderBy('date')->get();
    }

    public function getUpcomingForSpecialist(int $specialistId): Collection
    {
        return $this->model->where('specialist_id', $specialistId)->upcoming()->get();
    }

    public function findOnDate(int $specialistId, string $date): ?Holiday
    {
        return $this->model->where('specialist_id', $specialistId)->whereDate('date', $date)->first();
    }

    public function existsOnDate(int $specialistId, string $date): bool
    {
        return $this->model->where('specialist_id', $specialistId)->whereDate('date', $date)->exists();
    }

    public function createForSpecialist(Specialist $specialist, array $data): Holiday
    {
        return $specialist->holidays()->create($data);
    }
}
