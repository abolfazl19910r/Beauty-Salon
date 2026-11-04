<?php

namespace App\Services\Api;

use App\Exceptions\Api\ApiException;
use App\Models\User;
use App\Services\PhoneVerificationService;
use App\Services\SecurityLogService;
use App\Support\Auth\SmsCode;
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

    /** ستون‌های کد روی users — ApiPasswordResetService همان سازوکار را روی کد بازیابی رمز به کار می‌برد */
    protected const CODE_FIELD = 'login_verification_code';

    protected const CODE_EXPIRES_FIELD = 'login_verification_code_expire_at';

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

        return $this->payload($challenge, $user->phone);
    }

    /**
     * بررسی امنیتی L3 (۲۰۲۶-۱۱-۰۵): برای شماره‌ای که حساب ندارد، challenge «ساختگی» با همان شکل پاسخ — پیامکی نمی‌رود، هر کدی غلط
     * است (با همان شمارنده و همان خطاها) و «ارسال دوباره» همان مهلت‌ها را دارد. اپ و مهاجم نمی‌فهمند شماره حساب دارد یا نه.
     */
    public function startDecoy(string $phone, string $audience, ?int $salonId): array
    {
        $challenge = Str::random(48);
        $ttl = $this->ttlSeconds();

        Cache::put($this->key($challenge), [
            'user_id' => null,
            'decoy_phone' => $phone,
            'audience' => $audience,
            'salon_id' => (int) $salonId,
            'resends' => 0,
            'last_sent_at' => now()->getTimestamp(),
            'code_expires_at' => $this->decoyCodeExpiry(),
        ], $ttl);
        Cache::put($this->attemptsKey($challenge), 0, $ttl);

        return $this->payload($challenge, $phone);
    }

    /**
     * @param  int|null  $salonId  برای مشتری سالن مسیر؛ برای کادر null (سالن از challenge خوانده می‌شود)
     */
    public function verify(string $challenge, string $code, string $audience, ?int $salonId): User
    {
        [$data, $user] = $this->load($challenge, $audience, $salonId);

        if ($user === null) {
            // challenge ساختگی (L3): همان رفتار کد غلط
            if (now()->getTimestamp() > (int) ($data['code_expires_at'] ?? 0)) {
                throw new ApiException('code_expired', 'کد تأیید منقضی شده است. کد جدید بگیرید.', 422);
            }

            $this->failAttempt($challenge, null);
        }

        // کد منقضی تلاش حساب نمی‌شود؛ اپ باید «ارسال دوباره» را پیشنهاد کند
        $expiresAt = $user->{static::CODE_EXPIRES_FIELD};
        if (! $expiresAt || now()->isAfter($expiresAt)) {
            throw new ApiException('code_expired', 'کد تأیید منقضی شده است. کد جدید بگیرید.', 422);
        }

        $stored = $user->{static::CODE_FIELD};
        // کد فقط به‌صورت HMAC ذخیره می‌شود (بررسی امنیتی M1، SmsCode)
        if (is_string($stored) && hash_equals($stored, SmsCode::hash($code)) && $this->consumeCode($user, $code)) {
            $this->forget($challenge);
            $this->logAttempt(true, $user);

            return $user;
        }

        $this->logAttempt(false, $user);

        $this->failAttempt($challenge, $user);
    }

    private function failAttempt(string $challenge, ?User $user): never
    {
        $max = (int) config('api.login.max_code_attempts', 5);
        $attempts = (int) Cache::increment($this->attemptsKey($challenge));

        if ($attempts >= $max) {
            $this->forget($challenge);
            $user?->forceFill([static::CODE_FIELD => null, static::CODE_EXPIRES_FIELD => null])->save();

            throw new ApiException('too_many_code_attempts', 'کد اشتباه بیش از حد مجاز وارد شد. دوباره وارد شوید.', 429);
        }

        throw new ApiException('invalid_code', 'کد وارد شده نامعتبر است.', 422, ['attempts_left' => $max - $attempts]);
    }

    private function decoyCodeExpiry(): int
    {
        return now()->addMinutes((int) config(static::CODE_FIELD === 'verification_code' ? 'auth.reset_code_expire_minutes' : 'auth.verification_code_expire_minutes', 2))->getTimestamp();
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

        if ($user !== null) {
            $this->sendCode($user);
        } else {
            $data['code_expires_at'] = $this->decoyCodeExpiry();
        }

        $data['resends']++;
        $data['last_sent_at'] = now()->getTimestamp();
        $ttl = $this->ttlSeconds();
        Cache::put($this->key($challenge), $data, $ttl);
        Cache::put($this->attemptsKey($challenge), (int) Cache::get($this->attemptsKey($challenge), 0), $ttl);

        return $this->payload($challenge, $user?->phone ?? (string) $data['decoy_phone']);
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
     * @return array{0: array, 1: ?User} — کاربر null فقط برای challenge ساختگی (startDecoy)
     */
    protected function load(string $challenge, string $audience, ?int $salonId): array
    {
        $data = Cache::get($this->key($challenge));

        if (! is_array($data)
            || $data['audience'] !== $audience
            || ($salonId !== null && (int) $data['salon_id'] !== $salonId)) {
            throw $this->expired();
        }

        if ($data['user_id'] === null && isset($data['decoy_phone'])) {
            return [$data, null];
        }

        $user = User::find($data['user_id']);
        if (! $user) {
            $this->forget($challenge);

            throw $this->expired();
        }

        return [$data, $user];
    }

    /** کد درست است: مصرفش کن (یک‌بارمصرف). */
    protected function consumeCode(User $user, string $code): bool
    {
        return $this->verificationService->verifyLoginCode($user, $code);
    }

    protected function logAttempt(bool $success, User $user): void
    {
        $this->securityLogService->logLogin($success, $user->phone, $user);
    }

    protected function deliverCode(User $user): void
    {
        $this->verificationService->sendLoginCode($user);
    }

    protected function sendCode(User $user): void
    {
        try {
            $this->deliverCode($user);
        } catch (\Throwable $e) {
            Log::error('Failed to send API login code', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            throw new ApiException('code_send_failed', 'خطا در ارسال کد تأیید. لطفاً دوباره تلاش کنید.', 503);
        }
    }

    protected function payload(string $challenge, string $phone): array
    {
        return [
            'challenge' => $challenge,
            'expires_in' => $this->ttlSeconds(),
            'code_expires_in' => (int) config('auth.verification_code_expire_minutes', 2) * 60,
            'resend_after' => (int) config('api.login.resend_cooldown_seconds', 60),
            'phone_hint' => substr($phone, 0, 4).'***'.substr($phone, -4),
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
        return static::KEY_PREFIX.hash('sha256', $challenge);
    }

    protected function attemptsKey(string $challenge): string
    {
        return $this->key($challenge).':attempts';
    }
}
