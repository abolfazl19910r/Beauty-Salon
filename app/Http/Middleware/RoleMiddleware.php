<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param $role
     * @return Response
     */
    public function handle(Request $request, Closure $next, $role): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $roles = is_array($role) ? $role : explode('|', $role);

        if (!auth()->user()->hasAnyRole($roles)) {
            abort(403, 'این عملیات برای شما مجاز نیست.');
        }

        return $next($request);
    }
}
