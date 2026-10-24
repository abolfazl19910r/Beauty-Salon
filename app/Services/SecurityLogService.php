<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\SecurityLogRepositoryInterface;
use App\Support\CurrentSalon;
use Illuminate\Database\Eloquent\Model;
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

    /**
     * ⭐ کار کلیدی کاربر با IP و user agent (بسته‌ی مجوز 5د؛ ماده‌ی ۶۶۸ آیین دادرسی کیفری): نوبت، نظر، تیکت، ثبت‌نام سالن، رمز،
     * موبایل، حذف حساب، پرداخت — App\Observers\KeyActionAuditObserver. فقط در درخواست واقعی HTTP (MarkClientRequest)؛
     * کارهای صف و scheduler (لغو خودکار و…) IP کاربری ندارند و ثبت نمی‌شوند.
     */
    public function logAction(string $event, ?Model $subject = null, array $context = [], ?int $userId = null): void
    {
        if (! self::hasClientRequest()) {
            return;
        }

        $this->persist(event: $event, level: 'info', context: $context, userId: $userId, subject: $subject);
    }

    public static function hasClientRequest(): bool
    {
        return app()->bound('request') && request()->attributes->get(\App\Http\Middleware\MarkClientRequest::ATTRIBUTE) === true;
    }

    private function persist(string $event, string $level, array $context = [], ?int $userId = null, ?Model $subject = null): void
    {
        $this->securityLogRepository->create([
            'user_id' => $userId ?? Auth::id(),
            'salon_id' => app(CurrentSalon::class)->id() ?? ($subject?->getAttribute('salon_id')),
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'event' => $event,
            'level' => $level,
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 1000),
            'context' => $context,
        ]);
    }
}
