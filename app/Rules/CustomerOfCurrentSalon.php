<?php

namespace App\Rules;

use App\Repositories\Contracts\UserRepositoryInterface;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * user_id باید مشتریِ سالن فعلی باشد — نوبت و کد تخفیف شخصی مال مشتری همین سالن است. بدون سالن (سوپرادمین) هر
 * مشتری‌ای پذیرفته می‌شود.
 */
class CustomerOfCurrentSalon implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = app(UserRepositoryInterface::class)->querySalonCustomers()->whereKey($value)->exists();

        if (! $exists) {
            $fail('کاربر انتخاب شده مشتری این سالن نیست.');
        }
    }
}
