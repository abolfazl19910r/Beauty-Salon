<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * هدرهای امنیتی همه‌ی پاسخ‌های برنامه (وب، API، خطاها) — config/security.php.
 *
 * جایگزین App\Http\Middleware\SecurityMiddleware که هیچ‌وقت ثبت نشده بود (بسته‌ی مجوز 5د). محدودیت نرخ سراسری
 * ۶۰ در دقیقه‌ی آن کلاس عمداً نیامد: کاربران اینترنت همراه پشت CGNAT یک IP مشترک دارند و limiterهای نام‌دار هر مسیر
 * (RouteServiceProvider) جای آن را دارند.
 *
 * هدری که خود پاسخ از قبل گذاشته عوض نمی‌شود (مثلاً صفحه‌ای که بخواهد frame شود).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach ((array) config('security.headers', []) as $name => $value) {
            if ($value !== null && $value !== '' && ! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        $maxAge = (int) config('security.hsts.max_age', 0);
        if ($maxAge > 0 && $request->isSecure() && ! $response->headers->has('Strict-Transport-Security')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age='.$maxAge.(config('security.hsts.include_subdomains') ? '; includeSubDomains' : '')
            );
        }

        return $response;
    }
}
