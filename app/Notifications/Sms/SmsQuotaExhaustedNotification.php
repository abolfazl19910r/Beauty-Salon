<?php

namespace App\Notifications\Sms;

use App\Models\Salon;
use App\Services\SMSService;
use App\Support\Notifications\NotificationEvents;
use App\Traits\RespectsNotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * ⭐ فیچر «سقف/قطع پیامک ماهانه». فرستاده‌شده به هم ادمین‌های خودِ سالن (Salon::admins()) و هم
 * هر کاربری با نقش 'super-admin' — SmsQuotaService::shouldNotifyExhaustion() تضمین می‌کند این
 * فقط یک‌بار در هر ماه تقویمی برای هر سالن ارسال شود.
 */
class SmsQuotaExhaustedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RespectsNotificationSettings;

    public function __construct(private readonly Salon $salon, private readonly int $quota) {}

    public function via(object $notifiable): array
    {
        return $this->gatedChannels(NotificationEvents::SMS_QUOTA_EXHAUSTED_ADMIN, ['database', 'sms']);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sms_quota_exhausted',
            'salon_id' => $this->salon->id,
            'salon_name' => $this->salon->name,
            'quota' => $this->quota,
            'message' => sprintf(
                'سهمیه‌ی پیامک ماهانه‌ی سالن «%s» (%s پیامک) تمام شد. ارسال پیامک برای این سالن تا ماه بعد یا شارژ دستی متوقف است.',
                $this->salon->name,
                number_format($this->quota)
            ),
        ];
    }

    /**
     * ⭐ عمداً بدون salon_id: این پیامک هشداریه، نه یک پیامک عادی سالن — نباید خودش هم قربانی
     * همون سقفی بشه که داره گزارشش می‌ده (وگرنه بعد از اتمام سهمیه، هیچ‌کس هیچ‌وقت خبردار نمی‌شه).
     */
    public function toSms(object $notifiable): bool
    {
        $message = sprintf(
            "⚠️ سهمیه‌ی پیامک ماهانه‌ی سالن «%s» تمام شد (%s پیامک).\nارسال پیامک نوبت/یادآوری/ورود برای این سالن تا شروع ماه بعد یا شارژ دستی توسط پشتیبانی متوقف می‌ماند.",
            $this->salon->name,
            number_format($this->quota)
        );

        return (new SMSService)->send($notifiable->phone, $message);
    }
}
