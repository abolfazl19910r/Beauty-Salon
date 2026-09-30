<?php

namespace App\Notifications\Concerns;

/**
 * برای اعلان‌هایی که تا ۲۰۲۶-۰۹-۳۰ همزمان بودند: فقط کانال پیامک به صف sms می‌رود (کاوه‌نگار کند دیگر درخواست HTTP را
 * نگه نمی‌دارد)؛ اعلان داخلی (database) مثل قبل همان لحظه و در همان پردازه ثبت می‌شود — salon_id آن در نبود
 * notificationSalonId() از سالن جاری درخواست می‌آید که worker ندارد (UserNotification::salonIdFor).
 */
trait QueuesOnlySmsChannel
{
    use SendsSmsOnSmsQueue;

    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }
}
