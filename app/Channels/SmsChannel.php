<?php

namespace App\Channels;

use App\Notifications\Contracts\PushesToApp;
use App\Services\Notification\NotificationSettingService;
use App\Services\Push\AppMessenger;
use App\Services\Push\Outgoing;
use App\Services\Push\SmsCapture;
use App\Services\SMSService;
use Illuminate\Notifications\Notification;

/**
 * کانال پیامک اعلان‌ها. اعلانی که PushesToApp است (بسته‌ی ۴) برای گیرنده‌ی دارای اپ از AppMessenger می‌گذرد: متن همان toSms()،
 * تصمیم «پوش یا پیامک یا هر دو» با حالت پلتفرم و سوییچ‌های «پیامک» و «اپ» همان رویداد در تنظیمات اعلان سالن.
 */
class SmsChannel
{
    public function __construct(protected readonly SMSService $smsService) {}

    public function send($notifiable, Notification $notification)
    {
        if (! method_exists($notification, 'toSms')) {
            return null;
        }

        if (! $notification instanceof PushesToApp) {
            return $notification->toSms($notifiable);
        }

        $salonId = method_exists($notification, 'notificationSalonId') ? $notification->notificationSalonId($notifiable) : null;
        $messenger = app(AppMessenger::class);
        $event = $notification->pushKey($notifiable);

        if (! $messenger->hasDevices($notifiable, $salonId, $event)) {
            // بدون اپ: دقیقاً رفتار قبلی (سوییچ پیامک را via() از پیش اعمال کرده؛ این کانال فقط برای اپ اضافه می‌شد)
            return app(NotificationSettingService::class)->isEnabled($event, 'sms', $salonId) ? $notification->toSms($notifiable) : null;
        }

        $handled = false;
        foreach (SmsCapture::run(fn () => $notification->toSms($notifiable)) as $sms) {
            $handled = $messenger->deliver(new Outgoing(
                recipient: $notifiable,
                phone: $sms['mobile'],
                text: $sms['message'],
                salonId: $sms['salon_id'] ?? $salonId,
                smsFeature: $sms['feature'],
                pushKey: $event,
                settingsEvent: $event,
                data: $notification->pushData(),
            )) || $handled;
        }

        return $handled;
    }
}
