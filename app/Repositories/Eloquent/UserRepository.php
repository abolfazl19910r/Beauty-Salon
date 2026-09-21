<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function query(): Builder
    {
        return $this->model->query();
    }

    public function findByPhone(string $phone): ?User
    {
        return $this->model->where('phone', $phone)->first();
    }

    public function findStaffByPhone(string $phone): ?User
    {
        return $this->model->where('phone', $phone)->where('user_type', 'staff')->first();
    }

    public function findCustomerByPhoneInSalon(string $phone, ?int $salonId): ?User
    {
        return $this->model->where('phone', $phone)
            ->where('salon_id', $salonId)
            ->where('user_type', 'customer')
            ->first();
    }

    public function staffPhoneExists(string $phone): bool
    {
        return $this->model->where('user_type', 'staff')->where('phone', $phone)->exists();
    }

    public function searchCustomersInSalon(string $term, ?int $salonId, int $limit = 5): Collection
    {
        return $this->model->query()
            ->where('user_type', 'customer')
            ->where('salon_id', $salonId)
            ->where(function ($query) use ($term) {
                $query->where('phone', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'phone']);
    }

    public function getAdminRecipients(?int $salonId = null): Collection
    {
        $query = $this->model->where('is_admin', true)
            ->orWhereHas('roles.permissions', function ($query) {
                $query->where('name', 'access_admin_panel');
            });

        if ($salonId) {
            $query->whereHas('salons', function ($query) use ($salonId) {
                $query->where('salons.id', $salonId);
            });
        }

        return $query->get();
    }

    public function getSuperAdmins(): Collection
    {
        return $this->model->whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))->get();
    }

    public function getUsersWithoutRole(int $roleId): Collection
    {
        return $this->model->whereDoesntHave('roles', function ($query) use ($roleId) {
            $query->where('role_id', $roleId);
        })->get();
    }

    public function getOptionsOrderedByName(array $columns = ['id', 'name', 'phone']): Collection
    {
        return $this->model->orderBy('name')->get($columns);
    }

    public function countWithTwoFactorEnabled(): int
    {
        return $this->model->where('two_factor_enabled', true)->count();
    }
}
