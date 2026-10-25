<?php

namespace App\Services\Verification;

/** نتیجه‌ی سرویس احراز (بسته‌ی مجوز 5د، مرحله‌ی ب) */
final class VerificationOutcome
{
    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    /** @param array<string, mixed> $payload پاسخ خام سرویس، برای سابقه (بی داده‌ی حساس اضافه) */
    private function __construct(
        public readonly string $status,
        public readonly ?string $reference = null,
        public readonly ?string $reason = null,
        public readonly array $payload = [],
    ) {}

    public static function pending(?string $reference = null, array $payload = []): self
    {
        return new self(self::PENDING, $reference, null, $payload);
    }

    public static function approved(?string $reference = null, array $payload = []): self
    {
        return new self(self::APPROVED, $reference, null, $payload);
    }

    public static function rejected(string $reason, ?string $reference = null, array $payload = []): self
    {
        return new self(self::REJECTED, $reference, $reason, $payload);
    }
}
