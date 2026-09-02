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

        if (! $salon || $salon->is_suspended || $salon->subscription_ends_at->isPast()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'phone' => 'اشتراک سالن شما پایان یافته یا غیرفعال شده است. لطفاً با پشتیبانی تماس بگیرید.',
            ]);
        }

        app(CurrentSalon::class)->set($salon);

        return $next($request);
    }
}
