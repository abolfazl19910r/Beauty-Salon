<?php

namespace App\Repositories\Eloquent;

use App\Models\Announcement;
use App\Repositories\Contracts\AnnouncementRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AnnouncementRepository extends BaseRepository implements AnnouncementRepositoryInterface
{
    public function __construct(Announcement $model)
    {
        parent::__construct($model);
    }

    public function getActive(): Collection
    {
        return $this->model->active()->byPriority()->get();
    }

    public function getTopActive(): ?Announcement
    {
        return $this->model->active()->orderBy('priority', 'desc')->first();
    }

    public function paginateActive(int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->active()->byPriority()->paginate($perPage);
    }

    public function findActiveOrFail(int $id): Announcement
    {
        return $this->model->active()->findOrFail($id);
    }

    public function paginateAllOrdered(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->orderBy('priority', 'desc')
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    public function countActive(): int
    {
        return $this->model->active()->count();
    }

    public function countPending(): int
    {
        return $this->model->where('is_active', true)
            ->where('published_at', '>', now())
            ->count();
    }

    public function countExpired(): int
    {
        return $this->model->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->count();
    }
}
