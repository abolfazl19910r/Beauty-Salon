<?php

namespace App\Exceptions;


class DiscountCodeInvalidException extends DomainException
{
    protected int $httpStatus = 422; // Unprocessable Entity

    protected ?string $userMessage = 'کد تخفیف وارد شده معتبر نیست.';

    /**
     * @var array<string, mixed>
     */
    private array $contextData = [];

    /**
     * @param string $reason دلیل فنی نامعتبری (برای log)
     * @param string|null $userMessage پیام کاربرپسند فارسی
     * @param array<string, mixed> $context
     */
    public static function because(
        string $reason,
        ?string $userMessage = null,
        array $context = []
    ): self {
        $instance = new self($reason);
        if ($userMessage !== null) {
            $instance->userMessage = $userMessage;
        }
        $instance->contextData = $context;

        return $instance;
    }

    public function context(): array
    {
        return $this->contextData;
    }
}
