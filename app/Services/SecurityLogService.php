<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\SecurityLogRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Previously, this service only wrote to the log file (Log::channel('security')); SecurityController
 * read from a DB table of the same name that never existed and was never populated anywhere — meaning that even
 * when the table was created, it was always empty. Now every method writes to both the file (for raw server debugging) and
 * the security_logs table (for actual display in the security dashboard/history).
 */
class SecurityLogService
{
    public function __construct(private readonly SecurityLogRepositoryInterface $securityLogRepository) {}

    public function logLogin(bool $success, string $username, ?User $user = null): void
    {
        $data = [
            'event' => 'login_attempt',
            'success' => $success,
            'username' => $username,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
        ];

        if (! $success) {
            Log::channel('security')->warning('Failed login attempt', $data);
        } else {
            Log::channel('security')->info('Successful login', $data);
        }

        $this->persist(
            event: 'login_attempt',
            level: $success ? 'info' : 'warning',
            userId: $user?->id ?? Auth::id(),
            context: ['success' => $success, 'username' => $username],
        );
    }

    public function logSuspiciousActivity(string $event, array $details = []): void
    {
        $data = array_merge([
            'event' => $event,
            'user_id' => Auth::id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
        ], $details);

        Log::channel('security')->warning('Suspicious activity detected', $data);

        $this->persist(event: $event, level: 'warning', context: $details);
    }

    /**
     * ⭐ Fix (real bug, confirmed by actually running the test suite — not just reading code):
     * SecurePaymentController::initiate()'s catch block deliberately calls this with a null
     * $paymentId when PaymentService::createPayment() itself throws (there's no Payment row yet
     * to reference), but this was strictly typed `string` with no `?` — every payment-initiation
     * failure crashed with an uncaught TypeError from inside the *error-logging* call itself,
     * instead of the graceful "پرداخت با مشکل مواجه شد" response the controller intended. Nothing
     * in this method actually requires a non-null string; it's only ever used as a log/context
     * value.
     */
    public function logPaymentAttempt(?string $paymentId, $amount, bool $success, array $details = []): void
    {
        $data = array_merge([
            'event' => 'payment_attempt',
            'payment_id' => $paymentId,
            'amount' => $amount,
            'success' => $success,
            'user_id' => Auth::id(),
            'ip' => request()->ip(),
            'timestamp' => now(),
        ], $details);

        Log::channel($success ? 'payments' : 'security')->info(
            $success ? 'Successful payment' : 'Failed payment attempt',
            $data
        );

        $this->persist(
            event: 'payment_attempt',
            level: $success ? 'info' : 'warning',
            context: array_merge(['payment_id' => $paymentId, 'amount' => $amount, 'success' => $success], $details),
        );
    }

    public function logProfileChange(string $field, $oldValue, $newValue): void
    {
        $data = [
            'event' => 'profile_change',
            'user_id' => Auth::id(),
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'ip' => request()->ip(),
            'timestamp' => now(),
        ];

        Log::channel('security')->info('Profile information changed', $data);

        $this->persist(
            event: 'profile_change',
            level: 'info',
            context: ['field' => $field, 'old_value' => $oldValue, 'new_value' => $newValue],
        );
    }

    private function persist(string $event, string $level, array $context = [], ?int $userId = null): void
    {
        $this->securityLogRepository->create([
            'user_id' => $userId ?? Auth::id(),
            'event' => $event,
            'level' => $level,
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
            'context' => $context,
        ]);
    }
}
