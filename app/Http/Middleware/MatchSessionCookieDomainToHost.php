<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ⭐ رفع خطای 419 Page Expired روی http://127.0.0.1:8000 (۲۰۲۶-۰۹-۲۳).
 *
 * SESSION_DOMAIN=.rasta-app.test برای حالت ساب‌دامینی لازمه (تا کوکی session بین rasta-app.test و
 * {slug}.rasta-app.test مشترک باشه). ولی مرورگر کوکی‌ای که Domain اش با هاست جاری جور نیست رو
 * اصلاً ذخیره نمی‌کنه: روی 127.0.0.1 (یا localhost، یا هر هاست دیگه) هیچ کوکی session/XSRF ذخیره
 * نمی‌شد، پس هر فرم POST — ساخت سالن، خرید فوری، ورود — با CSRF mismatch → 419 رد می‌شد.
 *
 * این middleware قبل از EncryptCookies/StartSession اجرا می‌شه و فقط برای همین یک درخواست، اگه
 * هاست جاری زیرمجموعه‌ی session.domain نباشه، domain کوکی رو null (host-only) می‌کنه. روی
 * rasta-app.test و ساب‌دامین‌هاش هیچ تغییری نیست؛ روی 127.0.0.1 کوکی مخصوص همون هاست ساخته می‌شه.
 * هر دو آدرس به‌طور هم‌زمان کار می‌کنن (فقط session شون از هم جداست، که درسته).
 */
class MatchSessionCookieDomainToHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $domain = config('session.domain');

        if (is_string($domain) && $domain !== '' && ! self::hostMatchesCookieDomain($request->getHost(), $domain)) {
            config(['session.domain' => null]);
        }

        return $next($request);
    }

    /**
     * همون قاعده‌ی مرورگر (RFC 6265 domain-match): هاست خودِ دامنه باشه یا زیردامنه‌اش.
     * نقطه‌ی اول «.rasta-app.test» فقط قرارداد قدیمیه و نادیده گرفته می‌شه.
     */
    public static function hostMatchesCookieDomain(string $host, string $cookieDomain): bool
    {
        $host = strtolower($host);
        $cookieDomain = strtolower(ltrim($cookieDomain, '.'));

        return $host === $cookieDomain || str_ends_with($host, '.'.$cookieDomain);
    }
}
