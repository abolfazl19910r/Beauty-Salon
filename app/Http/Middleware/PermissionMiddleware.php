<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, $permission)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (auth()->user()->is_admin) {
            return $next($request);
        }

        $permissions = is_array($permission) ? $permission : explode('|', $permission);

        foreach ($permissions as $perm) {
            if (auth()->user()->hasPermission($perm)) {
                return $next($request);
            }
        }

        abort(403, 'شما دسترسی لازم برای این عملیات را ندارید.');
    }
}
