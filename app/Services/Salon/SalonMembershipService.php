<?php

namespace App\Services\Salon;

use App\Models\Salon;
use App\Models\User;
use App\Support\RoleAssignments;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * ⭐ عضویت یک حساب کادر در چند سالن (تصمیم ۵، ۲۰۲۶-۱۰-۲۲؛ ۵-۳ الف: مالک و منشی هر دو).
 *
 * یک شماره = یک حساب کادر در کل پلتفرم (همان قانون یکتایی قبلی)؛ عضویت هر سالن با نقش خودش (owner/staff) در salon_admins
 * و نقش‌های همان سالن در role_user.salon_id. چون حساب مشترک است، مالکِ یک سالن نام، شماره، رمز و فعال/غیرفعال بودن عضوی
 * را که در سالن دیگری هم هست عوض نمی‌کند (isShared)؛ «حذف» او فقط برداشتن از همان سالن است (leave).
 */
class SalonMembershipService
{
    public function existingStaff(string $phone): ?User
    {
        return User::query()->where('phone', $phone)->where('user_type', 'staff')->first();
    }

    /**
     * @param  'owner'|'staff'  $role
     * @param  list<int>  $roleIds  نقش‌های همین سالن (برای منشی)
     *
     * @throws ValidationException
     */
    public function addExisting(User $user, Salon $salon, string $role, array $roleIds = [], string $field = 'phone'): void
    {
        if ($user->hasRole('super-admin')) {
            throw ValidationException::withMessages([$field => 'این شماره مال حساب مدیر پلتفرم است و به سالن اضافه نمی‌شود.']);
        }

        if ($user->salonRoleIn($salon->id) !== null) {
            throw ValidationException::withMessages([$field => 'این شماره همین حالا عضو پنل همین سالن است.']);
        }

        DB::transaction(function () use ($user, $salon, $role, $roleIds) {
            // نقش‌های بی‌سالنِ قدیمی‌اش به سالن فعلی‌اش بسته شوند تا در این سالن کار نکنند
            RoleAssignments::pinLegacyRowsToOnlySalon($user);

            $salon->admins()->attach($user->id, ['role' => $role]);

            // مالک بودن در هر سالن به ستون is_admin نیاز دارد؛ در سالن‌هایی که منشی است User::isAdmin آن را false می‌کند
            if ($role === 'owner' && ! $user->getRawOriginal('is_admin')) {
                $user->forceFill(['is_admin' => true])->save();
            }

            RoleAssignments::give($user, $roleIds, $salon->id);
            $user->forgetSalonRoles();
        });
    }

    /** عضو سالن دیگری هم هست؟ (حساب مشترک) */
    public function isShared(User $user, ?Salon $salon): bool
    {
        return $salon !== null
            && DB::table('salon_admins')->where('user_id', $user->id)->where('salon_id', '!=', $salon->id)->exists();
    }

    /** برداشتن از یک سالن (حساب و عضویت‌های دیگر می‌مانند) */
    public function leave(User $user, Salon $salon): void
    {
        DB::transaction(function () use ($user, $salon) {
            $salon->admins()->detach($user->id);
            RoleAssignments::forgetSalon($user, $salon->id);
            $user->forgetSalonRoles();
        });
    }
}
