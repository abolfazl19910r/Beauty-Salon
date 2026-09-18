<?php

namespace App\Exceptions;

class SpecialistQuotaExceededException extends DomainException
{
    protected int $httpStatus = 422; // Unprocessable Entity

    protected ?string $userMessage = 'سقف تعداد متخصصین این سالن تکمیل شده است. برای افزودن متخصص جدید، سقف را با سوپر ادمین در میان بگذارید.';

    /**
     * @var array<string, mixed>
     */
    private array $contextData = [];

    /**
     * @param  string  $technicalReason  دلیل فنی برای log
     * @param  array<string, mixed>  $context  داده‌های اضافی (salon_id, current_count, max_specialists_count, ...)
     */
    public static function quotaReached(string $technicalReason, array $context = []): self
    {
        $instance = new self($technicalReason);
        $instance->contextData = $context;

        return $instance;
    }

    public function context(): array
    {
        return $this->contextData;
    }
}
