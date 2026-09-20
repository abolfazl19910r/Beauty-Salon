<?php

namespace App\Repositories\Contracts;

use App\Models\WalletSetting;

interface WalletSettingRepositoryInterface extends RepositoryInterface
{
    public function first(): ?WalletSetting;
}
