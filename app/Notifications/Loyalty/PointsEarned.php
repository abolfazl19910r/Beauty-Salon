<?php

namespace App\Notifications\Loyalty;

use App\Models\LoyaltyPoint;
use App\Notifications\Concerns\QueuesOnlySmsChannel;
use App\Services\SMSService;
use App\Support\Notifications\NotificationEvents;
use App\Traits\RespectsNotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class PointsEarned extends Notification implements ShouldQueue
{
    use Queueable, QueuesOnlySmsChannel, RespectsNotificationSettings, SerializesModels;

    private LoyaltyPoint $loyaltyPoint;

    /**
     * @return void
     */
    public function __construct(LoyaltyPoint $loyaltyPoint)
    {
        $this->loyaltyPoint = $loyaltyPoint;
    }

    protected function settingsSalonId(): ?int
    {
        return \App\Models\User::whereKey($this->loyaltyPoint->user_id)->value('salon_id');
    }

    public function via(mixed $notifiable): array
    {
        return $this->gatedChannels(NotificationEvents::LOYALTY_POINTS_EARNED_CUSTOMER, ['database', 'sms'], $notifiable);
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

        return app(SMSService::class)->send($notifiable->phone, $message);
    }
}
