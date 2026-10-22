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

        // ⭐ تصمیم ۵ (۲۰۲۶-۱۰-۲۲): سالن جاری از انتخاب نشست (AdminSalonSelector)، نه همیشه سالن اول
        $selector = app(\App\Support\AdminSalonSelector::class);
        $memberships = $selector->memberships($user);

        if ($memberships->isEmpty()) {
            // عضو هیچ سالنی نیست: کسی که اصلاً دسترسی پنل ندارد (مثلاً متخصص) ۴۰۳، مدیرِ بی‌سالن مثل قبل خروج
            abort_unless($user->hasPermission('access_admin_panel'), 403);

            return $this->logoutWith($request, 'اشتراک سالن شما پایان یافته یا غیرفعال شده است. لطفاً با پشتیبانی تماس بگیرید.');
        }

        $salon = $memberships->firstWhere('id', $selector->chosenId());

        if ($salon === null && $memberships->count() > 1) {
            $selector->forget();

            return redirect()->route('admin.salons.choose');
        }

        $salon ??= $memberships->first();

        if ($salon->is_suspended) {
            if ($memberships->count() > 1) {
                $selector->forget();

                return redirect()->route('admin.salons.choose')->withErrors(['salon' => "سالن «{$salon->name}» غیرفعال شده است."]);
            }

            return $this->logoutWith($request, 'اشتراک سالن شما پایان یافته یا غیرفعال شده است. لطفاً با پشتیبانی تماس بگیرید.');
        }

        session([\App\Support\AdminSalonSelector::SESSION_KEY => $salon->id]);

        // سالنِ برداشته از فهرست چندتایی پرچم «بارگذاری تنبل ممنوع» دارد (N+1 Salon::plan در /admin و /admin/billing، آزمون ۲۰۲۶-۱۰-۲۲)؛
        // سالن جاری یک مدل تنهاست، پس مثل قبل از چندسالنی تنها خوانده می‌شود
        if ($memberships->count() > 1) {
            $salon = \App\Models\Salon::withoutGlobalScopes()->findOrFail($salon->id);
        }

        app(CurrentSalon::class)->set($salon);

        if ($salon->subscription_ends_at->isPast() && ! $request->routeIs('admin.billing.*')) {
            return redirect()->route('admin.billing.index')->withErrors([
                'subscription' => 'اشتراک سالن شما پایان یافته است. لطفاً ابتدا اشتراک را تمدید کنید.',
            ]);
        }

        return $next($request);
    }

    private function logoutWith(Request $request, string $message): Response
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['phone' => $message]);
    }
}
