<?php

namespace App\Services\SalonSignup;

use App\Exceptions\Api\ApiException;
use App\Jobs\SendRegistrationCodeJob;
use App\Models\Salon;
use App\Models\User;
use App\Repositories\Contracts\SalonRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Auth\SmsCodeChallenge;
use App\Services\Salon\SalonLogoService;
use App\Support\Queues;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * ثبت‌نام عمومی سالن فقط بعد از تأیید کد پیامکی (تصمیم ۲۰۲۶-۱۰-۱۱).
 *
 * قبلاً سالن و مالک پیش از تأیید کد ساخته می‌شدند: ثبت‌نام نیمه‌کاره با شماره‌ی کس دیگری، آن شماره را برای همیشه از کادر
 * می‌گرفت (شماره‌ی کادر در کل پلتفرم یکتاست) و یک slug خوب را بی‌پرداخت نگه می‌داشت. حالا اطلاعات فرم (رمز رمزنگاری‌شده،
 * لوگو در پوشه‌ی موقت خصوصی) پشت challenge می‌ماند و سالن، مالک و دوره‌ی آزمایشی فقط با کد درست ساخته می‌شوند؛ slug و
 * شماره همان لحظه دوباره چک می‌شوند (ثبت‌نام دیگری ممکن است زودتر تأیید شده باشد).
 */
class SalonSignupChallenge extends SmsCodeChallenge
{
    public const PENDING_LOGO_DIR = 'signup-pending';

    public function __construct(
        private readonly SalonSignupService $signupService,
        private readonly SalonRepositoryInterface $salonRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    protected function prefix(): string
    {
        return 'salon-signup:';
    }

    protected function deliver(array $data, string $code): void
    {
        Log::info('Queued salon signup code', ['phone' => $data['phone'], 'code' => $code]);
        Queues::dispatchOtp(new SendRegistrationCodeJob($data['phone'], $code, null));
    }

    /**
     * @param  array  $data  خروجی validated() فرم + contact + intent
     * @return string challenge
     */
    public function start(array $data, ?UploadedFile $logo): string
    {
        $password = $data['owner_password'];
        unset($data['owner_password'], $data['owner_password_confirmation'], $data['logo']);

        $data['phone'] = $data['owner_phone'];
        $data['owner_password_encrypted'] = Crypt::encryptString($password);
        $data['pending_logo'] = $logo
            ? $logo->storeAs(self::PENDING_LOGO_DIR, Str::random(40).'.'.$logo->extension(), 'local')
            : null;

        return $this->open($data);
    }

    /**
     * کد درست → سالن، مالک (تأییدشده) و لوگو ساخته می‌شوند.
     *
     * @return array{salon: Salon, owner: User, intent: string, plan_key: ?string}
     */
    public function complete(string $challenge, string $code): array
    {
        $data = $this->confirm($challenge, $code);

        if ($this->salonRepository->slugExists($data['slug'])) {
            $this->dropLogo($data);
            throw new ApiException('slug_taken', 'این نشانی (slug) در این فاصله به سالن دیگری داده شد. لطفاً نشانی دیگری انتخاب کنید.', 422);
        }
        if ($this->userRepository->staffPhoneExists($data['owner_phone'])) {
            $this->dropLogo($data);
            throw new ApiException('phone_taken', 'این شماره در این فاصله برای حساب کادر دیگری ثبت شد.', 422);
        }

        try {
            $result = DB::transaction(function () use ($data) {
                $result = $this->signupService->register(['owner_password' => Crypt::decryptString($data['owner_password_encrypted'])] + $data);
                $result['owner']->forceFill(['phone_verified_at' => now()])->save();

                return $result;
            });
        } catch (UniqueConstraintViolationException) {
            $this->dropLogo($data);
            throw new ApiException('slug_taken', 'این نشانی یا شماره در این فاصله ثبت شد. لطفاً دوباره ثبت‌نام کنید.', 422);
        }

        if ($data['pending_logo'] && Storage::disk('local')->exists($data['pending_logo'])) {
            $path = Storage::disk('local')->path($data['pending_logo']);
            app(SalonLogoService::class)->replace($result['salon'], new UploadedFile($path, basename($path), null, null, true));
            $this->dropLogo($data);
        }

        return ['salon' => $result['salon']->fresh(), 'owner' => $result['owner'], 'intent' => $data['intent'] ?? 'trial', 'plan_key' => $data['plan_key'] ?? null];
    }

    private function dropLogo(array $data): void
    {
        if (! empty($data['pending_logo'])) {
            Storage::disk('local')->delete($data['pending_logo']);
        }
    }
}
