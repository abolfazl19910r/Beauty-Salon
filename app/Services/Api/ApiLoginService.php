<?php

namespace App\Services\Api;

use App\Exceptions\Api\ApiException;
use App\Models\User;
use App\Services\PhoneVerificationService;
use App\Services\SecurityLogService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ورود دومرحله‌ای اپ بدون session (بسته‌ی ۱ اپلیکیشن) — همان منطق وب: رمز ← کد پیامکی ← ورود.
 *
 * جای session وب (login_user_id) یک «challenge» تصادفی است که در cache با کاربر، اپ (customer/staff) و سالن
 * نگه داشته می‌شود. کد همان کد ورود وب است (PhoneVerificationService::sendLoginCode، صف otp، هزینه‌ی پلتفرم).
 * سخت‌گیری‌های اضافه نسبت به وب (که فقط به throttle آی‌پی تکیه دارد):
 *  - هر challenge حداکثر api.login.max_code_attempts کد غلط؛ بعد می‌سوزد و کد کاربر هم پاک می‌شود.
 *  - ارسال دوباره با فاصله‌ی حداقل resend_cooldown_seconds و حداکثر max_resends بار.
 *  - challenge فقط برای همان اپ و همان سالنی که ساخته شده معتبر است.
 */
class ApiLoginService
{
    protected const KEY_PREFIX = 'api-login:';

    public function __construct(
        protected readonly PhoneVerificationService $verificationService,
        protected readonly SecurityLogService $securityLogService,
    ) {}

    public function start(User $user, string $audience, int $salonId): array
    {
        $this->sendCode($user);

        $challenge = Str::random(48);
        $ttl = $this->ttlSeconds();

        Cache::put($this->key($challenge), [
            'user_id' => $user->id,
            'audience' => $audience,
            'salon_id' => $salonId,
            'resends' => 0,
            'last_sent_at' => now()->getTimestamp(),
        ], $ttl);
        Cache::put($this->attemptsKey($challenge), 0, $ttl);

        return $this->payload($challenge, $user);
    }

    /**
     * @param  int|null  $salonId  برای مشتری سالن مسیر؛ برای کادر null (سالن از challenge خوانده می‌شود)
     */
    public function verify(string $challenge, string $code, string $audience, ?int $salonId): User
    {
        [$data, $user] = $this->load($challenge, $audience, $salonId);

        // کد منقضی تلاش حساب نمی‌شود؛ اپ باید «ارسال دوباره» را پیشنهاد کند
        if (! $user->login_verification_code_expire_at || now()->isAfter($user->login_verification_code_expire_at)) {
            throw new ApiException('code_expired', 'کد تأیید منقضی شده است. کد جدید بگیرید.', 422);
        }

        if ($user->login_verification_code !== null
            && hash_equals((string) $user->login_verification_code, $code)
            && $this->verificationService->verifyLoginCode($user, $code)) {
            $this->forget($challenge);
            $this->securityLogService->logLogin(true, $user->phone, $user);

            return $user;
        }

        $this->securityLogService->logLogin(false, $user->phone, $user);

        $max = (int) config('api.login.max_code_attempts', 5);
        $attempts = (int) Cache::increment($this->attemptsKey($challenge));

        if ($attempts >= $max) {
            $this->forget($challenge);
            $user->forceFill(['login_verification_code' => null, 'login_verification_code_expire_at' => null])->save();

            throw new ApiException('too_many_code_attempts', 'کد اشتباه بیش از حد مجاز وارد شد. دوباره وارد شوید.', 429);
        }

        throw new ApiException('invalid_code', 'کد وارد شده نامعتبر است.', 422, ['attempts_left' => $max - $attempts]);
    }

    public function resend(string $challenge, string $audience, ?int $salonId): array
    {
        [$data, $user] = $this->load($challenge, $audience, $salonId);

        $cooldown = (int) config('api.login.resend_cooldown_seconds', 60);
        $wait = $data['last_sent_at'] + $cooldown - now()->getTimestamp();
        if ($wait > 0) {
            throw new ApiException('resend_too_soon', 'برای ارسال دوباره‌ی کد کمی صبر کنید.', 429, ['retry_after' => $wait], ['Retry-After' => $wait]);
        }

        if ($data['resends'] >= (int) config('api.login.max_resends', 3)) {
            throw new ApiException('resend_limit_reached', 'تعداد ارسال دوباره‌ی کد به سقف رسید. دوباره وارد شوید.', 429);
        }

        $this->sendCode($user);

        $data['resends']++;
        $data['last_sent_at'] = now()->getTimestamp();
        $ttl = $this->ttlSeconds();
        Cache::put($this->key($challenge), $data, $ttl);
        Cache::put($this->attemptsKey($challenge), (int) Cache::get($this->attemptsKey($challenge), 0), $ttl);

        return $this->payload($challenge, $user);
    }

    /**
     * @return array{token: string, token_type: string, expires_at: string, token_id: int}
     */
    public function issueToken(User $user, string $audience, string $deviceName): array
    {
        $expiresAt = now()->addDays((int) config('api.token_idle_days', 90));
        $token = $user->createToken($deviceName, [$audience], $expiresAt);

        return [
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'token_id' => $token->accessToken->getKey(),
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    /**
     * @return array{0: array, 1: User}
     */
    protected function load(string $challenge, string $audience, ?int $salonId): array
    {
        $data = Cache::get($this->key($challenge));

        if (! is_array($data)
            || $data['audience'] !== $audience
            || ($salonId !== null && (int) $data['salon_id'] !== $salonId)) {
            throw $this->expired();
        }

        $user = User::find($data['user_id']);
        if (! $user) {
            $this->forget($challenge);

            throw $this->expired();
        }

        return [$data, $user];
    }

    protected function sendCode(User $user): void
    {
        try {
            $this->verificationService->sendLoginCode($user);
        } catch (\Throwable $e) {
            Log::error('Failed to send API login code', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            throw new ApiException('code_send_failed', 'خطا در ارسال کد تأیید. لطفاً دوباره تلاش کنید.', 503);
        }
    }

    protected function payload(string $challenge, User $user): array
    {
        return [
            'challenge' => $challenge,
            'expires_in' => $this->ttlSeconds(),
            'code_expires_in' => (int) config('auth.verification_code_expire_minutes', 2) * 60,
            'resend_after' => (int) config('api.login.resend_cooldown_seconds', 60),
            'phone_hint' => substr($user->phone, 0, 4).'***'.substr($user->phone, -4),
        ];
    }

    protected function expired(): ApiException
    {
        return new ApiException('challenge_expired', 'مهلت ورود تمام شده است. دوباره وارد شوید.', 410);
    }

    protected function forget(string $challenge): void
    {
        Cache::forget($this->key($challenge));
        Cache::forget($this->attemptsKey($challenge));
    }

    protected function ttlSeconds(): int
    {
        return (int) config('api.login.challenge_ttl_minutes', 10) * 60;
    }

    protected function key(string $challenge): string
    {
        return self::KEY_PREFIX.hash('sha256', $challenge);
    }

    protected function attemptsKey(string $challenge): string
    {
        return $this->key($challenge).':attempts';
    }
}
