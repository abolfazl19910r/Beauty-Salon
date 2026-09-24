<?php

namespace App\Payments;

final class PayoutRequest
{
    public function __construct(
        public readonly int $amountRial,
        public readonly string $iban,
        public readonly string $description,
        public readonly string $reference,
    ) {}
}
