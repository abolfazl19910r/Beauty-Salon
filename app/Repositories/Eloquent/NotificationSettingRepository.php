<?php

namespace App\Repositories\Eloquent;

use App\Models\NotificationSetting;
use App\Repositories\Contracts\NotificationSettingRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class NotificationSettingRepository extends BaseRepository implements NotificationSettingRepositoryInterface
{
    public function __construct(NotificationSetting $model)
    {
        parent::__construct($model);
    }

    public function getAllKeyedByEventKey(?int $salonId): array
    {
        return $this->forSalon($salonId)->get()->keyBy('event_key')->all();
    }

    public function firstOrCreateForEvent(string $eventKey, array $defaults, ?int $salonId): NotificationSetting
    {
        return $this->model->firstOrCreate(['salon_id' => $salonId, 'event_key' => $eventKey], $defaults);
    }

    public function getByEventKeys(array $eventKeys, ?int $salonId): Collection
    {
        return $this->forSalon($salonId)->whereIn('event_key', $eventKeys)->get();
    }

    public function updateOrCreateForEvent(string $eventKey, array $data, ?int $salonId): NotificationSetting
    {
        return $this->model->updateOrCreate(['salon_id' => $salonId, 'event_key' => $eventKey], $data);
    }

    /**
     * هر سالن ردیف‌های خودش را دارد؛ salon_id = null فقط برای بافت بدون سالن (کنسول/سوپرادمین).
     */
    private function forSalon(?int $salonId): Builder
    {
        return $this->model->newQuery()->when(
            $salonId === null,
            fn (Builder $q) => $q->whereNull('salon_id'),
            fn (Builder $q) => $q->where('salon_id', $salonId)
        );
    }
}
