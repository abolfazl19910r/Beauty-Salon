<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TwoFactorAuthService
{
    protected SMSService $smsService;

    public function __construct(SMSService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function generateCode(User $user): string
    {
        $code = rand(100000, 999999);

        Cache::put("2fa:{$user->id}", $code, now()->addMinutes(2));

        $template = config('services.kavenegar.templates.login_verify');

        $this->smsService->sendTemplate($user->phone, $template, [(string)$code]);

        Log::info('2FA code generated', [
            'user_id' => $user->id,
            'phone' => $user->phone
        ]);

        return $code;
    }

    public function verify(User $user, string $code): bool
    {
        $storedCode = Cache::get("2fa:{$user->id}");

        if (!$storedCode) {
            Log::warning('2FA code expired or not found', [
                'user_id' => $user->id
            ]);
            return false;
        }

        $isValid = $storedCode === $code;

        if ($isValid) {
            Cache::forget("2fa:{$user->id}");
            Log::info('2FA verified successfully', [
                'user_id' => $user->id
            ]);
        } else {
            Log::warning('2FA verification failed', [
                'user_id' => $user->id
            ]);
        }

        return $isValid;
    }

    public function isEnabled(User $user): bool
    {
        return $user->two_factor_enabled;
    }

    public function enable(User $user): void
    {
        $user->update([
            'two_factor_enabled' => true
        ]);

        Log::info('2FA enabled', [
            'user_id' => $user->id
        ]);
    }

    public function disable(User $user): void
    {
        $user->update([
            'two_factor_enabled' => false
        ]);

        Log::info('2FA disabled', [
            'user_id' => $user->id
        ]);
    }
}
