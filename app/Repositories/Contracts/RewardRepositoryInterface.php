<?php

namespace App\Repositories\Contracts;

use App\Models\Reward;
use Illuminate\Database\Eloquent\Collection;

interface RewardRepositoryInterface extends RepositoryInterface
{
    public function getActive(): Collection;

    public function allOrderedByRequiredPoints(): Collection;

    public function getNextForPoints(int $points): ?Reward;

    public function sumUsedCount(): int;

    public function countActive(): int;
}
