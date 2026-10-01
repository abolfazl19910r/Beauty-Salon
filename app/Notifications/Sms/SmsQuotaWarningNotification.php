<?php

namespace App\Notifications\Sms;

use App\Models\Salon;
use App\Notifications\Concerns\SendsSmsOnSmsQueue;
use App\Services\SMSService;
use App\Support\Notifications\NotificationEvents;
use App\Traits\RespectsNotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * هشدار مصرف ۸۰٪ سهمیه‌ی پیامک ماهانه (۲۰۲۶-۰۹-۳۰)، یک بار در ماه (SmsQuotaService::shouldWarnNearLimit)، به مدیران سالن —
 * تا پیش از قطع شدن پیامک‌ها بسته بخرند. پیامکش مثل بقیه‌ی پیامک‌های سالن از سهمیه کم می‌شود (هنوز ۲۰٪ مانده).
 */
class SmsQuotaWarningNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationSettings, SendsSmsOnSmsQueue;

    public function __construct(private readonly Salon $salon, private readonly int $quota, private readonly int $credit) {}

    protected function settingsSalonId(): ?int
    {
        return $this->salon->id;
    }

    public function via(object $notifiable): array
    {
        return $this->gatedChannels(NotificationEvents::SMS_QUOTA_WARNING_ADMIN, ['database', 'sms'], $notifiable);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sms_quota_warning',
            'salon_id' => $this->salon->id,
            'quota' => $this->quota,
            'credit' => $this->credit,
            'message' => sprintf(
                '۸۰٪ سهمیه‌ی پیامک این ماه (%s قطعه) مصرف شد. اعتبار خریده‌شده: %s قطعه. برای قطع نشدن پیامک‌ها از «صورتحساب» بسته‌ی پیامک بخرید.',
                number_format($this->quota),
                number_format($this->credit)
            ),
            'link' => route('admin.billing.index', [], false),
        ];
    }

    public function toSms(object $notifiable): bool
    {
        return app(SMSService::class)->send(
            $notifiable->phone,
            '۸۰٪ پیامک این ماه مصرف شد. برای قطع نشدن، از صورتحساب بسته بخرید.',
            $this->salon->id
        );
    }
}
