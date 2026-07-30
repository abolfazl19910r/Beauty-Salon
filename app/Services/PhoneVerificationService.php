<?php

namespace App\Services;

use App\Jobs\SendLoginVerificationCodeJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PhoneVerificationService
{
    public function __construct(protected readonly SMSService $smsService)
    {
    }

    public function sendCode(User $user): bool
    {
        $code = $this->generateCode();

        $user->update([
            'verification_code' => $code,
            'verification_code_expire_at' => Carbon::now()->addMinutes(
                config('auth.verification_code_expire_minutes', 2)
            )
        ]);

        $template = config('services.kavenegar.templates.register_verify');

        Log::info('Sending registration verification code', [
            'user_id' => $user->id,
            'phone' => $user->phone,
            'template' => $template,
            'code' => $code
        ]);

        $result = $this->smsService->sendTemplate(
            $user->phone,
            $template,
            [(string)$code]
        );

        if (!$result) {
            Log::error('Failed to send registration verification code', [
                'user_id' => $user->id,
                'phone' => $user->phone
            ]);
        }

        return $result;
    }

    public function sendLoginCode(User $user): bool
    {
        $code = $this->generateCode();

        $user->update([
            'login_verification_code' => $code,
            'login_verification_code_expire_at' => Carbon::now()->addMinutes(
                config('auth.verification_code_expire_minutes', 2)
            )
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
                'received' => $code
            ]);
            return false;
        }

        if (Carbon::now()->isAfter($user->verification_code_expire_at)) {
            Log::warning('Verification code expired', [
                'user_id' => $user->id,
                'expired_at' => $user->verification_code_expire_at
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
                'received' => $code
            ]);
            return false;
        }

        if (Carbon::now()->isAfter($user->login_verification_code_expire_at)) {
            Log::warning('Login verification code expired', [
                'user_id' => $user->id,
                'expired_at' => $user->login_verification_code_expire_at
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
