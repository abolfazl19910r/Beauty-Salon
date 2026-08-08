<?php

namespace App\Exceptions;

class BookingNotAvailableException extends DomainException
{
    protected int $httpStatus = 409; // Conflict

    protected ?string $userMessage = 'زمان انتخابی شما دیگر در دسترس نیست. لطفاً زمان دیگری را انتخاب کنید.';

    /**
     * @var array<string, mixed>
     */
    private array $contextData = [];

    /**
     * @param  string  $technicalReason  دلیل فنی برای log
     * @param  array<string, mixed>  $context  داده‌های اضافی (specialist_id, requested_time, ...)
     * @param  string|null  $userMessage  پیام کاربرپسند
     */
    public static function slotTaken(
        string $technicalReason,
        array $context = [],
        ?string $userMessage = null
    ): self {
        $instance = new self($technicalReason);
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
