<?php

namespace App\Support\Seo;

use App\Models\Salon;
use App\Support\CurrentSalon;
use Illuminate\Http\Request;

/**
 * تصمیم ایندکس و canonical (سئو، ۲۰۲۶-۱۱-۲۳). پیش‌فرض بسته: فقط routeهای config/seo.php ایندکس می‌شوند.
 */
final class Seo
{
    public static function enabled(): bool
    {
        return (bool) config('seo.indexing', true);
    }

    public static function indexable(Request $request): bool
    {
        if (! self::enabled() || ! $request->isMethodSafe()) {
            return false;
        }

        $name = $request->route()?->getName();
        if (! is_string($name) || $name === '') {
            return false;
        }

        // بی CENTRAL_DOMAIN نسخه‌ی /s/{slug} نام بی‌پیشوند دارد (همان نسخه‌ی اصلی)؛ با CENTRAL_DOMAIN نامش «legacy.» است و به
        // زیردامنه می‌رود — هرگز نسخه‌ی تکراری ایندکس‌پذیر نیست

        if (in_array($name, config('seo.salon_routes', []), true)) {
            return self::salonIsPublic(app(CurrentSalon::class)->get());
        }

        return in_array($name, config('seo.central_routes', []), true)
            && app(CurrentSalon::class)->get() === null
            && self::onCentralHost($request);
    }

    /** سالنی که صفحه‌ی عمومی‌اش برای همه باز است (همان شرط ResolveSalonFromRoute، بی پیش‌نمایش کادر). */
    public static function salonIsPublic(?Salon $salon): bool
    {
        return $salon !== null
            && ! $salon->is_suspended
            && $salon->subscription_ends_at !== null
            && $salon->subscription_ends_at->isFuture()
            && $salon->isVerified();
    }

    /** نشانی canonical: همان نشانی بی query (فیلتر دسته و صفحه‌بندی نسخه‌ی جدا نیستند). */
    public static function canonical(Request $request): string
    {
        return $request->url();
    }

    private static function onCentralHost(Request $request): bool
    {
        $central = strtolower((string) config('app.central_domain'));

        return $central === '' || strtolower($request->getHost()) === $central;
    }
}
