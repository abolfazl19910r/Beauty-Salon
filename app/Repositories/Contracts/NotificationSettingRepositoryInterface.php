<?php

namespace App\Repositories\Contracts;

use App\Models\NotificationSetting;
use Illuminate\Database\Eloquent\Collection;

interface NotificationSettingRepositoryInterface extends RepositoryInterface
{
    /**
     * @return array<string, NotificationSetting>
     */
    public function getAllKeyedByEventKey(?int $salonId): array;

    public function firstOrCreateForEvent(string $eventKey, array $defaults, ?int $salonId): NotificationSetting;

    public function getByEventKeys(array $eventKeys, ?int $salonId): Collection;

    public function updateOrCreateForEvent(string $eventKey, array $data, ?int $salonId): NotificationSetting;
}
