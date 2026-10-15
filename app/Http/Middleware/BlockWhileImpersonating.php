<?php

namespace App\Http\Middleware;

use App\Services\SuperAdmin\SuperAdminImpersonation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ⭐ در «ورود اضطراری» ابرمدیر (۲۰۲۶-۱۰-۱۵) رمز و مشخصات ورود خود مالک سالن عوض نشود؛ ابرمدیر این‌ها را از «مالک سالن» در
 * صفحه‌ی ویرایش سالن مدیریت می‌کند (که در گزارش کارها ثبت می‌شود).
 */
class BlockWhileImpersonating
{
    public function handle(Request $request, Closure $next): Response
    {
        if (SuperAdminImpersonation::active() && in_array($request->route()?->getName(), SuperAdminImpersonation::BLOCKED_ROUTES, true)) {
            return back()->with('error', 'در حالت پشتیبانی نمی‌توانید رمز یا مشخصات ورود مالک را عوض کنید؛ از «مالک سالن» در پنل ابرمدیر استفاده کنید.');
        }

        return $next($request);
    }
}
