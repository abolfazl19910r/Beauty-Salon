<?php

namespace App\Http\Middleware;

use App\Support\CurrentSalon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ⭐ Commit 4b-3 (feat/saas-multi-tenant-salons): discovered while splitting
 * routes/web/specialistprofile.php — the specialist's own dashboard (specialist.* routes) had
 * NO salon-resolving middleware at all before this, on any commit. Most of it is safe by
 * accident (SpecialistBookingManagementController etc. already filter explicitly by
 * `specialist_id`, so BelongsToSalon's global scope being inactive there doesn't leak anything
 * extra), but SpecialistWalletService::getWithdrawableAmount() (and anywhere else that queries a
 * BelongsToSalon model WITHOUT an explicit specialist_id filter, like WalletSetting::first())
 * would silently read whichever salon's settings happen to be first in the table — a real,
 * not hypothetical, cross-tenant read.
 *
 * Mirrors EnsureAdminSalonActive's shape but resolves the salon from the specialist's OWN
 * specialist record (auth()->user()->specialist, via App\Traits\ResolvesSpecialist's same
 * lookup) rather than from salon_admins — a specialist belongs to exactly one salon directly via
 * specialists.salon_id, no pivot needed.
 */
class EnsureSpecialistSalonActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $specialist = $user->specialist;

        // ⭐ Fix (real bug, pre-existing in this middleware, not introduced this session):
        // aborting outright here broke every specialist-panel controller's own designed
        // "no specialist record yet" handling — SpecialistWalletController (and siblings) use
        // ResolvesSpecialist::resolveSpecialist() (nullable, non-throwing) specifically so they
        // can gracefully render 'specialist.profile-not-found' with a normal 200, instead of a
        // hard failure. This middleware ran first and always aborted with 404 before any of that
        // controller logic had a chance to run, turning a friendly "please complete your profile"
        // page into an unconditional 404 for every specialist-panel page. Simply not setting
        // CurrentSalon when there's no specialist preserves the original cross-tenant-safety
        // reasoning this middleware exists for (nothing salon-scoped should run without a
        // specialist to scope it to) while letting the request continue exactly as it did before
        // this middleware was added — routes that truly require a specialist already call
        // resolveSpecialistOrFail()/requireSpecialist() themselves and 404 on their own.
        if ($specialist) {
            app(CurrentSalon::class)->set($specialist->salon);
        }

        return $next($request);
    }
}
