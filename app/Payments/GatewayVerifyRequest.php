<?php

namespace App\Payments;

final class GatewayVerifyRequest
{
    /**
     * @param  array<string, mixed>  $callback  همه‌ی پارامترهای بازگشت از درگاه (query + POST)
     */
    public function __construct(
        public readonly int $amountRial,
        public readonly array $callback,
    ) {}
}
