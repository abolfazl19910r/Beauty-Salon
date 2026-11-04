<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * بررسی امنیتی ۲۰۲۶-۱۱-۰۵، M5: وقتی زیردامنه‌ی سالن‌ها فعال است (CENTRAL_DOMAIN پر)، آدرس قدیمی arisheh.ir/s/{slug}/… با
 * ۳۰۲ به {slug}.arisheh.ir/… می‌رود (مسیر و query حفظ می‌شود، لینک‌های قدیمی و QRها همچنان کار می‌کنند).
 *
 * چرا: کوکی session حالا فقط مال همان میزبان است (SESSION_DOMAIN خالی؛ روی HTTPS با پیشوند __Host-) تا اسکریپتی روی زیردامنه‌ی یک سالن
 * نتواند برای کل دامنه کوکی بگذارد (تثبیت نشست / cookie tossing). کوکی مشترک فقط به این دلیل لازم بود که صفحه‌ی قدیمی /s/{slug} روی
 * دامنه‌ی اصلی فرم‌هایی می‌ساخت که action‌شان زیردامنه بود (۴۱۹). با این انتقال هر صفحه‌ی سالن و فرم‌هایش روی یک میزبان‌اند.
 *
 * فقط GET/HEAD و فقط روی خود دامنه‌ی اصلی (روی 127.0.0.1 و میزبان‌های دیگر زیردامنه‌ای در کار نیست). ۳۰۲ نه ۳۰۱: اگر CENTRAL_DOMAIN
 * بعداً خالی شود (مثلاً گواهی wildcard نبود)، مرورگرها انتقال را کش نکرده‌اند. POST قدیمی (بانک و …) دست نمی‌خورد.
 */
class RedirectLegacySalonPathToSubdomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $central = strtolower((string) config('app.central_domain'));
        // پارامتر salon_slug را ResolveSalonFromRoute از route برمی‌دارد؛ slug از خود مسیر
        preg_match('#^/s/([^/]+)#', $request->getPathInfo(), $m);
        $slug = strtolower($m[1] ?? '');

        if ($central === '' || ! $request->isMethodSafe() || strtolower($request->getHost()) !== $central
            || ! preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $slug)) {
            return $next($request);
        }

        $rest = substr($request->getPathInfo(), strlen($m[0]));
        $port = $request->getPort();
        $defaultPort = $request->isSecure() ? 443 : 80;
        $url = $request->getScheme().'://'.$slug.'.'.$central.($port && (int) $port !== $defaultPort ? ':'.$port : '')
            .($rest === '' ? '/' : $rest);
        $query = $request->getQueryString();

        return redirect()->away($url.($query !== null && $query !== '' ? '?'.$query : ''), 302);
    }
}
