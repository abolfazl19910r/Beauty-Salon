<?php

namespace App\Notifications;

use App\Models\LoyaltyPoint;
use App\Services\SMSService;
use Illuminate\Notifications\Notification;

class PointsEarned extends Notification
{
    private LoyaltyPoint $loyaltyPoint;
    private SMSService $smsService;

    /**
     *
     * @param LoyaltyPoint $loyaltyPoint
     * @return void
     */
    public function __construct(LoyaltyPoint $loyaltyPoint)
    {
        $this->loyaltyPoint = $loyaltyPoint;
        $this->smsService = new SMSService();
    }

    /**
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via(mixed $notifiable): array
    {
        return ['database', 'sms'];
    }

    /**
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'points' => $this->loyaltyPoint->points,
            'description' => $this->loyaltyPoint->description,
            'booking_id' => $this->loyaltyPoint->booking_id,
            'created_at' => $this->loyaltyPoint->created_at
        ];
    }

    /**
     *
     * @param mixed $notifiable
     * @return bool
     */
    public function toSms(mixed $notifiable): bool
    {
        $message = sprintf(
            "%d امتیاز به حساب کاربری شما اضافه شد. موجودی فعلی: %d",
            $this->loyaltyPoint->points,
            LoyaltyPoint::where('user_id', $notifiable->id)->sum('points')
        );

        return $this->smsService->send($notifiable->phone, $message);
    }
}
