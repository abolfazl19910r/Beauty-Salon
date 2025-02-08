<?php

namespace App\Channels;

use App\Services\SMSService;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    protected SMSService $smsService;

    public function __construct(SMSService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function send($notifiable, Notification $notification)
    {
        if (method_exists($notification, 'toSms')) {
            return $notification->toSms($notifiable);
        }
    }
}
