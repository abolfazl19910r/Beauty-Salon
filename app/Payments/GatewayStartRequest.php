<?php

namespace App\Payments;

final class GatewayStartRequest
{
    /**
     * @param  int|null  $transactionId  شناسه‌ی عددی ردیف payment_transactions — درگاه‌هایی که شماره‌ی فاکتور
     *                                   عددی و یکتا می‌خوان (آسان پرداخت: localInvoiceId) از همین استفاده می‌کنن.
     */
    public function __construct(
        public readonly int $amountRial,
        public readonly string $callbackUrl,
        public readonly string $description,
        public readonly ?string $mobile = null,
        public readonly ?string $email = null,
        public readonly ?string $orderId = null,
        public readonly ?int $transactionId = null,
    ) {}

    /** تنها جای تبدیل تومان→ریال در لایه‌ی درگاه‌ها. */
    public static function fromToman(float|int|string $amountToman, string $callbackUrl, string $description, ?string $mobile = null, ?string $email = null, ?string $orderId = null): self
    {
        return new self((int) ((float) $amountToman * 10), $callbackUrl, $description, $mobile, $email, $orderId);
    }

    public function with(?int $amountRial = null, ?string $callbackUrl = null, ?int $transactionId = null): self
    {
        return new self(
            $amountRial ?? $this->amountRial,
            $callbackUrl ?? $this->callbackUrl,
            $this->description,
            $this->mobile,
            $this->email,
            $this->orderId,
            $transactionId ?? $this->transactionId,
        );
    }
}
