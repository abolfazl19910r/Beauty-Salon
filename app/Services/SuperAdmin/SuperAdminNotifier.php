<?php

namespace App\Services\SuperAdmin;

use App\Notifications\SuperAdmin\PlatformAlertNotification;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * فرستادن اعلان به «صندوق اعلان» همه‌ی ابرمدیرها (۲۰۲۶-۱۰-۱۵). بعد از commit تراکنش جاری (پرداخت/ثبت‌نام در تراکنش‌اند)،
 * و خطایش هرگز کار اصلی را نمی‌شکند.
 */
class SuperAdminNotifier
{
    public function send(string $kind, string $message, ?string $link = null, ?int $salonId = null): void
    {
        DB::afterCommit(function () use ($kind, $message, $link, $salonId) {
            try {
                $superAdmins = app(UserRepositoryInterface::class)->getSuperAdmins();
                if ($superAdmins->isNotEmpty()) {
                    Notification::send($superAdmins, new PlatformAlertNotification($kind, $message, $link, $salonId));
                }
            } catch (\Throwable $e) {
                Log::warning('SuperAdminNotifier failed', ['kind' => $kind, 'error' => $e->getMessage()]);
            }
        });
    }
}
