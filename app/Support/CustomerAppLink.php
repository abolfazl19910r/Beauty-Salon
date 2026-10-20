<?php

namespace App\Support;

use App\Models\AppRelease;
use App\Models\Salon;

/**
 * لینک‌های اپ «ماهرو» برای سایت یک سالن (بسته‌ی ۳ب، ۲۰۲۶-۱۰-۱۴؛ بسته‌ی ۵، ۲۰۲۶-۱۰-۲۰).
 *
 * - app_url / intent_url: روی اندروید، لینک intent کروم اپ را مستقیم روی همان سالن باز می‌کند (mahru://salon/{slug} — صفحه‌ی
 *   salon/[slug] اپ). اگر اپ نصب نیست، کروم به browser_fallback_url می‌رود: صفحه‌ی «اپ» همین سالن (install_url).
 *   از بسته‌ی ۵، اپ نصب‌شده لینک https سالن را هم خودش باز می‌کند (App Links، /.well-known/assetlinks.json).
 * - install_url: صفحه‌ی /app خود سالن (دانلود اندروید + کد سالن، راهنمای آیفون).
 * - download_url: خود فایل APK روی دامنه‌ی اصلی (آخرین نسخه‌ی منتشرشده)؛ CUSTOMER_APP_DOWNLOAD_URL جایگزینش می‌کند؛ null = هنوز نسخه‌ای نیست.
 * - download_page_url: صفحه‌ی «دانلود اپ ماهرو» با کد همین سالن.
 */
final class CustomerAppLink
{
    /**
     * @return array{app_url: string, intent_url: string, install_url: string, download_url: ?string, download_page_url: string}
     */
    public static function forSalon(Salon $salon): array
    {
        $scheme = (string) config('api.customer_app_scheme', 'mahru');
        $package = (string) config('api.customer_app_package', 'ir.mahru');
        $path = 'salon/'.rawurlencode($salon->slug);
        $install = route('salon.app', ['salon_slug' => $salon->slug]);

        return [
            'app_url' => "{$scheme}://{$path}",
            'intent_url' => "intent://{$path}#Intent;scheme={$scheme};package={$package};S.browser_fallback_url=".rawurlencode($install).';end',
            'install_url' => $install,
            'download_url' => self::downloadUrl(),
            'download_page_url' => CentralUrl::route('app-download.show', ['salon' => $salon->slug]),
        ];
    }

    public static function downloadUrl(): ?string
    {
        if ($override = config('api.customer_app_download_url')) {
            return (string) $override;
        }

        return AppRelease::latestFor('customer') !== null ? CentralUrl::route('app-download.file', ['variant' => 'customer']) : null;
    }
}
