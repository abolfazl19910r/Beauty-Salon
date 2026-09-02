<?php

namespace App\Http\Middleware;

use App\Support\CurrentSalon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * ⭐ Commit 4b-2 (feat/saas-multi-tenant-salons), prerequisite for migrating any authenticated
 * customer route under /s/{slug}: ResolveSalonFromRoute only resolves WHICH salon the current
 * URL belongs to and blocks a suspended/expired one — it never checks whether the person
 * currently logged in is actually a customer OF that salon. Without this, a customer logged in
 * to salon A's account could open salon B's /s/{slug}/dashboard and act with salon A's session
 * against salon B's pages — auth() alone can't catch this, since it only knows "is someone
 * logged in", not "does this session belong here".
 *
 * Applied ONLY inside the authenticated group nested under /s/{salon_slug} (routes/web.php),
 * after 'auth' and 'salon.resolve' have already run — this checks a logged-in customer's
 * salon_id against the CurrentSalon that ResolveSalonFromRoute just set. Deliberately does
 * nothing for staff (admin/specialist) — they never reach these customer routes at all, since
 * they're never linked as a 'customer' user_type, so this check is a no-op for them by
 * construction rather than needing its own bypass branch.
 *
 * Full logout (not just a redirect) on mismatch: Laravel's session/guard model is one login per
 * browser session, so "logged in to salon A" and "browsing salon B" existing at once is already
 * an inconsistent state — leaving the stale session intact and just redirecting to salon B's
 * login would let the person bounce back to salon A's pages via browser back/forward while
 * still nominally "authenticated" there. Logging out fully forces a clean re-login scoped to
 * whichever salon they actually want next.
 */
class EnsureCustomerBelongsToSalon
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $salon = app(CurrentSalon::class)->get();

        if ($user && $user->user_type === 'customer' && $salon && $user->salon_id !== $salon->id) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('salon.login', ['salon_slug' => $salon->slug])
                ->withErrors(['phone' => 'لطفا با حساب کاربری همین سالن وارد شوید.']);
        }

        return $next($request);
    }
}
