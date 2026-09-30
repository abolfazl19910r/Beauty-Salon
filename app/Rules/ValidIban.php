<?php

namespace App\Rules;

use App\Support\Iban;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidIban implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = is_string($value) ? str_replace(' ', '', $value) : '';

        if (! preg_match('/^[0-9]{24}$/', $digits)) {
            $fail('لطفاً ۲۴ رقم شماره شبا را بدون IR وارد کنید.');

            return;
        }

        if (! Iban::isValid(Iban::COUNTRY.$digits)) {
            $fail('شماره شبا معتبر نیست: رقم کنترلی آن با بقیه‌ی ارقام نمی‌خواند (احتمالاً یک رقم اشتباه یا جابه‌جا تایپ شده). لطفاً شبا را از روی کارت یا اپ بانک دوباره بررسی کنید.');
        }
    }
}
