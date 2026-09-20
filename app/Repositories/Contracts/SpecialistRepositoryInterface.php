<?php

namespace App\Repositories\Contracts;

use App\Models\Specialist;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface SpecialistRepositoryInterface extends RepositoryInterface
{
    public function paginateWithFilters(array $filters, int $perPage = 10): LengthAwarePaginator;

    public function searchPaginated(array $filters, int $perPage = 10): LengthAwarePaginator;

    public function getTopRated(int $limit = 10): Collection;

    public function findByPhone(string $phone): ?Specialist;

    public function getSalonIdIgnoringScopes(int $specialistId): ?int;
}
