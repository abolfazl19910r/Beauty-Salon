<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 3): protects /superadmin/*.
 *
 * ⚠️ Deliberately checks $user->hasRole('super-admin') — NOT $user->hasPermission('super_admin').
 * User::hasPermission() has a blanket bypass: `if ($this->is_admin) return true;` before it even
 * looks at permissions. That means EVERY existing admin (is_admin=true) would automatically pass
 * a hasPermission('super_admin') check too, regardless of what permission string is asked for —
 * so building this middleware around hasPermission() would have made every current salon owner a
 * super admin the moment this shipped. hasRole() has no such bypass (plain
 * `$this->roles->contains('name', $role)`), so it's the only one of the two actually safe to use
 * here. This is the same class of bug this project has already fixed multiple times around
 * is_admin (R-AdminLoyalty, R-AdminForms, R-Events) — caught here before shipping rather than
 * after.
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user || ! $user->hasRole('super-admin')) {
            abort(403, 'دسترسی فقط برای سوپر ادمین.');
        }

        return $next($request);
    }
}
