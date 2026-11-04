<?php

namespace App\Support\Auth;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * قفل موقت ورود با رمز برای هر شماره (بررسی امنیتی ۲۰۲۶-۱۱-۰۵، L5). پیش‌تر فقط سقف دقیقه‌ای IP بود؛ با IPهای زیاد رمز یک حساب را
 * می‌شد بی‌پایان حدس زد (کد پیامکی مرحله‌ی دوم جبران می‌کرد، ولی رمز درست‌حدس‌زده همان لو رفتن رمز است).
 *
 * auth.password_lockout: بعد از max_attempts رمز غلط در decay_minutes، همان شماره تا پایان آن مهلت با هیچ رمزی وارد نمی‌شود.
 * - برای شماره‌ی ناموجود هم دقیقاً همین (پاسخ قفل، وجود حساب را لو نمی‌دهد)؛
 * - دامنه‌ها جدا: کادر (ورود مرکزی و اپ همکار) و مشتری هر سالن؛
 * - ورود موفق شمارنده را صفر می‌کند؛ بازیابی رمز (کد پیامکی) قفل را برمی‌دارد — صاحب واقعی شماره هیچ‌وقت پشت قفل نمی‌ماند.
 */
final class PasswordLockout
{
    public const MESSAGE = 'رمز اشتباه بیش از حد مجاز وارد شد. چند دقیقه‌ی دیگر دوباره امتحان کنید یا رمز را بازیابی کنید.';

    public static function staff(string $phone): string
    {
        return 'pw-lock:staff:'.$phone;
    }

    public static function customer(int $salonId, string $phone): string
    {
        return 'pw-lock:customer:'.$salonId.':'.$phone;
    }

    public static function locked(string $key): bool
    {
        return RateLimiter::tooManyAttempts($key, self::max());
    }

    /** ثانیه تا باز شدن */
    public static function availableIn(string $key): int
    {
        return RateLimiter::availableIn($key);
    }

    public static function failed(string $key): void
    {
        RateLimiter::hit($key, self::minutes() * 60);

        if (RateLimiter::attempts($key) === self::max()) {
            Log::channel('security')->warning('Password login locked after too many wrong passwords', ['key' => preg_replace('/\d{4}$/', '****', $key)]);
        }
    }

    public static function clear(string $key): void
    {
        RateLimiter::clear($key);
    }

    private static function max(): int
    {
        return max(1, (int) config('auth.password_lockout.max_attempts', 10));
    }

    private static function minutes(): int
    {
        return max(1, (int) config('auth.password_lockout.decay_minutes', 15));
    }
}
