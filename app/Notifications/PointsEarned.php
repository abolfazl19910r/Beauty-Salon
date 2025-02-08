<?php

namespace App\Notifications;

use App\Models\LoyaltyPoint;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PointsEarned extends Notification
{
    use Queueable;

    protected $loyaltyPoint;

    public function __construct($loyaltyPoint)
    {
        $this->loyaltyPoint = $loyaltyPoint;
    }

    public function via($notifiable): array
    {
        return ['database', 'sms'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'points' => $this->loyaltyPoint->points,
            'description' => $this->loyaltyPoint->description,
            'booking_id' => $this->loyaltyPoint->booking_id,
            'created_at' => $this->loyaltyPoint->created_at
        ];
    }

    public function toSms($notifiable): string
    {
        return "{$this->loyaltyPoint->points} امتیاز به حساب کاربری شما اضافه شد. موجودی فعلی: " .
            LoyaltyPoint::where('user_id', $notifiable->id)->sum('points');
    }
}
