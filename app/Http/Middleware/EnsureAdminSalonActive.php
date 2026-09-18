<?php

namespace App\Http\Middleware;

use App\Support\CurrentSalon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 3): protects /admin/* (the
 * per-salon admin panel — as opposed to /superadmin/*, which EnsureSuperAdmin protects).
 *
 * Super admins bypass this entirely (same hasRole('super-admin') check as EnsureSuperAdmin, for
 * the same reason — see that class's docblock) and deliberately do NOT get a CurrentSalon bound
 * here: a super admin opening /admin directly (rather than /superadmin) sees every salon's data
 * unfiltered, consistent with BelongsToSalon's "no CurrentSalon set = no filter" behavior — there
 * is no single salon to scope them to anyway, since they aren't in any salon's salon_admins row.
 *
 * For an ordinary admin, $user->salons()->first() is always exactly one row in v1 (see
 * User::salons(), salon_admins pivot) — a user with zero rows there (was never linked to a
 * salon) is treated the same as one whose salon is suspended or expired, rather than silently
 * falling through with no CurrentSalon bound, which would have let their queries run
 * completely unscoped.
 *
 * ⭐ فاز ۲، محور «۱. پرداخت آنلاین و صورتحساب» (تصمیم تأییدشده با ابوالفضل، ۲۰۲۶-۰۹-۱۹): قبل از
 * این فیچر، هر دو حالت suspend دستی و انقضای تاریخ یکسان مدیریت می‌شدند — logout کامل + پیام
 * «با پشتیبانی تماس بگیرید». این رفتار فیچر «تمدید آنلاین» را برای سالن *منقضی‌شده* عملاً
 * غیرقابل‌استفاده می‌کرد، چون ادمین اصلاً نمی‌توانست به هیچ صفحه‌ای زیر /admin (از جمله صفحه‌ی
 * تمدید) برسد تا تمدید کند.
 *
 * راه‌حل: این دو حالت را از هم جدا کردیم —
 *   - `is_suspended` (اقدام دستی/آگاهانه‌ی سوپر ادمین): همان رفتار قبلی بدون تغییر — logout کامل.
 *     تمدید آنلاین به‌درستی نمی‌تواند یک suspend دستی را دور بزند.
 *   - فقط `subscription_ends_at` گذشته (و suspend نشده): به‌جای logout، فقط CurrentSalon ست
 *     می‌شود و درخواست به‌جز مسیرهای `admin.billing.*` مسدود می‌شود (redirect به صفحه‌ی تمدید) —
 *     یعنی ادمین می‌تواند وارد شود، وضعیت اشتراکش را ببیند و آنلاین تمدید کند، ولی نمی‌تواند به
 *     هیچ بخش دیگری از پنل دسترسی داشته باشد تا وقتی واقعاً تمدید کند.
 */
class EnsureAdminSalonActive
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

        $salon = $user->salons()->first();

        if (! $salon || $salon->is_suspended) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'phone' => 'اشتراک سالن شما پایان یافته یا غیرفعال شده است. لطفاً با پشتیبانی تماس بگیرید.',
            ]);
        }

        app(CurrentSalon::class)->set($salon);

        if ($salon->subscription_ends_at->isPast() && ! $request->routeIs('admin.billing.*')) {
            return redirect()->route('admin.billing.index')->withErrors([
                'subscription' => 'اشتراک سالن شما پایان یافته است. لطفاً ابتدا اشتراک را تمدید کنید.',
            ]);
        }

        return $next($request);
    }
}
