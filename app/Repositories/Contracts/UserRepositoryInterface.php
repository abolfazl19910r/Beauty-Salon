<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface extends RepositoryInterface
{
    public function query(): Builder;

    public function findByPhone(string $phone): ?User;

    public function findStaffByPhone(string $phone): ?User;

    public function findCustomerByPhoneInSalon(string $phone, ?int $salonId): ?User;

    public function staffPhoneExists(string $phone): bool;

    public function searchCustomersInSalon(string $term, ?int $salonId, int $limit = 5): Collection;

    public function getAdminRecipients(?int $salonId = null): Collection;

    public function getSuperAdmins(): Collection;

    public function getUsersWithoutRole(int $roleId): Collection;

    public function getOptionsOrderedByName(array $columns = ['id', 'name', 'phone']): Collection;

    public function countWithTwoFactorEnabled(): int;
}
