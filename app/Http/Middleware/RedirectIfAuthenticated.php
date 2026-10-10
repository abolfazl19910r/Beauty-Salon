<?php

namespace App\Http\Middleware;

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
                // باید دقیقاً با مقصد بعد از ورود یکی باشد — هر دو از User::homePath() می‌خوانند.
                return redirect(Auth::user()->homePath());
            }
        }

        return $next($request);
    }
}
