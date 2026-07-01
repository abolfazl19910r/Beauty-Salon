<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidIban implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = str_replace(' ', '', $value);

        if (! preg_match('/^[0-9]{24}$/', $digits)) {
            $fail('لطفاً ۲۴ رقم شماره شبا را بدون IR وارد کنید.');
        }
    }
}
