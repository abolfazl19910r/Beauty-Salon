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
        ], fn ($v) => $v !== null);
    }
}
