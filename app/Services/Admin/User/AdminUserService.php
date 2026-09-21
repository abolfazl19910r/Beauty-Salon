<?php

namespace App\Services\Admin\User;

use App\Models\Salon;
use App\Models\User;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RoleRepositoryInterface $roleRepository,
    ) {}

    /**
     * ⭐ فاز ۲ SaaS، محور «۲. چند ادمین برای یک سالن»: وقتی $data['salon'] (یک Salon) و
     * $data['salon_role'] ('owner'|'staff') هر دو داده شده باشن، کاربر تازه‌ساز به
     * salon_admins همون سالن وصل می‌شه. SuperAdminService::createSalonWithAdmin() عمداً این دو
     * کلید رو نمی‌فرسته — خودش با نقش 'owner' جدا attach می‌کنه (منطق سالن/اشتراک/سهمیه که به
     * این سرویس ربطی نداره) — پس رفتار اون مسیر بدون تغییر می‌مونه.
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = $this->userRepository->create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'is_admin' => $data['is_admin'],
                'phone_verified_at' => $data['is_active'] ? now() : null,
                'user_type' => 'staff',
            ]);

            if (! empty($data['roles'])) {
                $user->roles()->sync($this->filterAssignableRoles($data['roles'] ?? []));
            }

            if (! empty($data['salon']) && ! empty($data['salon_role'])) {
                $data['salon']->admins()->attach($user->id, ['role' => $data['salon_role']]);
            }

            return $user;
        });
    }

    /**
     * @param  Salon|null  $salon  فاز ۲ SaaS، محور ۲: وقتی داده بشه (owner یک ادمین سالن خودش رو
     *                             ویرایش می‌کنه) و $data['salon_role'] هم داده شده باشه، نقش این
     *                             کاربر در همون سالن (owner/staff) هم‌زمان به‌روز می‌شه — با محافظت
     *                             «آخرین owner نمی‌تونه تنزل بگیره» (guardNotLastOwner()).
     */
    public function update(User $user, array $data, ?Salon $salon = null): User
    {
        return DB::transaction(function () use ($user, $data, $salon) {
            if ($salon && ! empty($data['salon_role']) && $data['salon_role'] === 'staff') {
                $this->guardNotLastOwner($user, $salon, 'نقش او در سالن تغییر کند');
            }

            $user->update([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'is_admin' => $data['is_admin'],
                'phone_verified_at' => $data['is_active']
                    ? ($user->phone_verified_at ?? now())
                    : null,
            ]);

            $user->roles()->sync($this->filterAssignableRoles($data['roles'] ?? []));

            if ($salon && ! empty($data['salon_role'])) {
                $salon->admins()->updateExistingPivot($user->id, ['role' => $data['salon_role']]);
            }

            return $user;
        });
    }

    public function delete(User $user, ?Salon $salon = null): int
    {
        $bookingsCount = $user->bookings()->count();
        if ($bookingsCount > 0) {
            return $bookingsCount;
        }

        if ($salon) {
            $this->guardNotLastOwner($user, $salon, 'حذف شود');
        }

        $user->roles()->detach();
        $user->salons()->detach();
        $user->delete();

        return 0;
    }

    public function updateStatus(User $user, bool $activate, ?Salon $salon = null): void
    {
        if (! $activate && $salon) {
            $this->guardNotLastOwner($user, $salon, 'غیرفعال شود');
        }

        $user->forceFill([
            'phone_verified_at' => $activate ? ($user->phone_verified_at ?? now()) : null,
        ])->save();
    }

    public function resetPassword(User $user, string $password): void
    {
        $user->forceFill(['password' => Hash::make($password)])->save();
    }

    public function syncRoles(User $user, array $roles): void
    {
        // ⭐ Fix (session 7): was referencing an undefined $data variable instead of the
        // $roles parameter — every call silently stripped ALL of the target user's roles
        // regardless of what was submitted, since $data['roles'] ?? [] always resolved to [].
        $user->roles()->sync($this->filterAssignableRoles($roles));
    }

    /**
     * ⭐ فاز ۲ SaaS، محور ۲ (تصمیم تأییدشده): سالن باید همیشه حداقل یک owner فعال داشته باشد —
     * حذف/تنزل/غیرفعال‌سازی آخرین owner مسدود می‌شود تا سالن هیچ‌وقت بدون ادمین قابل‌دسترس نماند.
     *
     * @throws \App\Exceptions\DomainException
     */
    private function guardNotLastOwner(User $user, Salon $salon, string $action): void
    {
        $isOwner = $salon->admins()
            ->wherePivot('user_id', $user->id)
            ->wherePivot('role', 'owner')
            ->exists();

        if (! $isOwner) {
            return;
        }

        $ownerCount = $salon->admins()->wherePivot('role', 'owner')->count();

        if ($ownerCount <= 1) {
            throw \App\Exceptions\LastSalonOwnerException::cannot($action);
        }
    }

    private function filterAssignableRoles(array $roleIds): array
    {
        if (auth()->user()?->hasRole('super-admin')) {
            return $roleIds;
        }

        $superRoleId = $this->roleRepository->getIdByName('super-admin');

        return array_values(array_diff($roleIds, array_filter([$superRoleId])));
    }
}
