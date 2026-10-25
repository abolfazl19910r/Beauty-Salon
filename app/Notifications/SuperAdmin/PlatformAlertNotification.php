<?php

namespace App\Notifications\SuperAdmin;

use Illuminate\Notifications\Notification;

/**
 * ⭐ «صندوق اعلان» ابرمدیر (۲۰۲۶-۱۰-۱۵): رویدادهای سطح پلتفرم (تیکت و پاسخ تازه، دموی تازه، ثبت‌نام سالن، پرداخت اشتراک/بسته‌ی
 * پیامک، پرداخت پلتفرمِ نیازمند بررسی، کار ناموفق صف) — فقط داخلی (جدول user_notifications)، صفحه‌ی superadmin/notifications.
 */
class PlatformAlertNotification extends Notification
{
    public const KINDS = [
        'ticket' => 'تیکت تازه',
        'ticket_reply' => 'پاسخ سالن به تیکت',
        'complaint' => 'شکایت تازه (مهلت ۷۲ ساعت)',
        'complaint_due' => 'مهلت پاسخ به شکایت',
        'demo' => 'درخواست دمو',
        'signup' => 'ثبت‌نام سالن',
        'subscription_paid' => 'پرداخت اشتراک',
        'sms_pack_paid' => 'خرید بسته‌ی پیامک',
        'payment_attention' => 'پرداخت نیازمند بررسی',
        'job_failed' => 'کار ناموفق صف',
    ];

    public function __construct(
        public readonly string $kind,
        public readonly string $message,
        public readonly ?string $link = null,
        public readonly ?int $salonId = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function notificationSalonId(object $notifiable): ?int
    {
        return $this->salonId;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'superadmin_alert',
            'kind' => $this->kind,
            'message' => $this->message,
            'link' => $this->link,
            'salon_id' => $this->salonId,
        ];
    }
}
