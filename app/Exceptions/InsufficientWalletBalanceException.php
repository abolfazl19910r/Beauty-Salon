<?php

namespace App\Exceptions;

class InsufficientWalletBalanceException extends DomainException
{
    public float $required = 0;

    public float $available = 0;

    public static function forAmount(float $required, float $available): self
    {
        $exception = new self(sprintf(
            'موجودی کیف پول کافی نیست. مبلغ مورد نیاز: %s تومان، موجودی فعلی: %s تومان.',
            number_format($required),
            number_format($available)
        ));

        $exception->required = $required;
        $exception->available = $available;

        return $exception;
    }

    public function httpStatusCode(): int
    {
        return 422;
    }
}
