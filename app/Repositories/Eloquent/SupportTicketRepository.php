<?php

namespace App\Repositories\Eloquent;

use App\Models\SupportTicket;
use App\Repositories\Contracts\SupportTicketRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SupportTicketRepository extends BaseRepository implements SupportTicketRepositoryInterface
{
    public function __construct(SupportTicket $model)
    {
        parent::__construct($model);
    }

    public function paginateForUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where('user_id', $userId)
            ->latest()
            ->paginate($perPage);
    }

    public function findForUser(int $id, int $userId): ?SupportTicket
    {
        return $this->model->where('id', $id)->where('user_id', $userId)->first();
    }

    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->model->with(['user', 'assignedTo'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['priority'] ?? null, fn ($q, $priority) => $q->where('priority', $priority))
            ->when($filters['category'] ?? null, fn ($q, $category) => $q->where('category', $category))
            ->when($filters['assigned'] ?? null, function ($q, $assigned) {
                if ($assigned === 'unassigned') {
                    $q->whereNull('assigned_to');
                } elseif ($assigned === 'me') {
                    $q->where('assigned_to', auth()->id());
                }
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function countByStatus(string $status): int
    {
        return $this->model->where('status', $status)->count();
    }

    public function getRecentUnassigned(int $limit = 5): Collection
    {
        return $this->model->whereNull('assigned_to')
            ->whereNotIn('status', ['resolved', 'closed'])
            ->latest()
            ->take($limit)
            ->get();
    }
}
