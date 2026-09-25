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

    public function getAdminRecipients(?int $salonId): Collection;

    /** کاربرهای یک سالن (پیش‌فرض: CurrentSalon): مشتری‌های همون سالن، ادمین‌های salon_admins، و کاربرِ متخصص‌هاش. بدون سالن: همه. */
    public function querySalonMembers(?int $salonId = null): Builder;

    public function isSalonMember(User $user, ?int $salonId = null): bool;

    public function getSuperAdmins(): Collection;

    public function getUsersWithoutRole(int $roleId): Collection;

    /** مشتری‌های یک سالن (پیش‌فرض: CurrentSalon؛ بدون سالن: همه‌ی مشتری‌ها). */
    public function querySalonCustomers(?int $salonId = null): Builder;

    public function getSalonCustomerOptions(array $columns = ['id', 'name', 'phone']): Collection;
}
