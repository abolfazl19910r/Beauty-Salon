<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Support\CurrentSalon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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

    public function getAdminRecipients(?int $salonId): Collection
    {
        if (! $salonId) {
            return new Collection;
        }

        return $this->model
            ->whereHas('salons', fn ($query) => $query->where('salons.id', $salonId))
            ->where(function ($query) {
                $query->where('is_admin', true)
                    ->orWhereHas('roles.permissions', fn ($q) => $q->where('name', 'access_admin_panel'));
            })
            ->get();
    }

    /**
     * «عضو سالن»: مشتری همین سالن، مدیر در salon_admins، کاربر متخصص همین سالن (user_id) یا کاربر staff با تلفن یک متخصص همین سالن
     * (متخصص‌های حذف‌نرم‌شده هم — مثل قبل).
     *
     * ⭐ کارایی (۲۰۲۶-۰۹-۳۰): این چهار منبع قبلاً با OR/EXISTS در یک شرط روی users بودند؛ MariaDB با آن شکل هیچ ایندکسی نمی‌تواند
     * بزند و برای هر شمارش کل users پلتفرم را می‌خواند (داده‌ی ۱۰۰۰ سالن: ۱۵۷ هزار ردیف، ۸۶–۱۴۴ms، رشد با کل پلتفرم). حالا هر منبع
     * جدا با ایندکس خودش خوانده و در یک جدول مشتق UNION می‌شود (~۱٫۵ms). ⚠️ `id IN (A UNION B …)` بدون جدول مشتق روی MariaDB
     * به زیرکوئری وابسته تبدیل می‌شد و بدتر بود (۳ ثانیه) — FROM (…) AS salon_members لازم است.
     */
    public function querySalonMembers(?int $salonId = null): Builder
    {
        $salonId ??= app(CurrentSalon::class)->id();
        $query = $this->model->newQuery();

        if ($salonId === null) {
            return $query;
        }

        // pgsql ستون تولیدی staff_phone_key ندارد (ایندکس جزئی دارد)
        $staffByPhone = DB::getDriverName() === 'pgsql'
            ? DB::table('users as su')->join('specialists as sp', 'su.phone', '=', 'sp.phone')->where('su.user_type', 'staff')
            : DB::table('users as su')->join('specialists as sp', 'su.staff_phone_key', '=', 'sp.phone');

        $sources = DB::table('users')->select('id')->where('user_type', 'customer')->where('salon_id', $salonId)
            ->union(DB::table('salon_admins')->select('user_id')->where('salon_id', $salonId))
            ->union(DB::table('specialists')->select('user_id')->where('salon_id', $salonId)->whereNotNull('user_id'))
            ->union($staffByPhone->select('su.id')->where('sp.salon_id', $salonId));

        return $query->whereIn('users.id', DB::query()->fromSub($sources, 'salon_members')->select('salon_members.id'));
    }

    public function isSalonMember(User $user, ?int $salonId = null): bool
    {
        return $this->querySalonMembers($salonId)->whereKey($user->id)->exists();
    }

    public function getSuperAdmins(): Collection
    {
        return $this->model->whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))->get();
    }

    public function getUsersWithoutRole(int $roleId): Collection
    {
        return $this->querySalonMembers()->whereDoesntHave('roles', function ($query) use ($roleId) {
            $query->where('role_id', $roleId);
        })->get();
    }

    public function querySalonCustomers(?int $salonId = null): Builder
    {
        $salonId ??= app(CurrentSalon::class)->id();

        return $this->model->newQuery()
            ->where('user_type', 'customer')
            ->when($salonId !== null, fn (Builder $q) => $q->where('salon_id', $salonId));
    }

    public function getSalonCustomerOptions(array $columns = ['id', 'name', 'phone']): Collection
    {
        return $this->querySalonCustomers()->orderBy('name')->get($columns);
    }
}
