<?php

namespace App\Repositories\Eloquent;

use App\Models\SalonSmsUsage;
use App\Repositories\Contracts\SalonSmsUsageRepositoryInterface;

class SalonSmsUsageRepository extends BaseRepository implements SalonSmsUsageRepositoryInterface
{
    public function __construct(SalonSmsUsage $model)
    {
        parent::__construct($model);
    }

    public function firstOrCreateForPeriod(int $salonId, string $period): SalonSmsUsage
    {
        return $this->model->firstOrCreate(
            ['salon_id' => $salonId, 'period' => $period],
            ['used_count' => 0]
        );
    }
}
