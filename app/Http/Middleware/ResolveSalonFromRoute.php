<?php

namespace App\Http\Middleware;

use App\Models\Salon;
use App\Support\CurrentSalon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 3, updated in commit 4b):
 * resolves the salon for public customer-facing routes (routes/web/public.php, prefix
 * /s/{salon_slug}). This is the ONLY place a salon is looked up by slug — the admin panel
 * (EnsureAdminSalonActive) resolves it from the logged-in user instead, since an admin never
 * needs the slug in the URL.
 *
 * A missing OR suspended/expired salon both abort with a generic 404 rather than a 403 — a 403
 * would confirm to an outside visitor that a given slug exists but is inaccessible, which is
 * exactly the kind of thing a suspended/former customer's competitor could probe for. 404 gives
 * no such signal either way.
 *
 * URL::defaults(['salon_slug' => ...]) is what makes the /s/{slug} migration NOT require
 * touching the ~52 files that call route('services.index') etc. across this codebase — Laravel
 * fills in any route parameter set here automatically for every named-route URL generated during
 * the rest of THIS request, so those call sites need zero changes. This only covers URLs
 * generated during a live request, though — a queued job or notification built outside any HTTP
 * request (e.g. SendBookingReminderJob) has no request to set a default on, so any future route
 * migrated under this prefix that's linked to from a background job needs its salon_slug passed
 * explicitly at that call site instead. None of the routes moved so far (web/public.php: home,
 * services.index/show, blog.index/show) are currently linked to from a background job — flagged
 * here for whoever migrates web/bookings.php later, since booking reminder jobs very much are.
 */
class ResolveSalonFromRoute
{
    public function handle(Request $request, Closure $next): Response
    {
        $salon = Salon::where('slug', $request->route('salon_slug'))->first();

        if (! $salon || $salon->is_suspended || $salon->subscription_ends_at->isPast()) {
            abort(404);
        }

        app(CurrentSalon::class)->set($salon);
        URL::defaults(['salon_slug' => $salon->slug]);

        return $next($request);
    }
}
