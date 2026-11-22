<?php

namespace App\Payments;

use App\Payments\Drivers\ZarinpalDriver;
use App\Support\PlatformZarinpal;

/**
 * درایور «درگاه پلتفرم» سالن‌ها (۲۰۲۶-۱۱-۲۲): همان زرین‌پال با مرچنت پلتفرم (ZARINPAL_MERCHANT_ID، sandbox طبق PlatformZarinpal).
 * اگر مرچنت پلتفرم روی درگاه واقعی تنظیم نشده باشد، شروع پرداخت بی تماس با زرین‌پال و با پیام روشن رد می‌شود (retryable تا درگاه
 * بعدی سالن امتحان شود) — پول مشتری هرگز به مرچنت ناشناس نمی‌رود.
 */
class PlatformZarinpalDriver extends ZarinpalDriver
{
    private bool $configured;

    public static function make(): self
    {
        $merchant = PlatformZarinpal::merchantId();
        $driver = new self(['merchant_id' => (string) $merchant], PlatformZarinpal::sandbox());
        $driver->configured = $merchant !== null;

        return $driver;
    }

    public function start(GatewayStartRequest $request): GatewayStartResult
    {
        if (! $this->configured) {
            return GatewayStartResult::failed(PlatformZarinpal::MISSING_MESSAGE, retryable: true, raw: ['platform_merchant_missing' => true]);
        }

        return parent::start($request);
    }
}
