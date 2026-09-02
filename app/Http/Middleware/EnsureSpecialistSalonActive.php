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

        if (! $specialist) {
            abort(404, 'رکورد متخصص برای این حساب کاربری یافت نشد.');
        }

        app(CurrentSalon::class)->set($specialist->salon);

        return $next($request);
    }
}
