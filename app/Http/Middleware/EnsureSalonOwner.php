<?php

namespace App\Http\Middleware;

use App\Support\CurrentSalon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ⭐ فاز ۲ SaaS، محور «۲. چند ادمین برای یک سالن» (تصمیم تأییدشده با ابوالفضل، ۲۰۲۶-۰۹-۱۹):
 * مدیریت ادمین‌های یک سالن (`/admin/users/*` — افزودن/ویرایش/حذف owner یا staff دیگر، تغییر
 * نقش‌ها/دسترسی‌ها) فقط برای owner همون سالن مجازه، نه staff — چون اگه این دسترسی به staff هم
 * داده بشه، احتمال سوءاستفاده از دسترسی‌ها هست (مثلاً یک staff بتونه به خودش یا شخص دیگری
 * دسترسی owner/مالی بده).
 *
 * عمداً یک middleware جدا از EnsureAdminSalonActive («سالن فعاله؟») و PermissionMiddleware
 * («این permission رو داره؟») — این یک قانون *مالکیت سالن* است (salon_admins.role==='owner'،
 * نه یک permission سیستمی قابل‌تخصیص)، دقیقاً هم‌الگو با این‌که EnsureSuperAdmin از hasRole()
 * استفاده می‌کنه نه hasPermission() (به‌خاطر bypass کامل is_admin روی hasPermission()).
 *
 * سوپر ادمین طبق الگوی همیشگی این پروژه (EnsureAdminSalonActive و غیره) کاملاً بای‌پس می‌شه —
 * او خودش از پنل /superadmin به همه‌ی سالن‌ها دسترسی داره؛ اگه مستقیم به /admin/users بیاد،
 * چون CurrentSalon براش هیچ‌وقت ست نمی‌شه، بدون فیلتر همه رو می‌بینه (رفتار یکسان با بقیه‌ی
 * کنترلرهای صاحب‌داده).
 */
class EnsureSalonOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        $salon = app(CurrentSalon::class)->get();
        $isOwner = $salon && $salon->admins()
            ->wherePivot('user_id', $user->id)
            ->wherePivot('role', 'owner')
            ->exists();

        if (! $isOwner) {
            abort(403, 'این بخش فقط برای مالک سالن در دسترس است.');
        }

        return $next($request);
    }
}
