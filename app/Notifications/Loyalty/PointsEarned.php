<?php

namespace App\Notifications\Loyalty;

use App\Models\LoyaltyPoint;
use App\Services\SMSService;
use App\Support\Notifications\NotificationEvents;
use App\Traits\RespectsNotificationSettings;
use Illuminate\Notifications\Notification;

class PointsEarned extends Notification
{
    use RespectsNotificationSettings;

    private LoyaltyPoint $loyaltyPoint;

    private SMSService $smsService;

    /**
     * @return void
     */
    public function __construct(LoyaltyPoint $loyaltyPoint)
    {
        $this->loyaltyPoint = $loyaltyPoint;
        $this->smsService = new SMSService;
    }

    public function via(mixed $notifiable): array
    {
        return $this->gatedChannels(NotificationEvents::LOYALTY_POINTS_EARNED_CUSTOMER, ['database', 'sms']);
    }

    public function toDatabase(mixed $notifiable): array
    {
        return [
            'points' => $this->loyaltyPoint->points,
            'description' => $this->loyaltyPoint->description,
            'booking_id' => $this->loyaltyPoint->booking_id,
            'created_at' => $this->loyaltyPoint->created_at,
        ];
    }

    public function toSms(mixed $notifiable): bool
    {
        $message = sprintf(
            '%d امتیاز به حساب کاربری شما اضافه شد. موجودی فعلی: %d',
            $this->loyaltyPoint->points,
            LoyaltyPoint::where('user_id', $notifiable->id)->sum('points')
        );

        return $this->smsService->send($notifiable->phone, $message);
    }
}
