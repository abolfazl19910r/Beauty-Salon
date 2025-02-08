<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RewardRedeemed extends Notification
{
    use Queueable;

    protected $reward;
    protected $discountCode;

    public function __construct($reward, $discountCode)
    {
        $this->reward = $reward;
        $this->discountCode = $discountCode;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database', 'sms'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('دریافت پاداش جدید')
            ->line("پاداش {$this->reward->title} با موفقیت دریافت شد")
            ->line("کد تخفیف شما: {$this->discountCode->code}")
            ->line("مهلت استفاده از کد تخفیف تا: " . verta($this->discountCode->expires_at)->format('Y/m/d'))
            ->action('مشاهده پاداش‌ها', route('loyalty.rewards'));
    }

    public function toDatabase($notifiable): array
    {
        return [
            'reward_id' => $this->reward->id,
            'reward_title' => $this->reward->title,
            'discount_code' => $this->discountCode->code,
            'expires_at' => $this->discountCode->expires_at
        ];
    }

    public function toSms($notifiable): string
    {
        return "کد تخفیف {$this->discountCode->code} برای پاداش {$this->reward->title} صادر شد. مهلت استفاده تا " .
            verta($this->discountCode->expires_at)->format('Y/m/d');
    }
}
