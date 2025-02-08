<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionSecurity
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            config(['session.lifetime' => 30]);

            if (time() - session('last_activity', 0) > 1800) { // 30 minutes
                Auth::logout();
                return redirect()->route('login')
                    ->with('message', 'جلسه شما به دلیل عدم فعالیت منقضی شد. لطفا مجددا وارد شوید.');
            }

            if (session('user_ip') !== $request->ip()) {
                Auth::logout();
                return redirect()->route('login')
                    ->with('message', 'جلسه شما به دلیل تغییر IP منقضی شد.');
            }

            session(['last_activity' => time()]);
            session(['user_ip' => $request->ip()]);
        }

        return $next($request);
    }
}
