<?php

namespace App\Repositories\Contracts;

use App\Models\SalonSmsUsage;

interface SalonSmsUsageRepositoryInterface extends RepositoryInterface
{
    public function firstOrCreateForPeriod(int $salonId, string $period): SalonSmsUsage;
}
