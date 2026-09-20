<?php

namespace App\Repositories\Contracts;

interface LoyaltySettingRepositoryInterface extends RepositoryInterface
{
    public function getValue(string $key, mixed $default = null): mixed;
}
