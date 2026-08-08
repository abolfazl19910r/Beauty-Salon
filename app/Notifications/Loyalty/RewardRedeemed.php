<?php

namespace App\Notifications\Loyalty;

use App\Services\SMSService;
use Illuminate\Notifications\Notification;

class RewardRedeemed extends Notification
{
    private mixed $reward;

    private mixed $discountCode;

    private SMSService $smsService;

    /**
     * @return void
     */
    public function __construct(mixed $reward, mixed $discountCode)
    {
        $this->reward = $reward;
        $this->discountCode = $discountCode;
        $this->smsService = new SMSService;
    }

    public function via(mixed $notifiable): array
    {
        return ['database', 'sms'];
    }

    public function toDatabase(mixed $notifiable): array
    {
        return [
            'reward_id' => $this->reward->id,
            'reward_title' => $this->reward->title,
            'discount_code' => $this->discountCode->code,
            'expires_at' => $this->discountCode->expires_at,
        ];
    }

    public function toSms(mixed $notifiable): bool
    {
        $message = sprintf(
            'کد تخفیف %s برای پاداش %s صادر شد. مهلت استفاده تا %s',
            $this->discountCode->code,
            $this->reward->title,
            verta($this->discountCode->expires_at)->format('Y/m/d')
        );

        return $this->smsService->send($notifiable->phone, $message);
    }
}
