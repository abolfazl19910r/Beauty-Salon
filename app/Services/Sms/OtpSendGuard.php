<?php

namespace App\Services\Sms;

use App\Services\SuperAdmin\SuperAdminNotifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * نگهبان ارسال کد پیامکی (بررسی امنیتی ۲۰۲۶-۱۱-۰۴، H4 — «پمپاژ پیامک»). همه‌ی کدهای تأیید (ورود وب و اپ، ثبت‌نام، فراموشی رمز،
 * ۲FA، ثبت‌نام سالن، «سالن‌های من»، فعال‌سازی متخصص) از Queues::dispatchOtp رد می‌شوند و این‌جا سنجیده می‌شوند.
 *
 * سقف‌ها (تصمیم ابوالفضل، ۲۰۲۶-۱۱-۰۴؛ config/services.php ‏kavenegar.otp_limits):
 * - هر شماره‌ی مقصد: ۳ در ۱۰ دقیقه و ۱۰ در روز — بمباران گوشی یک نفر؛
 * - هر IP: ۳۰ در ساعت — سقف دقیقه‌ای مسیرها (RouteServiceProvider) هنوز ۷٬۲۰۰ در روز اجازه می‌داد؛
 * - کل پلتفرم: ۲٬۰۰۰ در روز (روز تقویمی به وقت سرور) — با چند IP هم اعتبار کاوه‌نگار خالی نشود. در ۸۰٪ و در رسیدن به سقف
 *   یک اعلان ابرمدیر (هر کدام یک بار در روز)؛ بعد از سقف هیچ کدی نمی‌رود تا فردا یا تا OTP_PLATFORM_DAILY بالا برود.
 *
 * رد شدن بی‌صداست: پاسخ صفحه/API همان «کد فرستاده شد» است (نه شمارش شماره‌ها، نه نشانه‌ای برای مهاجم)؛ فقط لاگ warning.
 */
class OtpSendGuard
{
    public function allow(?string $phone, ?string $ip = null): bool
    {
        $limits = (array) config('services.kavenegar.otp_limits');
        $ip ??= $this->requestIp();
        $phone = $phone !== null ? $this->normalizePhone($phone) : null;

        $platformCap = (int) ($limits['platform_per_day'] ?? 2000);
        $platformKey = $this->platformKey();
        if ($platformCap > 0 && (int) Cache::get($platformKey, 0) >= $platformCap) {
            $this->block('platform_daily', $phone, $ip);
            $this->alertOnce('full', sprintf('ارسال کد پیامکی متوقف شد: سقف روزانه‌ی پلتفرم (%s کد) پر شد. تا پایان امروز هیچ کد ورود/ثبت‌نامی فرستاده نمی‌شود؛ اگر مصرف واقعی است OTP_PLATFORM_DAILY را بالا ببرید.', number_format($platformCap)));

            return false;
        }

        $checks = [];
        if ($phone !== null && $phone !== '') {
            $checks['phone_10min'] = ['otp-guard:phone10:'.$phone, (int) ($limits['per_phone_10min'] ?? 3), 600];
            $checks['phone_daily'] = ['otp-guard:phoneday:'.$phone, (int) ($limits['per_phone_day'] ?? 10), 86400];
        }
        if ($ip !== null && $ip !== '') {
            $checks['ip_hourly'] = ['otp-guard:ip:'.$ip, (int) ($limits['per_ip_hour'] ?? 30), 3600];
        }

        foreach ($checks as $reason => [$key, $max]) {
            if ($max > 0 && RateLimiter::tooManyAttempts($key, $max)) {
                $this->block($reason, $phone, $ip);

                return false;
            }
        }
        foreach ($checks as [$key, $max, $decay]) {
            RateLimiter::hit($key, $decay);
        }

        Cache::add($platformKey, 0, now()->addDays(2));
        $count = (int) Cache::increment($platformKey);
        if ($platformCap > 0 && $count >= (int) ceil($platformCap * 0.8)) {
            $this->alertOnce('warning', sprintf('مصرف کد پیامکی امروز به %s از سقف روزانه‌ی %s رسید. با پر شدن سقف، ارسال کد تا فردا متوقف می‌شود.', number_format($count), number_format($platformCap)));
        }

        return true;
    }

    /** تعداد کدهای فرستاده‌شده‌ی امروز (برای نمایش/آزمون). */
    public function platformCountToday(): int
    {
        return (int) Cache::get($this->platformKey(), 0);
    }

    private function platformKey(): string
    {
        return 'otp-guard:platform:'.now()->format('Ymd');
    }

    private function requestIp(): ?string
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return null;
        }

        return request()?->ip();
    }

    /** ‎+98912…، 0098912…، 912… و ارقام فارسی ← یک کلید برای یک شماره */
    private function normalizePhone(string $phone): string
    {
        $phone = strtr($phone, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9']);
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return strlen($digits) >= 10 ? substr($digits, -10) : $digits;
    }

    private function block(string $reason, ?string $phone, ?string $ip): void
    {
        Log::warning('OTP send blocked', [
            'reason' => $reason,
            'phone' => $phone ? substr($phone, 0, 3).'****'.substr($phone, -3) : null,
            'ip' => $ip,
        ]);
    }

    private function alertOnce(string $level, string $message): void
    {
        if (! Cache::add('otp-guard:alert:'.$level.':'.now()->format('Ymd'), true, now()->addDays(2))) {
            return;
        }

        try {
            app(SuperAdminNotifier::class)->send('sms_provider', $message, route('superadmin.sms.index', [], false));
        } catch (\Throwable $e) {
            Log::warning('OTP guard alert failed', ['level' => $level, 'error' => $e->getMessage()]);
        }
    }
}
