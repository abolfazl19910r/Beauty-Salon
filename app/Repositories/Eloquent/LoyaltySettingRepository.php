<?php

namespace App\Repositories\Eloquent;

use App\Models\LoyaltySetting;
use App\Repositories\Contracts\LoyaltySettingRepositoryInterface;

class LoyaltySettingRepository extends BaseRepository implements LoyaltySettingRepositoryInterface
{
    public function __construct(LoyaltySetting $model)
    {
        parent::__construct($model);
    }

    public function getValue(string $key, mixed $default = null): mixed
    {
        $setting = $this->model->where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }
}
