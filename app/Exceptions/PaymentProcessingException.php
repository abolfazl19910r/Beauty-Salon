<?php

namespace App\Exceptions;

class PaymentProcessingException extends DomainException
{
    public static function gatewayFailed(string $reason = ''): self
    {
        $message = 'پرداخت با خطا مواجه شد و درگاه پاسخ موفقی نداد.';
        if ($reason !== '') {
            $message .= " ({$reason})";
        }

        return new self($message);
    }

    public static function transactionNotFound(string $reference): self
    {
        return new self("تراکنش پرداخت با کد پیگیری «{$reference}» یافت نشد.");
    }

    public static function alreadyProcessed(): self
    {
        return new self('این پرداخت قبلاً پردازش شده است.');
    }

    public function httpStatusCode(): int
    {
        return 422;
    }
}
