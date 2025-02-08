<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class PhoneVerificationService
{
    public function sendCode(User $user)
    {
        $code = rand(100000, 999999);

        $user->update([
            'verification_code' => $code,
            'verification_code_expire_at' => Carbon::now()->addMinutes(2)
        ]);

        $smsService = new SMSService();
        $message = "کد تایید شما: {$code}";

        return $smsService->send($user->phone, $message);
    }

    public function verify(User $user, string $code): bool
    {
        if ($user->verification_code !== $code) {
            return false;
        }

        if (Carbon::now()->isAfter($user->verification_code_expire_at)) {
            return false;
        }

        return $user->markPhoneAsVerified();
    }
}
