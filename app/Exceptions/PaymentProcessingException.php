<?php

namespace App\Exceptions;

class PaymentProcessingException extends DomainException
{
    protected int $httpStatus = 502;

    protected ?string $userMessage = 'در حال حاضر امکان پرداخت وجود ندارد. لطفاً چند لحظه دیگر تلاش کنید.';

    /**
     * @var array<string, mixed>
     */
    private array $contextData = [];

    /**
     * @param string $gatewayMessage پیام فنی دریافتی از درگاه (برای log)
     * @param string|null $userMessage پیام کاربرپسند (در صورت null از پیش‌فرض استفاده می‌شه)
     * @param array<string, mixed> $context داده‌های اضافی برای log
     */
    public static function gatewayFailed(
        string $gatewayMessage,
        ?string $userMessage = null,
        array $context = []
    ): self {
        $instance = new self($gatewayMessage);
        if ($userMessage !== null) {
            $instance->userMessage = $userMessage;
        }
        $instance->contextData = $context;

        return $instance;
    }

    public static function transactionNotFound(string $reference): self
    {
        $instance = new self("Payment transaction not found: {$reference}");
        $instance->userMessage = "تراکنش پرداخت با کد پیگیری «{$reference}» یافت نشد.";
        $instance->contextData = ['reference' => $reference];

        return $instance;
    }

    public static function alreadyProcessed(): self
    {
        $instance = new self('Payment already processed.');
        $instance->userMessage = 'این پرداخت قبلاً پردازش شده است.';
        $instance->httpStatus = 409;

        return $instance;
    }

    public function context(): array
    {
        return $this->contextData;
    }
}
