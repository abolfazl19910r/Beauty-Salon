<?php

namespace App\Services;

use App\Jobs\SendLoginVerificationCodeJob;
use App\Jobs\SendPhoneVerificationCodeJob;
use App\Models\User;
use Carbon\Carbon;
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
        $code = $this->generateCode();

        $user->update([
            'verification_code' => $code,
            'verification_code_expire_at' => Carbon::now()->addMinutes(
                config('auth.verification_code_expire_minutes', 2)
            ),
        ]);

        Log::info('Queued phone verification code for sending', [
            'user_id' => $user->id,
            'phone' => $user->phone,
            'code' => $code,
            'expires_at' => $user->verification_code_expire_at,
        ]);

        SendPhoneVerificationCodeJob::dispatch($user->id, $code);

        return true;
    }

    public function sendLoginCode(User $user): bool
    {
        $code = $this->generateCode();

        $user->update([
            'login_verification_code' => $code,
            'login_verification_code_expire_at' => Carbon::now()->addMinutes(
                config('auth.verification_code_expire_minutes', 2)
            ),
        ]);

        Log::info('Queued login verification code for sending', [
            'user_id' => $user->id,
            'phone' => $user->phone,
            'code' => $code,
            'expires_at' => $user->login_verification_code_expire_at,
        ]);

        SendLoginVerificationCodeJob::dispatch($user->id, $code);

        return true;
    }

    public function verify(User $user, string $code): bool
    {
        if ($user->verification_code !== $code) {
            Log::warning('Invalid verification code', [
                'user_id' => $user->id,
                'expected' => $user->verification_code,
                'received' => $code,
            ]);

            return false;
        }

        if (Carbon::now()->isAfter($user->verification_code_expire_at)) {
            Log::warning('Verification code expired', [
                'user_id' => $user->id,
                'expired_at' => $user->verification_code_expire_at,
            ]);

            return false;
        }

        return $user->markPhoneAsVerified();
    }

    public function verifyLoginCode(User $user, string $code): bool
    {
        if ($user->login_verification_code !== $code) {
            Log::warning('Invalid login verification code', [
                'user_id' => $user->id,
                'expected' => $user->login_verification_code,
                'received' => $code,
            ]);

            return false;
        }

        if (Carbon::now()->isAfter($user->login_verification_code_expire_at)) {
            Log::warning('Login verification code expired', [
                'user_id' => $user->id,
                'expired_at' => $user->login_verification_code_expire_at,
            ]);

            return false;
        }

        $user->update([
            'login_verification_code' => null,
            'login_verification_code_expire_at' => null,
        ]);

        return true;
    }

    protected function generateCode(): string
    {
        return (string) rand(100000, 999999);
    }
}
