<?php

namespace App\Payments;

final class GatewayVerifyResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $token = null,
        public readonly ?string $refId = null,
        public readonly ?string $cardPan = null,
        public readonly ?int $fee = null,
        public readonly ?string $message = null,
        public readonly bool $cancelledByUser = false,
        public readonly array $raw = [],
    ) {}
}
