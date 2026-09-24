<?php

namespace App\Payments;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * ⭐ انتقال مشتری به صفحه‌ی پرداخت درگاه (مرحله‌ی ۱ چند درگاه، ۲۰۲۶-۰۹-۲۵). درگاه‌های واسط (زرین‌پال،
 * زیبال، وندار) با redirect ساده (GET) کار می‌کنن؛ آسان پرداخت (و در مرحله‌ی ۲ بانک‌ها) توکن رو فقط با
 * فرم POST می‌پذیرن، پس یک صفحه‌ی کوچک با فرم خودکار برگردونده می‌شه.
 *
 * @param  array  $result  خروجی PaymentService::createPayment / createWalletChargePayment با success=true
 */
final class GatewayRedirect
{
    public static function to(array $result): RedirectResponse|Response
    {
        if (strtoupper((string) ($result['method'] ?? 'GET')) === 'POST') {
            return response()->view('payments.gateway-redirect', [
                'url' => $result['payment_url'],
                'fields' => (array) ($result['form_fields'] ?? []),
            ]);
        }

        return redirect()->away($result['payment_url']);
    }
}
