<?php

namespace App\Services\Customer;

use App\Events\User\NewUserRegistered;
use App\Exceptions\Api\ApiException;
use App\Jobs\SendRegistrationCodeJob;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Auth\SmsCodeChallenge;
use App\Services\PasswordStrengthService;
use App\Support\Queues;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * ثبت‌نام مشتری در یک سالن — وب (/s/{slug}/register) و اپ «ماهرو» (تصمیم ۲۰۲۶-۱۰-۱۱).
 *
 * حساب فقط بعد از تأیید کد پیامکی ساخته می‌شود. نام، شماره و هش رمز تا آن لحظه در cache (با یک challenge
 * تصادفی) می‌مانند. قبلاً ردیف کاربر پیش از تأیید ساخته می‌شد: هر کسی می‌توانست با شماره‌ی دیگری ثبت‌نام
 * نیمه‌کاره کند و صاحب شماره بعداً «این شماره قبلاً ثبت شده» می‌دید. حسابی که هنوز تأیید نشده (ثبت‌نام‌های
 * نیمه‌کاره‌ی قبل از این تغییر) جلوی ثبت‌نام را نمی‌گیرد و با تأیید کد به صاحب واقعی شماره می‌رسد.
 *
 * سازوکار challenge و کد در SmsCodeChallenge (مشترک با ثبت‌نام سالن)؛ challenge فقط برای همان سالن. کد پیامک احراز هویت
 * است (هزینه‌ی پلتفرم، صف otp).
 */
class CustomerRegistrationService extends SmsCodeChallenge
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordStrengthService $passwordStrength,
    ) {}

    protected function prefix(): string
    {
        return 'customer-register:';
    }

    protected function deliver(array $data, string $code): void
    {
        Log::info('Queued customer registration code', ['phone' => $data['phone'], 'code' => $code, 'salon_id' => $data['salon_id']]);
        Queues::dispatchOtp(new SendRegistrationCodeJob($data['phone'], $code, (int) $data['salon_id']));
    }

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

        $challenge = $this->open([
            'salon_id' => $salonId,
            'name' => $name,
            'phone' => $phone,
            'password_hash' => Hash::make($password),
            'strength' => $this->passwordStrength->score($password),
        ]);

        return $this->payload($challenge, $phone);
    }

    public function verify(string $challenge, string $code, int $salonId): User
    {
        $data = $this->confirm($challenge, $code, fn (array $d) => (int) $d['salon_id'] === $salonId);

        try {
            return DB::transaction(fn () => $this->createOrClaim($data));
        } catch (UniqueConstraintViolationException) {
            throw new ApiException('phone_taken', 'این شماره موبایل قبلاً در همین سالن ثبت شده است. وارد شوید یا رمز را بازیابی کنید.', 422);
        }
    }

    public function resendFor(string $challenge, int $salonId): array
    {
        $data = $this->resend($challenge, fn (array $d) => (int) $d['salon_id'] === $salonId);

        return $this->payload($challenge, $data['phone']);
    }

    /** شماره‌ی challenge برای نمایش در صفحه‌ی کد وب؛ null اگر منقضی شده باشد. */
    public function phoneOf(string $challenge, int $salonId): ?string
    {
        $data = $this->peek($challenge);

        return $data !== null && (int) $data['salon_id'] === $salonId ? $data['phone'] : null;
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
}
