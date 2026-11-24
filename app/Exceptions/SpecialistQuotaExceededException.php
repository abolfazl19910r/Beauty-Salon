<?php

namespace App\Exceptions;

class SpecialistQuotaExceededException extends DomainException
{
    protected int $httpStatus = 422; // Unprocessable Entity

    protected ?string $userMessage = 'سقف تعداد کارشناسان این سالن تکمیل شده است. برای افزودن کارشناس جدید، سقف را با سوپر ادمین در میان بگذارید.';

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

    /** دوره‌ی آزمایشی (بسته‌ی ۰، ۲۰۲۶-۱۱-۲۴): سقف ثابت پلن آزمایشی، نه سقف دستی سالن — راهش خرید پلن است */
    public static function trialLimitReached(int $limit, array $context = []): self
    {
        $instance = self::quotaReached('Trial specialist limit reached for salon.', $context + ['trial_limit' => $limit]);
        $instance->userMessage = sprintf(
            'در دوره‌ی آزمایشی رایگان حداکثر %s کارشناس می‌توانید اضافه کنید. برای کارشناس بیشتر، از «اشتراک و صورتحساب» پلن بخرید.',
            to_persian_num((string) $limit)
        );

        return $instance;
    }

    public function context(): array
    {
        return $this->contextData;
    }
}
