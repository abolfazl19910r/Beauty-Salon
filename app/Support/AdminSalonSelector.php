<?php

namespace App\Support;

use App\Models\Salon;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ⭐ مالک/منشی چندسالنی (تصمیم ۵، ۲۰۲۶-۱۰-۲۲): کدام سالن در پنل مدیر «جاری» است.
 *
 * پیش‌تر EnsureAdminSalonActive همیشه سالن اول را برمی‌داشت و سالن دوم از پنل در دسترس نبود. حالا انتخاب در نشست
 * (SESSION_KEY) است؛ عضو یک سالن مثل قبل مستقیم وارد همان سالن می‌شود، عضو چند سالن بعد از ورود صفحه‌ی «سالن‌های من» را می‌بیند.
 * هر بار از جدول عضویت خوانده می‌شود: سالنی که عضویتش برداشته شد، با دستکاری نشست هم باز نمی‌شود.
 */
final class AdminSalonSelector
{
    public const SESSION_KEY = 'admin_salon_id';

    /** @return Collection<int, Salon> عضویت‌ها، به ترتیب پیوستن (pivot->role: owner|staff) */
    public function memberships(User $user): Collection
    {
        return $user->salons()->withoutGlobalScopes()->orderBy('salon_admins.created_at')->orderBy('salons.id')->get();
    }

    public function chosenId(): ?int
    {
        $id = session(self::SESSION_KEY);

        return is_numeric($id) ? (int) $id : null;
    }

    public function choose(User $user, int $salonId): ?Salon
    {
        $salon = $this->memberships($user)->firstWhere('id', $salonId);
        if ($salon === null || $salon->is_suspended) {
            return null;
        }

        session([self::SESSION_KEY => $salon->id]);
        $user->forgetSalonRoles();

        return $salon;
    }

    public function forget(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /** @return array<int, int> شمار اعلان‌های خوانده‌نشده‌ی هر سالن این کاربر */
    public function unreadCounts(User $user): array
    {
        return DB::table('user_notifications')
            ->where('notifiable_type', $user->getMorphClass())->where('notifiable_id', $user->id)
            ->whereNull('read_at')->whereNotNull('salon_id')
            ->selectRaw('salon_id, count(*) as c')->groupBy('salon_id')
            ->pluck('c', 'salon_id')->map(fn ($c) => (int) $c)->all();
    }
}
