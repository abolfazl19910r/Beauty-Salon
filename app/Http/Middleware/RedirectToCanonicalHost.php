<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * یک نسخه‌ی اصلی دامنه (بسته‌ی مجوز 5د، تصمیم ۲۰۲۶-۱۰-۲۵: بی www): www.CENTRAL_DOMAIN با 301 (GET/HEAD) یا 308 (بقیه، بدن
 * درخواست حفظ شود) به CENTRAL_DOMAIN با همان مسیر و query می‌رود. کد لوگوی اینماد فقط برای یک دامنه کار می‌کند و همان محتوا روی
 * دو میزبان تخلف «بارگذاری بستر روی نشانی دیگر» است. در خود برنامه، مستقل از CDN. بدون CENTRAL_DOMAIN کاری نمی‌کند.
 */
class RedirectToCanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $central = strtolower((string) config('app.central_domain'));

        if ($central !== '' && strtolower($request->getHost()) === 'www.'.$central) {
            $port = in_array($request->getPort(), [80, 443], true) ? '' : ':'.$request->getPort();
            $target = $request->getScheme().'://'.$central.$port.$request->getRequestUri();

            return redirect()->away($target, $request->isMethodSafe() ? 301 : 308);
        }

        return $next($request);
    }
}
