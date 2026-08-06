<?php

namespace App\Services;

/**
 * قبلاً این محاسبه داخل SecurityController بود و هر بار امتیاز امنیتی محاسبه می‌شد،
 * روی خود هش ذخیره‌شده‌ی رمز عبور (نه رمز خام) دوباره اجرا می‌شد — چون هش همیشه رشته‌ای
 * ثابت‌طول و پر از تنوع کاراکتره، این همیشه امتیاز بالا می‌داد، فارغ از قدرت واقعی رمز.
 *
 * این سرویس فقط باید لحظه‌ی ثبت‌نام/تغییر رمز، روی خود رمز خام (قبل از Hash::make)
 * صدا زده بشه؛ نتیجه در ستون users.password_strength_score ذخیره و از اون به بعد
 * همیشه از همون ستون خونده می‌شه، نه دوباره از روی هش بازسازی.
 */
class PasswordStrengthService
{
    /**
     * @return int امتیاز ۰ تا ۱۰
     */
    public function score(string $password): int
    {
        $score = 0;

        $length = strlen($password);
        if ($length >= 12) {
            $score += 3;
        } elseif ($length >= 10) {
            $score += 2;
        } elseif ($length >= 8) {
            $score += 1;
        }

        if (preg_match('/[A-Z]/', $password)) {
            $score += 2;
        }
        if (preg_match('/[a-z]/', $password)) {
            $score += 2;
        }
        if (preg_match('/[0-9]/', $password)) {
            $score += 2;
        }
        if (preg_match('/[^A-Za-z0-9]/', $password)) {
            $score += 3;
        }

        $uniqueChars = count(array_unique(str_split($password)));
        if ($uniqueChars >= 8) {
            $score += 2;
        }

        if (preg_match('/(.)\1{2,}/', $password)) {
            $score -= 2;
        }
        if (preg_match('/^(?=.*[0-9])(?=.*[a-zA-Z])(?=.*[^A-Za-z0-9]).{8,}$/', $password)) {
            $score += 2;
        }

        $commonPasswords = ['password', '123456', 'qwerty', 'admin', '123456789', '12345'];
        if (in_array(strtolower($password), $commonPasswords, true)) {
            $score = 0;
        }

        return max(0, min(10, $score));
    }
}
