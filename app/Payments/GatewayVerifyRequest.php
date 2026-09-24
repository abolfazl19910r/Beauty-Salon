<?php

namespace App\Payments;

final class GatewayVerifyRequest
{
    /**
     * @param  array<string, mixed>  $callback  همه‌ی پارامترهای بازگشت از درگاه (query + POST)
     * @param  string|null  $token  توکنی که خود درگاه موقع شروع داده و در تراکنش ذخیره شده — driverها
     *                              اول همین رو استفاده می‌کنن، نه مقدار callback (که هر کسی می‌تونه بسازه).
     * @param  int|null  $transactionId  شناسه‌ی عددی ردیف payment_transactions (localInvoiceId آسان پرداخت)
     */
    public function __construct(
        public readonly int $amountRial,
        public readonly array $callback,
        public readonly ?string $token = null,
        public readonly ?int $transactionId = null,
    ) {}
}
