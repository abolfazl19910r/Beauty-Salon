<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MaxPercentage implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ((float) $value > 100) {
            $fail('این مقدار نمی‌تواند بیشتر از ۱۰۰ درصد باشد.');
        }
    }
}
