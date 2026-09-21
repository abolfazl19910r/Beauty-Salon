<?php

namespace App\Repositories\Contracts;

use App\Models\SupportTicket;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface SupportTicketRepositoryInterface extends RepositoryInterface
{
    public function paginateForUser(int $userId, int $perPage = 15): LengthAwarePaginator;

    public function findForUser(int $id, int $userId): ?SupportTicket;

    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function countByStatus(string $status): int;

    public function getRecentUnassigned(int $limit = 5): Collection;
}
