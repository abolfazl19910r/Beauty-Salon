<?php

namespace App\Services\SuperAdmin;

use App\Models\Salon;
use App\Models\User;
use App\Services\Admin\User\AdminUserService;
use Illuminate\Support\Facades\DB;

/**
 * ⭐ «مالک سالن» در ویرایش سالن ابرمدیر (۲۰۲۶-۱۰-۱۵): نام، شماره‌ی موبایل و رمز مالک (قبلاً فقط هنگام ساخت سالن)؛
 * اگر سالن مالکی ندارد، یکی ساخته می‌شود. همه در «گزارش کارهای ابرمدیر» ثبت می‌شوند (رمز فقط «عوض شد»).
 */
class SalonOwnerService
{
    public function __construct(private readonly AdminUserService $adminUsers, private readonly SuperAdminAudit $audit) {}

    public function owner(Salon $salon): ?User
    {
        return $salon->admins()->wherePivot('role', 'owner')->orderBy('users.id')->first();
    }

    /** @param  array{name: string, phone: string, password?: ?string}  $data */
    public function save(Salon $salon, array $data): User
    {
        return DB::transaction(function () use ($salon, $data) {
            $owner = $this->owner($salon);

            if (! $owner) {
                $owner = $this->adminUsers->create([
                    'name' => $data['name'], 'phone' => $data['phone'], 'password' => $data['password'],
                    'is_admin' => true, 'is_active' => true, 'roles' => [],
                ]);
                $salon->admins()->attach($owner->id, ['role' => 'owner']);
                $this->audit->record('salon.owner', "تعیین مالک تازه برای «{$salon->name}»: {$owner->name} ({$owner->phone})", $salon);

                return $owner;
            }

            $before = ['name' => $owner->name, 'phone' => $owner->phone];
            $owner->name = $data['name'];
            $owner->phone = $data['phone'];
            if (! empty($data['password'])) {
                $owner->password = $data['password'];
                $owner->password_changed_at = now();
            }
            $owner->save();

            $changes = SuperAdminAudit::diff($before, ['name' => $owner->name, 'phone' => $owner->phone]);
            if (! empty($data['password'])) {
                $changes['password'] = ['—', 'عوض شد'];
            }
            if ($changes) {
                $this->audit->record('salon.owner', "ویرایش مالک «{$salon->name}»", $salon, $changes);
            }

            return $owner;
        });
    }
}
