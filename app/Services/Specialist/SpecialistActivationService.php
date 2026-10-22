<?php

namespace App\Services\Specialist;

use App\Exceptions\Api\ApiException;
use App\Jobs\SendRegistrationCodeJob;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Auth\SmsCodeChallenge;
use App\Services\PasswordStrengthService;
use App\Support\Queues;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * ⭐ فعال‌سازی حساب متخصص با کد پیامکی (آزمون دستی ۲۰۲۶-۱۰-۲۲) — وب (/specialist/activate) و اپ «ماهرو همکار»
 * (POST /api/v1/staff/activate). فقط شماره‌ای که مدیر سالن به‌عنوان متخصص ثبت کرده و هنوز حساب ندارد؛ حساب بعد از کد درست ساخته
 * می‌شود (SpecialistAccountLinker::activate). کد پیامک احراز هویت است (هزینه‌ی پلتفرم، صف otp، قالب ثبت‌نام).
 */
class SpecialistActivationService extends SmsCodeChallenge
{
    public const NOT_SPECIALIST = 'این شماره به‌عنوان متخصص در هیچ سالنی ثبت نشده است. از مدیر سالن بخواهید شما را با همین شماره در «متخصصین» اضافه کند.';

    public const HAS_ACCOUNT = 'این شماره حساب کادر دارد. با همین شماره وارد شوید یا رمز را بازیابی کنید.';

    public function __construct(
        private readonly SpecialistAccountLinker $linker,
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordStrengthService $passwordStrength,
    ) {}

    protected function prefix(): string
    {
        return 'specialist-activate:';
    }

    protected function expiredMessage(): string
    {
        return 'مهلت فعال‌سازی تمام شده است. دوباره شروع کنید.';
    }

    protected function deliver(array $data, string $code): void
    {
        Log::info('Queued specialist activation code', ['phone' => $data['phone'], 'code' => $code, 'salon_id' => $data['salon_id']]);
        Queues::dispatchOtp(new SendRegistrationCodeJob($data['phone'], $code, $data['salon_id']));
    }

    /** پیش از فرستادن پیامک: فقط متخصص بی‌حساب */
    public function start(string $phone, string $password): array
    {
        if ($this->userRepository->findStaffByPhone($phone) !== null) {
            throw new ApiException('account_exists', self::HAS_ACCOUNT, 422);
        }

        $specialist = $this->linker->pending($phone)->first();
        if ($specialist === null) {
            throw new ApiException('not_a_specialist', self::NOT_SPECIALIST, 422);
        }

        $challenge = $this->open([
            'phone' => $phone,
            'salon_id' => (int) $specialist->salon_id,
            'password_hash' => Hash::make($password),
            'strength' => $this->passwordStrength->score($password),
        ]);

        return $this->payload($challenge, $phone);
    }

    public function verify(string $challenge, string $code): User
    {
        $data = $this->confirm($challenge, $code);

        return $this->linker->activate($data)
            ?? throw new ApiException('account_exists', self::HAS_ACCOUNT, 422);
    }

    public function resendFor(string $challenge): array
    {
        $data = $this->resend($challenge);

        return $this->payload($challenge, $data['phone']);
    }

    public function phoneOf(string $challenge): ?string
    {
        return $this->peek($challenge)['phone'] ?? null;
    }
}
