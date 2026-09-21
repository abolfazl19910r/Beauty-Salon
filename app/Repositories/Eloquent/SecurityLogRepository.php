<?php

namespace App\Repositories\Eloquent;

use App\Models\SecurityLog;
use App\Repositories\Contracts\SecurityLogRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SecurityLogRepository extends BaseRepository implements SecurityLogRepositoryInterface
{
    public function __construct(SecurityLog $model)
    {
        parent::__construct($model);
    }

    public function query(): Builder
    {
        return $this->model->query();
    }

    public function paginateForUser(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->model->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function paginateForUserWithFilters(int $userId, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->model->where('user_id', $userId)
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('event', $type))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->where('created_at', '>=', \Carbon\Carbon::parse($date)))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->where('created_at', '<=', \Carbon\Carbon::parse($date)))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getRecentForUser(int $userId, int $limit = 5): Collection
    {
        return $this->model->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->take($limit)
            ->get();
    }

    public function getLoginHistoryForUser(int $userId, int $limit = 10): Collection
    {
        return $this->model->where('user_id', $userId)
            ->where('event', 'login_attempt')
            ->orderByDesc('created_at')
            ->take($limit)
            ->get();
    }

    public function getLastSuccessfulLoginAt(int $userId): mixed
    {
        return $this->model->where('user_id', $userId)
            ->where('event', 'login_attempt')
            ->where('level', 'info')
            ->latest('created_at')
            ->value('created_at');
    }

    public function countSince(\DateTimeInterface $since): int
    {
        return $this->model->where('created_at', '>=', $since)->count();
    }

    public function countWarningsSince(\DateTimeInterface $since): int
    {
        return $this->model->where('level', 'warning')->where('created_at', '>=', $since)->count();
    }

    public function countFailedLoginAttemptsSince(\DateTimeInterface $since): int
    {
        return $this->model->where('event', 'login_attempt')
            ->where('level', 'warning')
            ->where('created_at', '>=', $since)
            ->count();
    }

    public function countWarningsForUserSince(int $userId, \DateTimeInterface $since): int
    {
        return $this->model->where('user_id', $userId)
            ->where('created_at', '>=', $since)
            ->where('level', 'warning')
            ->count();
    }

    public function countLoginAttemptsForUserSince(int $userId, \DateTimeInterface $since): int
    {
        return $this->model->where('user_id', $userId)
            ->where('event', 'login_attempt')
            ->where('created_at', '>=', $since)
            ->count();
    }
}
