<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * پنل ابرمدیر (/superadmin) فقط از IPهای security.superadmin_allowed_ips (بررسی امنیتی ۲۰۲۶-۱۱-۰۴، M4). فهرست خالی = بی محدودیت.
 * پیش از auth اجرا می‌شود: از IP دیگر حتی صفحه‌ی ورود هم نمی‌گوید پنلی هست — ۴۰۴، مثل هر آدرس ناموجود.
 */
class RestrictSuperAdminIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = (array) config('security.superadmin_allowed_ips', []);

        if ($allowed !== [] && ! IpUtils::checkIp((string) $request->ip(), $allowed)) {
            Log::channel('security')->warning('Super admin panel request from a non-allowed IP', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'user_id' => $request->user()?->id,
            ]);

            abort(404);
        }

        return $next($request);
    }
}
