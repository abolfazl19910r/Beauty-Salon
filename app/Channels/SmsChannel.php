<?php

namespace App\Channels;

use App\Services\SMSService;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    public function __construct(protected readonly SMSService $smsService) {}

    public function send($notifiable, Notification $notification)
    {
        if (method_exists($notification, 'toSms')) {
            return $notification->toSms($notifiable);
        }
    }
}
