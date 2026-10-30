<?php

namespace App\Payments;

use App\Models\Salon;

/**
 * ⭐ آدرس بازگشت درگاه روی دامنه‌ی ثابت (بسته‌ی 5ز، ۲۰۲۶-۱۰-۳۰). هر ترمینال زرین‌پال یک `domain` دارد (TerminalAdd) و آدرس بازگشت
 * با دامنه‌ی دیگر خطای ‎-14 می‌گیرد؛ پیش از این `route('payments.return')` روی هاست همان درخواست ساخته می‌شد (ساب‌دامین سالن،
 * `arisheh.ir/s/rasta` یا هاست لینک پرداخت اپ). حالا:
 * - پرداخت مشتری سالن (نوبت، کیف پول، دوره) ← دامنه‌ی خود سالن: `{slug}.CENTRAL_DOMAIN`، با CENTRAL_DOMAIN خالی میزبان APP_URL؛
 * - اشتراک و بسته‌ی پیامک (درگاه پلتفرم) ← دامنه‌ی مرکزی (میزبان APP_URL).
 * طرح (http/https) و درگاه از APP_URL می‌آید. مسیر payments.return بی Route::domain است و روی هر هاستی جواب می‌دهد؛ بعد با 303 به
 * callback کسب‌وکار روی هاست اصلی مشتری (همان‌جا که کوکی نشست دارد) می‌رود — GatewayReturnController.
 */
final class PaymentReturnUrl
{
    public static function for(string $publicId, ?Salon $salon): string
    {
        return self::root($salon).route('payments.return', ['publicId' => $publicId], false);
    }

    /** `https://rasta.arisheh.ir` یا `http://192.168.1.2:8000` */
    public static function root(?Salon $salon): string
    {
        $app = parse_url((string) config('app.url')) ?: [];
        $scheme = $app['scheme'] ?? 'https';
        $port = isset($app['port']) ? ':'.$app['port'] : '';

        return $scheme.'://'.self::domain($salon).$port;
    }

    /** دامنه‌ای که باید در پنل زرین‌پال برای درگاه ثبت شود (بی طرح و درگاه). */
    public static function domain(?Salon $salon): string
    {
        $central = trim((string) config('app.central_domain'));
        if ($salon && $salon->slug && $central !== '') {
            return $salon->slug.'.'.$central;
        }

        return (string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost');
    }
}
