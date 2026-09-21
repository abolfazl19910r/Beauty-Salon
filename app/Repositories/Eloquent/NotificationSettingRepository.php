<?php

namespace App\Repositories\Eloquent;

use App\Models\NotificationSetting;
use App\Repositories\Contracts\NotificationSettingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class NotificationSettingRepository extends BaseRepository implements NotificationSettingRepositoryInterface
{
    public function __construct(NotificationSetting $model)
    {
        parent::__construct($model);
    }

    public function getAllKeyedByEventKey(): array
    {
        return $this->model->all()->keyBy('event_key')->all();
    }

    public function firstOrCreateForEvent(string $eventKey, array $defaults): NotificationSetting
    {
        return $this->model->firstOrCreate(['event_key' => $eventKey], $defaults);
    }

    public function getByEventKeys(array $eventKeys): Collection
    {
        return $this->model->whereIn('event_key', $eventKeys)->get();
    }

    public function updateOrCreateForEvent(string $eventKey, array $data): NotificationSetting
    {
        return $this->model->updateOrCreate(['event_key' => $eventKey], $data);
    }
}
