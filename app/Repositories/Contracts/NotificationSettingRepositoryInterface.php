<?php

namespace App\Repositories\Contracts;

use App\Models\NotificationSetting;
use Illuminate\Database\Eloquent\Collection;

interface NotificationSettingRepositoryInterface extends RepositoryInterface
{
    /**
     * @return array<string, NotificationSetting>
     */
    public function getAllKeyedByEventKey(): array;

    public function firstOrCreateForEvent(string $eventKey, array $defaults): NotificationSetting;

    public function getByEventKeys(array $eventKeys): Collection;

    public function updateOrCreateForEvent(string $eventKey, array $data): NotificationSetting;
}
