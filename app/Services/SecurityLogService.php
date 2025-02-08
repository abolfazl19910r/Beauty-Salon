<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class SecurityLogService
{
    public function logLogin($success, $username): void
    {
        $data = [
            'event' => 'login_attempt',
            'success' => $success,
            'username' => $username,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()
        ];

        if (!$success) {
            Log::channel('security')->warning('Failed login attempt', $data);
        } else {
            Log::channel('security')->info('Successful login', $data);
        }
    }

    public function logSuspiciousActivity($event, $details = []): void
    {
        $data = array_merge([
            'event' => $event,
            'user_id' => Auth::id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()
        ], $details);

        Log::channel('security')->warning('Suspicious activity detected', $data);
    }

    public function logPaymentAttempt($paymentId, $amount, $success, $details = []): void
    {
        $data = array_merge([
            'event' => 'payment_attempt',
            'payment_id' => $paymentId,
            'amount' => $amount,
            'success' => $success,
            'user_id' => Auth::id(),
            'ip' => request()->ip(),
            'timestamp' => now()
        ], $details);

        Log::channel($success ? 'payments' : 'security')->info(
            $success ? 'Successful payment' : 'Failed payment attempt',
            $data
        );
    }

    public function logProfileChange($field, $oldValue, $newValue): void
    {
        $data = [
            'event' => 'profile_change',
            'user_id' => Auth::id(),
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'ip' => request()->ip(),
            'timestamp' => now()
        ];

        Log::channel('security')->info('Profile information changed', $data);
    }
}
