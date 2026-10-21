<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * نوشتن role_user با سالن (تصمیم ۵، ۲۰۲۶-۱۰-۲۲). sync رابطه‌ی belongsToMany لاراول ردیف‌ها را فقط با role_id می‌شناسد و نقشِ همان
 * کاربر در سالن‌های دیگرش را پاک می‌کرد؛ این کلاس فقط ردیف‌های یک سالن را دست می‌زند.
 *
 * salon_id = null: «بدون سالن» (سوپرادمین، کنسول) — رفتار قبلی: همه‌ی نقش‌های کاربر.
 */
final class RoleAssignments
{
    /** @param  list<int>  $roleIds */
    public static function give(User $user, array $roleIds, ?int $salonId): void
    {
        foreach (array_unique(array_map('intval', $roleIds)) as $roleId) {
            $exists = DB::table('role_user')->where('user_id', $user->id)->where('role_id', $roleId)
                ->where(fn ($q) => $salonId === null ? $q->whereNull('salon_id') : $q->where('salon_id', $salonId))
                ->exists();
            if (! $exists) {
                DB::table('role_user')->insert(['user_id' => $user->id, 'role_id' => $roleId, 'salon_id' => $salonId]);
            }
        }

        $user->unsetRelation('roles');
    }

    /**
     * نقش‌های کاربر «در این سالن» دقیقاً همین‌ها شوند. ردیف‌های سالن‌های دیگر و نقش super-admin دست نمی‌خورند. ردیف‌های قدیمی
     * بی‌سالن (null) فقط وقتی جزو این سالن حساب می‌شوند که کاربر عضو سالن دیگری نیست (کاربر تک‌سالنیِ پیش از این تغییر).
     *
     * @param  list<int>  $roleIds
     */
    public static function syncForSalon(User $user, array $roleIds, ?int $salonId): void
    {
        $superAdminRoleId = Role::withoutGlobalScopes()->where('name', 'super-admin')->value('id');

        DB::table('role_user')->where('user_id', $user->id)
            ->when($superAdminRoleId, fn ($q) => $q->where('role_id', '!=', $superAdminRoleId))
            ->where(function ($q) use ($user, $salonId) {
                if ($salonId === null) {
                    $q->whereNull('salon_id');

                    return;
                }
                $q->where('salon_id', $salonId);
                if (self::otherSalonCount($user, $salonId) === 0) {
                    $q->orWhereNull('salon_id');
                }
            })
            ->delete();

        self::give($user, $roleIds, $salonId);
    }

    /** خارج شدن کاربر از یک سالن: نقش‌هایش در همان سالن پاک می‌شود */
    public static function forgetSalon(User $user, int $salonId): void
    {
        DB::table('role_user')->where('user_id', $user->id)->where('salon_id', $salonId)->delete();
        $user->unsetRelation('roles');
    }

    /**
     * پیش از پیوستن کاربر به سالن دوم: نقش‌های بی‌سالنِ قدیمی‌اش (جز super-admin) به سالن فعلی‌اش بسته می‌شوند تا در سالن تازه کار نکنند.
     */
    public static function pinLegacyRowsToOnlySalon(User $user): void
    {
        $salonIds = DB::table('salon_admins')->where('user_id', $user->id)->pluck('salon_id');
        if ($salonIds->count() !== 1) {
            return;
        }

        $superAdminRoleId = Role::withoutGlobalScopes()->where('name', 'super-admin')->value('id');
        DB::table('role_user')->where('user_id', $user->id)->whereNull('salon_id')
            ->when($superAdminRoleId, fn ($q) => $q->where('role_id', '!=', $superAdminRoleId))
            ->update(['salon_id' => (int) $salonIds->first()]);
        $user->unsetRelation('roles');
    }

    private static function otherSalonCount(User $user, int $salonId): int
    {
        return DB::table('salon_admins')->where('user_id', $user->id)->where('salon_id', '!=', $salonId)->count();
    }
}
