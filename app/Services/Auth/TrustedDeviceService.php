<?php

namespace App\Services\Auth;

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * «مرا به خاطر بسپار» ورود وب (بسته‌ی ۶، ۲۰۲۶-۱۰-۲۰؛ مشتری سالن و کادر):
 *  - نشست ماندگار ۹۰ روزه (کوکی remember لاراول، config/auth.php guards.web.remember)؛
 *  - این دستگاه «شناخته‌شده» می‌شود: ورود بعدی همین کاربر روی همین مرورگر (مثلاً بعد از خروج، یا آیفونی که سایت را به صفحه‌ی اصلی
 *    افزوده) فقط رمز می‌خواهد، نه کد پیامکی — مثل ورود اپ که کد فقط بار اول روی هر گوشی است (تصمیم ۴).
 * رمز همیشه لازم است. کوکی جدا برای هر کاربر (mahru_device_{id})؛ در جدول فقط هش توکن. تغییر رمز همه‌ی دستگاه‌ها را باطل می‌کند (User).
 */
class TrustedDeviceService
{
    public static function days(): int
    {
        return max(1, (int) (config('auth.remember_days') ?: 90));
    }

    public static function cookieName(User $user): string
    {
        return 'mahru_device_'.$user->id;
    }

    public function trust(User $user, Request $request): void
    {
        $token = Str::random(64);

        TrustedDevice::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255) ?: null,
            'ip_address' => $request->ip(),
            'last_used_at' => now(),
            'expires_at' => now()->addDays(self::days()),
        ]);

        $this->queueCookie($user, $token);
    }

    /** دستگاه شناخته‌شده‌ی معتبر برای همین کاربر؟ اگر بله، مهلتش از امروز تمدید می‌شود. */
    public function isTrusted(User $user, Request $request): bool
    {
        $token = $request->cookie(self::cookieName($user));
        if (! is_string($token) || strlen($token) !== 64) {
            return false;
        }

        $device = TrustedDevice::query()->where('user_id', $user->id)->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', now())->first();
        if ($device === null) {
            return false;
        }

        $device->update(['last_used_at' => now(), 'expires_at' => now()->addDays(self::days()), 'ip_address' => $request->ip()]);
        $this->queueCookie($user, $token);

        return true;
    }

    public function forgetAll(User $user): void
    {
        TrustedDevice::query()->where('user_id', $user->id)->delete();
    }

    private function queueCookie(User $user, string $token): void
    {
        Cookie::queue(Cookie::make(self::cookieName($user), $token, self::days() * 1440, null, null, null, true, false, 'lax'));
    }
}
