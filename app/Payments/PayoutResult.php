<?php

namespace App\Payments;

final class PayoutResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $referenceCode = null,
        public readonly ?string $payoutId = null,
        public readonly ?string $message = null,
        public readonly array $raw = [],
        // ⭐ نتیجه نامعلوم (پاسخ نرسید بعد از فرستادن): شاید واریز ثبت شده باشه — نه ناموفق (برگشت به کیف پول =
        // احتمال پرداخت دوباره)، نه موفق. ProcessWithdrawalJob برداشت رو برای بررسی دستی در processing نگه می‌داره.
        public readonly bool $unknown = false,
    ) {}

    /** همون شکل آرایه‌ای که ProcessWithdrawalJob از قبل انتظار داره. */
    public function toArray(): array
    {
        return array_filter([
            'success' => $this->success,
            'reference_code' => $this->referenceCode,
            'payout_id' => $this->payoutId,
            'message' => $this->message,
            'raw' => $this->raw ?: null,
            'unknown' => $this->unknown ?: null,
        ], fn ($v) => $v !== null);
    }
}
