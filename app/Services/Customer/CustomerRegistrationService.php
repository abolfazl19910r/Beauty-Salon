<?php

namespace App\Services\Customer;

use App\Events\User\NewUserRegistered;
use App\Exceptions\Api\ApiException;
use App\Jobs\SendRegistrationCodeJob;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\PasswordStrengthService;
use App\Support\Queues;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ثبت‌نام مشتری در یک سالن — وب (/s/{slug}/register) و اپ «ماهرو» (تصمیم ۲۰۲۶-۱۰-۱۱).
 *
 * حساب فقط بعد از تأیید کد پیامکی ساخته می‌شود. نام، شماره و هش رمز تا آن لحظه در cache (با یک challenge
 * تصادفی) می‌مانند. قبلاً ردیف کاربر پیش از تأیید ساخته می‌شد: هر کسی می‌توانست با شماره‌ی دیگری ثبت‌نام
 * نیمه‌کاره کند و صاحب شماره بعداً «این شماره قبلاً ثبت شده» می‌دید. حسابی که هنوز تأیید نشده (ثبت‌نام‌های
 * نیمه‌کاره‌ی قبل از این تغییر) جلوی ثبت‌نام را نمی‌گیرد و با تأیید کد به صاحب واقعی شماره می‌رسد.
 *
 * سخت‌گیری‌ها مثل ورود اپ (ApiLoginService): حداکثر api.login.max_code_attempts کد غلط، فاصله و سقف ارسال
 * دوباره، challenge فقط برای همان سالن. کد پیامک احراز هویت است (هزینه‌ی پلتفرم، صف otp).
 */
class CustomerRegistrationService
{
    private const KEY_PREFIX = 'customer-register:';

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordStrengthService $passwordStrength,
    ) {}

    public function phoneTaken(string $phone, int $salonId): bool
    {
        $existing = $this->userRepository->findCustomerByPhoneInSalon($phone, $salonId);

        return $existing !== null && $existing->phone_verified_at !== null;
    }

    public function start(int $salonId, string $name, string $phone, string $password): array
    {
        if ($this->phoneTaken($phone, $salonId)) {
            throw new ApiException('phone_taken', 'این شماره موبایل قبلاً در همین سالن ثبت شده است. وارد شوید یا رمز را بازیابی کنید.', 422);
        }

        $challenge = Str::random(48);
        $code = $this->newCode();

        $this->put($challenge, [
            'salon_id' => $salonId,
            'name' => $name,
            'phone' => $phone,
            'password_hash' => Hash::make($password),
            'strength' => $this->passwordStrength->score($password),
            'code_hash' => hash('sha256', $code),
            'code_expires_at' => $this->codeExpiry(),
            'resends' => 0,
            'last_sent_at' => now()->getTimestamp(),
        ]);
        Cache::put($this->attemptsKey($challenge), 0, $this->ttlSeconds());

        $this->send($phone, $code, $salonId);

        return $this->payload($challenge, $phone);
    }

    public function verify(string $challenge, string $code, int $salonId): User
    {
        $data = $this->load($challenge, $salonId);

        if (now()->getTimestamp() > $data['code_expires_at']) {
            throw new ApiException('code_expired', 'کد تأیید منقضی شده است. کد جدید بگیرید.', 422);
        }

        if (! hash_equals($data['code_hash'], hash('sha256', $code))) {
            $max = (int) config('api.login.max_code_attempts', 5);
            $attempts = (int) Cache::increment($this->attemptsKey($challenge));

            if ($attempts >= $max) {
                $this->forget($challenge);

                throw new ApiException('too_many_code_attempts', 'کد اشتباه بیش از حد مجاز وارد شد. دوباره ثبت‌نام کنید.', 429);
            }

            throw new ApiException('invalid_code', 'کد وارد شده نامعتبر است.', 422, ['attempts_left' => $max - $attempts]);
        }

        $this->forget($challenge);

        try {
            return DB::transaction(fn () => $this->createOrClaim($data));
        } catch (UniqueConstraintViolationException) {
            throw new ApiException('phone_taken', 'این شماره موبایل قبلاً در همین سالن ثبت شده است. وارد شوید یا رمز را بازیابی کنید.', 422);
        }
    }

    public function resend(string $challenge, int $salonId): array
    {
        $data = $this->load($challenge, $salonId);

        $wait = $data['last_sent_at'] + (int) config('api.login.resend_cooldown_seconds', 60) - now()->getTimestamp();
        if ($wait > 0) {
            throw new ApiException('resend_too_soon', 'برای ارسال دوباره‌ی کد کمی صبر کنید.', 429, ['retry_after' => $wait], ['Retry-After' => $wait]);
        }
        if ($data['resends'] >= (int) config('api.login.max_resends', 3)) {
            throw new ApiException('resend_limit_reached', 'تعداد ارسال دوباره‌ی کد به سقف رسید. دوباره ثبت‌نام کنید.', 429);
        }

        $code = $this->newCode();
        $data['code_hash'] = hash('sha256', $code);
        $data['code_expires_at'] = $this->codeExpiry();
        $data['resends']++;
        $data['last_sent_at'] = now()->getTimestamp();
        $this->put($challenge, $data);
        Cache::put($this->attemptsKey($challenge), (int) Cache::get($this->attemptsKey($challenge), 0), $this->ttlSeconds());

        $this->send($data['phone'], $code, (int) $data['salon_id']);

        return $this->payload($challenge, $data['phone']);
    }

    /** شماره‌ی challenge برای نمایش در صفحه‌ی کد وب؛ null اگر منقضی شده باشد. */
    public function phoneOf(string $challenge, int $salonId): ?string
    {
        $data = Cache::get($this->key($challenge));

        return is_array($data) && (int) $data['salon_id'] === $salonId ? $data['phone'] : null;
    }

    private function createOrClaim(array $data): User
    {
        $attributes = [
            'name' => $data['name'],
            'password' => $data['password_hash'],
            'password_changed_at' => now(),
            'password_strength_score' => $data['strength'],
            'phone_verified_at' => now(),
            'verification_code' => null,
            'verification_code_expire_at' => null,
        ];

        $existing = User::query()
            ->where('phone', $data['phone'])->where('salon_id', $data['salon_id'])->where('user_type', 'customer')
            ->lockForUpdate()->first();

        if ($existing && $existing->phone_verified_at !== null) {
            throw new ApiException('phone_taken', 'این شماره موبایل قبلاً در همین سالن ثبت شده است. وارد شوید یا رمز را بازیابی کنید.', 422);
        }

        if ($existing) {
            // ثبت‌نام نیمه‌کاره‌ی قدیمی (پیش از این تغییر) — حالا صاحب واقعی شماره آن را تأیید کرد
            $existing->forceFill($attributes)->save();
            Log::info('Unverified customer account claimed by verified registration', ['user_id' => $existing->id]);

            return $existing;
        }

        $user = new User;
        $user->forceFill($attributes + [
            'phone' => $data['phone'],
            'salon_id' => $data['salon_id'],
            'user_type' => 'customer',
        ])->save();

        event(new NewUserRegistered($user));

        return $user;
    }

    private function load(string $challenge, int $salonId): array
    {
        $data = Cache::get($this->key($challenge));

        if (! is_array($data) || (int) $data['salon_id'] !== $salonId) {
            throw new ApiException('challenge_expired', 'مهلت ثبت‌نام تمام شده است. دوباره ثبت‌نام کنید.', 410);
        }

        return $data;
    }

    private function send(string $phone, string $code, int $salonId): void
    {
        Log::info('Queued customer registration code', ['phone' => $phone, 'code' => $code, 'salon_id' => $salonId]);
        Queues::dispatchOtp(new SendRegistrationCodeJob($phone, $code, $salonId));
    }

    private function payload(string $challenge, string $phone): array
    {
        return [
            'challenge' => $challenge,
            'expires_in' => $this->ttlSeconds(),
            'code_expires_in' => (int) config('auth.verification_code_expire_minutes', 2) * 60,
            'resend_after' => (int) config('api.login.resend_cooldown_seconds', 60),
            'phone_hint' => substr($phone, 0, 4).'***'.substr($phone, -4),
        ];
    }

    private function newCode(): string
    {
        return (string) random_int(100000, 999999);
    }

    private function codeExpiry(): int
    {
        return now()->addMinutes((int) config('auth.verification_code_expire_minutes', 2))->getTimestamp();
    }

    private function put(string $challenge, array $data): void
    {
        Cache::put($this->key($challenge), $data, $this->ttlSeconds());
    }

    private function forget(string $challenge): void
    {
        Cache::forget($this->key($challenge));
        Cache::forget($this->attemptsKey($challenge));
    }

    private function ttlSeconds(): int
    {
        return (int) config('api.login.challenge_ttl_minutes', 10) * 60;
    }

    private function key(string $challenge): string
    {
        return self::KEY_PREFIX.hash('sha256', $challenge);
    }

    private function attemptsKey(string $challenge): string
    {
        return $this->key($challenge).':attempts';
    }
}
