<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::user();

                // ⭐ Fix (session 7): this guest middleware must mirror
                // AuthenticatedSessionController::redirectPath() — super-admin has to be
                // checked before the generic is_admin fallback below, otherwise an
                // already-authenticated super-admin (who also has is_admin=true) hitting a
                // guest-only route (e.g. revisiting /login) is sent to '/admin/dashboard'
                // instead of '/superadmin/dashboard'.
                if ($user->hasRole('super-admin')) {
                    return redirect('/superadmin/dashboard');
                }

                if ($user->hasRole('specialists') || $user->hasRole('specialist')) {
                    return redirect('/my-dashboard');
                }

                if ($user->is_admin) {
                    return redirect(RouteServiceProvider::HOME);
                }

                return redirect(RouteServiceProvider::USER_HOME);
            }
        }

        return $next($request);
    }
}
