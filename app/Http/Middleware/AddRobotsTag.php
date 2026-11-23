<?php

namespace App\Http\Middleware;

use App\Support\Seo\Seo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * سئو (۲۰۲۶-۱۱-۲۳): هر پاسخی که صفحه‌ی عمومی ایندکس‌پذیر نیست (پنل‌ها، ورود، API، پرداخت، پیش‌نمایش سالن احرازنشده، …)
 * «X-Robots-Tag: noindex, nofollow» می‌گیرد. پیش‌فرض بسته: صفحه‌ی تازه تا در config/seo.php نیاید ایندکس نمی‌شود.
 * سراسری و append (درونی‌ترین میان‌افزار سراسری) تا بعد از $next هم route و هم سالن جاری معلوم باشد.
 */
class AddRobotsTag
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response->headers->has('X-Robots-Tag') && ! Seo::indexable($request)) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
