<?php

namespace App\Services\Auth;

use App\Exceptions\Api\ApiException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * ثبت‌نامی که پیش از تأیید کد پیامکی هیچ ردیفی در دیتابیس نمی‌سازد (۲۰۲۶-۱۰-۱۱): داده‌ی فرم پشت یک challenge تصادفی در cache
 * می‌ماند و فقط کد درست آن را آزاد می‌کند. پایه‌ی ثبت‌نام مشتری (CustomerRegistrationService) و ثبت‌نام سالن (SalonSignupChallenge).
 *
 * سخت‌گیری‌ها مثل ورود اپ (ApiLoginService): حداکثر api.login.max_code_attempts کد غلط (بعد challenge می‌سوزد)، ارسال دوباره
 * با فاصله‌ی resend_cooldown_seconds و حداکثر max_resends بار، عمر challenge_ttl_minutes. کد فقط هش‌شده نگه داشته می‌شود.
 * خطاها ApiException با کدهای ثابت‌اند تا هم API و هم صفحه‌ی وب از یک قاعده استفاده کنند.
 */
abstract class SmsCodeChallenge
{
    abstract protected function prefix(): string;

    /** پیامک کد به شماره‌ی $data['phone'] */
    abstract protected function deliver(array $data, string $code): void;

    protected function expiredMessage(): string
    {
        return 'مهلت ثبت‌نام تمام شده است. دوباره ثبت‌نام کنید.';
    }

    /** challenge تازه می‌سازد و کد می‌فرستد. $data باید phone داشته باشد. */
    public function open(array $data): string
    {
        $challenge = Str::random(48);
        $code = $this->newCode();

        $this->put($challenge, [
            'data' => $data,
            'code_hash' => hash('sha256', $code),
            'code_expires_at' => $this->codeExpiry(),
            'resends' => 0,
            'last_sent_at' => now()->getTimestamp(),
        ]);
        Cache::put($this->attemptsKey($challenge), 0, $this->ttlSeconds());

        $this->deliver($data, $code);

        return $challenge;
    }

    /** داده‌ی challenge بدون مصرف آن؛ null اگر منقضی/ناموجود. */
    public function peek(string $challenge): ?array
    {
        $entry = Cache::get($this->key($challenge));

        return is_array($entry) ? $entry['data'] : null;
    }

    /**
     * کد را می‌سنجد؛ کد درست challenge را مصرف می‌کند و داده را برمی‌گرداند.
     *
     * @param  \Closure(array): bool|null  $belongs  مثلاً «همین سالن» — نه → challenge_expired
     */
    public function confirm(string $challenge, string $code, ?\Closure $belongs = null): array
    {
        $entry = $this->load($challenge, $belongs);

        if (now()->getTimestamp() > $entry['code_expires_at']) {
            throw new ApiException('code_expired', 'کد تأیید منقضی شده است. کد جدید بگیرید.', 422);
        }

        if (! hash_equals($entry['code_hash'], hash('sha256', $code))) {
            $max = (int) config('api.login.max_code_attempts', 5);
            $attempts = (int) Cache::increment($this->attemptsKey($challenge));

            if ($attempts >= $max) {
                $this->forget($challenge);

                throw new ApiException('too_many_code_attempts', 'کد اشتباه بیش از حد مجاز وارد شد. دوباره ثبت‌نام کنید.', 429);
            }

            throw new ApiException('invalid_code', 'کد وارد شده نامعتبر است.', 422, ['attempts_left' => $max - $attempts]);
        }

        $this->forget($challenge);

        return $entry['data'];
    }

    /** کد تازه با فاصله و سقف ارسال دوباره؛ داده‌ی challenge را برمی‌گرداند. */
    public function resend(string $challenge, ?\Closure $belongs = null): array
    {
        $entry = $this->load($challenge, $belongs);

        $wait = $entry['last_sent_at'] + (int) config('api.login.resend_cooldown_seconds', 60) - now()->getTimestamp();
        if ($wait > 0) {
            throw new ApiException('resend_too_soon', 'برای ارسال دوباره‌ی کد کمی صبر کنید.', 429, ['retry_after' => $wait], ['Retry-After' => $wait]);
        }
        if ($entry['resends'] >= (int) config('api.login.max_resends', 3)) {
            throw new ApiException('resend_limit_reached', 'تعداد ارسال دوباره‌ی کد به سقف رسید. دوباره ثبت‌نام کنید.', 429);
        }

        $code = $this->newCode();
        $entry['code_hash'] = hash('sha256', $code);
        $entry['code_expires_at'] = $this->codeExpiry();
        $entry['resends']++;
        $entry['last_sent_at'] = now()->getTimestamp();
        $this->put($challenge, $entry);
        Cache::put($this->attemptsKey($challenge), (int) Cache::get($this->attemptsKey($challenge), 0), $this->ttlSeconds());

        $this->deliver($entry['data'], $code);

        return $entry['data'];
    }

    public function forget(string $challenge): void
    {
        Cache::forget($this->key($challenge));
        Cache::forget($this->attemptsKey($challenge));
    }

    /** پاسخ استاندارد شروع/ارسال دوباره (همان شکل ورود اپ). */
    public function payload(string $challenge, string $phone): array
    {
        return [
            'challenge' => $challenge,
            'expires_in' => $this->ttlSeconds(),
            'code_expires_in' => (int) config('auth.verification_code_expire_minutes', 2) * 60,
            'resend_after' => (int) config('api.login.resend_cooldown_seconds', 60),
            'phone_hint' => substr($phone, 0, 4).'***'.substr($phone, -4),
        ];
    }

    private function load(string $challenge, ?\Closure $belongs): array
    {
        $entry = Cache::get($this->key($challenge));

        if (! is_array($entry) || ($belongs && ! $belongs($entry['data']))) {
            throw new ApiException('challenge_expired', $this->expiredMessage(), 410);
        }

        return $entry;
    }

    private function newCode(): string
    {
        return (string) random_int(100000, 999999);
    }

    private function codeExpiry(): int
    {
        return now()->addMinutes((int) config('auth.verification_code_expire_minutes', 2))->getTimestamp();
    }

    private function put(string $challenge, array $entry): void
    {
        Cache::put($this->key($challenge), $entry, $this->ttlSeconds());
    }

    private function ttlSeconds(): int
    {
        return (int) config('api.login.challenge_ttl_minutes', 10) * 60;
    }

    private function key(string $challenge): string
    {
        return $this->prefix().hash('sha256', $challenge);
    }

    private function attemptsKey(string $challenge): string
    {
        return $this->key($challenge).':attempts';
    }
}
