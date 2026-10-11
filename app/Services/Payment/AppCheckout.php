<?php

namespace App\Services\Payment;

use App\Models\Salon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

/**
 * لینک «رفتن به درگاه» برای اپ «ماهرو» (بسته‌ی ۳؛ تصمیم ۲۰۲۶-۱۰-۱۱: صفحه‌ی بانک در مرورگر بیرونی).
 *
 * اپ نمی‌تواند فرم POST بانک‌ها (سامان، ملت، پارسیان، آسان پرداخت) را خودش بسازد؛ پس API یک لینک امضاشده‌ی کوتاه‌عمر
 * روی همین سرور می‌دهد که درگاه‌های GET را redirect و درگاه‌های POST را با فرم خودکار (GatewayRedirect) باز می‌کند.
 * اطلاعات شروع درگاه برای همین مدت در cache می‌ماند (کلید = public_id تراکنش).
 */
class AppCheckout
{
    private const PREFIX = 'app-checkout:';

    public function remember(Salon $salon, array $gatewayResult): string
    {
        $publicId = (string) $gatewayResult['transaction'];
        $minutes = (int) config('api.checkout_ttl_minutes', 15);

        Cache::put(self::PREFIX.$publicId, [
            'payment_url' => $gatewayResult['payment_url'],
            'method' => $gatewayResult['method'] ?? 'GET',
            'form_fields' => $gatewayResult['form_fields'] ?? [],
        ], now()->addMinutes($minutes));

        return URL::temporarySignedRoute('app.checkout', now()->addMinutes($minutes), [
            'salon_slug' => $salon->slug,
            'publicId' => $publicId,
        ]);
    }

    public function find(string $publicId): ?array
    {
        $data = Cache::get(self::PREFIX.$publicId);

        return is_array($data) ? $data : null;
    }

    /** لینک برگشت به اپ — اپ هرگز به پارامترهایش اعتماد نمی‌کند و وضعیت را از GET payments/{tx} می‌پرسد. */
    public static function deepLink(string $publicId, string $state, string $purpose): string
    {
        return config('api.customer_app_scheme', 'mahru').'://payment?'.http_build_query([
            'tx' => $publicId,
            'state' => $state,
            'purpose' => $purpose,
        ]);
    }
}
