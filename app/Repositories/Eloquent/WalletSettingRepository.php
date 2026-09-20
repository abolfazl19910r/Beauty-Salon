<?php

namespace App\Repositories\Eloquent;

use App\Models\WalletSetting;
use App\Repositories\Contracts\WalletSettingRepositoryInterface;

class WalletSettingRepository extends BaseRepository implements WalletSettingRepositoryInterface
{
    public function __construct(WalletSetting $model)
    {
        parent::__construct($model);
    }

    public function first(): ?WalletSetting
    {
        return $this->model->first();
    }
}
