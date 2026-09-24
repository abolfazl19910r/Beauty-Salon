<?php

namespace App\Payments;

final class GatewayStartRequest
{
    public function __construct(
        public readonly int $amountRial,
        public readonly string $callbackUrl,
        public readonly string $description,
        public readonly ?string $mobile = null,
        public readonly ?string $email = null,
        public readonly ?string $orderId = null,
    ) {}

    /** تنها جای تبدیل تومان→ریال در لایه‌ی درگاه‌ها. */
    public static function fromToman(float|int|string $amountToman, string $callbackUrl, string $description, ?string $mobile = null, ?string $email = null, ?string $orderId = null): self
    {
        return new self((int) ((float) $amountToman * 10), $callbackUrl, $description, $mobile, $email, $orderId);
    }
}
