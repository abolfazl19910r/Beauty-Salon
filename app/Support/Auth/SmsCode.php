<?php

namespace App\Support\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * کدهای پیامکی وبِ ذخیره‌شده روی کاربر (بررسی امنیتی ۲۰۲۶-۱۱-۰۴، M1): کد ورود و تأیید موبایل، دومرحله‌ای، فراموشی رمز.
 *
 * قبلاً کد ۶ رقمی خام در users بود، با !== مقایسه می‌شد و جز سقف دقیقه‌ای IP شمارنده‌ی تلاش نداشت ← با IPهای زیاد حدس کد فراموشی رمز
 * در مهلت ۲ دقیقه و تصاحب حساب. حالا مثل مسیرهای اپ (SmsCodeChallenge): فقط HMAC کد (با APP_KEY) ذخیره می‌شود، مقایسه با hash_equals،
 * و هر کد حداکثر MAX_ATTEMPTS بار غلط وارد می‌شود؛ بعد کد می‌سوزد (پاک می‌شود) و کاربر باید کد تازه بگیرد.
 *
 * ستون‌ها: verification_code (تأیید موبایل و فراموشی رمز)، login_verification_code، two_factor_code — هر سه ۶۴ نویسه.
 */
final class SmsCode
{
    public const MAX_ATTEMPTS = 5;

    /** @var list<string> فقط در آزمون‌ها: کدهای ساخته‌شده به ترتیب (کد خام دیگر در دیتابیس نیست) */
    private static array $issued = [];

    public static function generate(int $length = 6): string
    {
        $length = max(4, min(10, $length));
        $code = (string) random_int((int) ('1'.str_repeat('0', $length - 1)), (int) str_repeat('9', $length));

        if (app()->runningUnitTests()) {
            self::$issued[] = $code;
        }

        return $code;
    }

    public static function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }

    /**
     * کد تازه می‌سازد، HMAC آن را با مهلت روی کاربر ذخیره می‌کند و شمارنده‌ی تلاش را صفر می‌کند. کد خام برای پیامک برمی‌گردد.
     */
    public static function issue(User $user, string $field, string $expiresField, int $minutes, int $length = 6): string
    {
        $code = self::generate($length);

        $user->forceFill([
            $field => self::hash($code),
            $expiresField => now()->addMinutes($minutes),
        ])->save();
        Cache::forget(self::attemptsKey($user, $field));

        return $code;
    }

    /**
     * کد را می‌سنجد (مهلت، HMAC، سقف تلاش). درست ← true (کد را پاک نمی‌کند؛ مصرف با صدازننده است). غلط ← شمارنده یکی بالا می‌رود و
     * در MAX_ATTEMPTS کد پاک می‌شود. منقضی/نبودن کد تلاش حساب نمی‌شود.
     */
    public static function check(User $user, string $field, string $expiresField, string $code): bool
    {
        $stored = $user->getAttribute($field);
        $expiresAt = $user->getAttribute($expiresField);

        if (! is_string($stored) || $stored === '' || ! $expiresAt || now()->isAfter($expiresAt)) {
            return false;
        }

        if (hash_equals($stored, self::hash(trim($code)))) {
            Cache::forget(self::attemptsKey($user, $field));

            return true;
        }

        $key = self::attemptsKey($user, $field);
        Cache::add($key, 0, now()->addMinutes(30));
        $attempts = (int) Cache::increment($key);

        if ($attempts >= self::MAX_ATTEMPTS) {
            $user->forceFill([$field => null, $expiresField => null])->save();
            Cache::forget($key);
            Log::warning('SMS code burned after too many wrong attempts', ['user_id' => $user->id, 'field' => $field]);
        }

        return false;
    }

    public static function clear(User $user, string $field, string $expiresField): void
    {
        $user->forceFill([$field => null, $expiresField => null])->save();
        Cache::forget(self::attemptsKey($user, $field));
    }

    /** فقط آزمون‌ها: آخرین کد ساخته‌شده */
    public static function lastIssued(): ?string
    {
        return self::$issued === [] ? null : self::$issued[array_key_last(self::$issued)];
    }

    /** فقط آزمون‌ها: همه‌ی کدهای ساخته‌شده به ترتیب @return list<string> */
    public static function issued(): array
    {
        return self::$issued;
    }

    /** @internal آزمون‌ها (TestCase::setUp) */
    public static function flushIssued(): void
    {
        self::$issued = [];
    }

    private static function attemptsKey(User $user, string $field): string
    {
        return 'sms-code-attempts:'.$field.':'.$user->getKey();
    }
}
