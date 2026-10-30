<?php

namespace App\Support;

use App\Payments\Drivers\ZarinpalDriver;

/**
 * ⭐ درگاه زرین‌پال خود پلتفرم (اشتراک و بسته‌ی پیامک سالن‌ها) — آزمون دستی 5ز (۲۰۲۶-۱۰-۳۰): در داکر `ZARINPAL_MERCHANT_ID=` خالی بود و
 * `env('KEY', 'default')` با کلید خالیِ موجود همان '' را می‌دهد ← زرین‌پال ‎-9 «The merchant id field is required».
 * - مقدار معتبر در .env ← همان؛
 * - sandbox بی مقدار معتبر ← SANDBOX_MERCHANT (مستندات sandbox: «هر UUID دلخواه»)؛
 * - درگاه واقعی بی مقدار معتبر ← null: پرداخت شروع نمی‌شود و پیام روشن می‌آید (پول پلتفرم هرگز به مرچنت ناشناس نمی‌رود).
 */
final class PlatformZarinpal
{
    public const SANDBOX_MERCHANT = '00000000-0000-0000-0000-000000000000';

    public const MISSING_MESSAGE = 'درگاه پرداخت پلتفرم تنظیم نشده است (ZARINPAL_MERCHANT_ID در .env سرور)؛ با پشتیبانی ماهرو تماس بگیرید.';

    public static function sandbox(): bool
    {
        return (bool) config('services.zarinpal.sandbox', true);
    }

    public static function merchantId(): ?string
    {
        return ZarinpalMerchant::validOrNull((string) config('services.zarinpal.merchant_id'))
            ?? (self::sandbox() ? self::SANDBOX_MERCHANT : null);
    }

    public static function driver(): ?ZarinpalDriver
    {
        $merchant = self::merchantId();

        return $merchant ? new ZarinpalDriver(['merchant_id' => $merchant], self::sandbox()) : null;
    }
}
