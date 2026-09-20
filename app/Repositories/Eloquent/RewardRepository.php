<?php

namespace App\Repositories\Eloquent;

use App\Models\Reward;
use App\Repositories\Contracts\RewardRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class RewardRepository extends BaseRepository implements RewardRepositoryInterface
{
    public function __construct(Reward $model)
    {
        parent::__construct($model);
    }

    public function getActive(): Collection
    {
        return $this->model->where('is_active', true)
            ->orderBy('required_points')
            ->get();
    }

    public function allOrderedByRequiredPoints(): Collection
    {
        return $this->model->orderBy('required_points')->get();
    }

    public function getNextForPoints(int $points): ?Reward
    {
        return $this->model->where('is_active', true)
            ->where('required_points', '>', $points)
            ->orderBy('required_points')
            ->first();
    }

    public function sumUsedCount(): int
    {
        return (int) $this->model->sum('used_count');
    }

    public function countActive(): int
    {
        return $this->model->where('is_active', true)->count();
    }
}
