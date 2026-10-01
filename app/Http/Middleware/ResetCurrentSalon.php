<?php

namespace App\Http\Middleware;

use App\Support\CurrentSalon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * اولین middleware هر درخواست: سالن جاری خالی شروع می‌شود و فقط middlewareهای سالن (ResolveSalonFromRoute،
 * EnsureAdminSalonActive، EnsureSpecialistSalonActive) یا allSalons() سوپرادمین آن را تعیین می‌کنند (۲۰۲۶-۱۰-۰۱).
 * در production هر درخواست از قبل نمونه‌ی تازه دارد؛ این برای پردازه‌های طولانی (Octane) و تست‌هاست که درخواست‌ها
 * همان نمونه‌ی برنامه را شریکند — وگرنه سالنِ ست‌شده‌ی بیرون از درخواست، نبودِ سالن در حالت سخت‌گیر را پنهان می‌کرد.
 */
class ResetCurrentSalon
{
    public function handle(Request $request, Closure $next): Response
    {
        app(CurrentSalon::class)->clear();

        return $next($request);
    }
}
