<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'لطفا وارد شوید.'], 401);
            }
            return redirect()->route('login');
        }

        $user = auth()->user();

        if (!$user->is_admin) {
            abort(403, 'شما دسترسی به پنل مدیریت ندارید.');
        }

        if ($user->hasRole('specialists')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'متخصصین نمی‌توانند به پنل مدیریت دسترسی داشته باشند.'], 403);
            }
            return redirect()->route('specialist.dashboard')
                ->with('error', 'شما باید از پنل متخصص استفاده کنید.');
        }

        return $next($request);
    }
}
