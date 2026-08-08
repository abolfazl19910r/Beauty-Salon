<?php

namespace App\Exceptions;

class InsufficientWalletBalanceException extends DomainException
{
    protected int $httpStatus = 422;

    protected ?string $userMessage = 'موجودی کیف پول شما برای این پرداخت کافی نیست.';

    /**
     * @var array<string, mixed>
     */
    private array $contextData = [];

    /**
     * @param  float  $required  مبلغ مورد نیاز
     * @param  float  $available  موجودی فعلی
     * @param  int|null  $walletId  آیدی کیف پول
     */
    public static function forAmount(float $required, float $available, ?int $walletId = null): self
    {
        $instance = new self(
            "Insufficient wallet balance: required={$required}, available={$available}"
        );
        $instance->contextData = [
            'required' => $required,
            'available' => $available,
            'shortfall' => round($required - $available, 2),
            'wallet_id' => $walletId,
        ];

        return $instance;
    }

    public function context(): array
    {
        return $this->contextData;
    }
}
