<?php

namespace App\Support\Customer;

/**
 * نام مشتری در صفحه‌های عمومی (حریم خصوصی): نام کوچک + حرف اول نام خانوادگی («مریم ا.»)؛ بی نام ← «مشتری سالن».
 * پیش‌تر خصوصی در HomeController؛ بسته‌ی ۵الف (صفحه‌ی «نظرها»ی سالن) مشترکش کرد.
 */
final class CustomerName
{
    public static function short(?string $name): string
    {
        $parts = preg_split('/\s+/u', trim((string) $name)) ?: [];
        if ($parts === [] || $parts[0] === '') {
            return 'مشتری سالن';
        }

        return count($parts) > 1 ? $parts[0].' '.mb_substr((string) end($parts), 0, 1).'.' : $parts[0];
    }
}
