<?php

namespace App\Services;

use App\Jobs\SendLoginVerificationCodeJob;
use App\Jobs\SendPhoneVerificationCodeJob;
use App\Models\User;
use App\Support\Auth\SmsCode;
use App\Support\Queues;
use App\Support\Sms\OtpLog;
use Illuminate\Support\Facades\Log;

class PhoneVerificationService
{
    public function __construct(protected readonly SMSService $smsService) {}

    /**
     * ⭐ Fix (test-writing session 10): the actual SMS send is now dispatched
     * asynchronously (SendPhoneVerificationCodeJob), matching the pattern already
     * established for sendLoginCode()/SendLoginVerificationCodeJob — code
     * generation/storage stays synchronous and fast, only the Kavenegar HTTP call
     * (which can legitimately take 20-30+ seconds when the API is slow/unreachable)
     * moves to the queue. This method is used both by registration
     * (RegisteredUserController) and by the post-auth phone-verification-notice flow
     * (PhoneVerificationController), so both benefit from the same fix.
     */
    public function sendCode(User $user): bool
    {
        $code = SmsCode::issue($user, 'verification_code', 'verification_code_expire_at', $this->expiryMinutes());

        Log::log(OtpLog::level(), 'Queued phone verification code for sending', ['user_id' => $user->id] + OtpLog::code($code));

        Queues::dispatchOtp(new SendPhoneVerificationCodeJob($user->id, $code));

        return true;
    }

    public function sendLoginCode(User $user): bool
    {
        $code = SmsCode::issue($user, 'login_verification_code', 'login_verification_code_expire_at', $this->expiryMinutes());

        Log::log(OtpLog::level(), 'Queued login verification code for sending', ['user_id' => $user->id] + OtpLog::code($code));

        Queues::dispatchOtp(new SendLoginVerificationCodeJob($user->id, $code));

        return true;
    }

    /** بررسی امنیتی M1: HMAC + hash_equals + حداکثر ۵ تلاش برای هر کد (SmsCode) */
    public function verify(User $user, string $code): bool
    {
        if (! SmsCode::check($user, 'verification_code', 'verification_code_expire_at', $code)) {
            Log::channel('security')->warning('Invalid or expired verification code', ['user_id' => $user->id]);

            return false;
        }

        return $user->markPhoneAsVerified();
    }

    public function verifyLoginCode(User $user, string $code): bool
    {
        if (! SmsCode::check($user, 'login_verification_code', 'login_verification_code_expire_at', $code)) {
            Log::channel('security')->warning('Invalid or expired login verification code', ['user_id' => $user->id]);

            return false;
        }

        // کد ورود به همین شماره رفته، پس شماره تأیید است (۲۰۲۶-۱۰-۲۲: حساب متخصصی که مدیر با رمز اولیه ساخته بعد از ورود به صفحه‌ی
        // «تأیید شماره» و پیامک دوم نمی‌رود)
        $user->forceFill([
            'login_verification_code' => null,
            'login_verification_code_expire_at' => null,
            'phone_verified_at' => $user->phone_verified_at ?? now(),
        ])->save();

        return true;
    }

    protected function expiryMinutes(): int
    {
        return (int) config('auth.verification_code_expire_minutes', 2);
    }
}
