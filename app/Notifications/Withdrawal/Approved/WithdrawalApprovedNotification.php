<?php

namespace App\Notifications\Withdrawal\Approved;

use App\Models\WithdrawalRequest;
use App\Notifications\Concerns\QueuesOnlySmsChannel;
use App\Services\SMSService;
use App\Support\Notifications\NotificationEvents;
use App\Traits\RespectsNotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class WithdrawalApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable, QueuesOnlySmsChannel, RespectsNotificationSettings, SerializesModels;

    private WithdrawalRequest $withdrawalRequest;

    public function __construct(WithdrawalRequest $withdrawalRequest)
    {
        $this->withdrawalRequest = $withdrawalRequest;
    }

    protected function settingsSalonId(): ?int
    {
        return \App\Support\SalonOfNotifiable::ofSpecialist($this->withdrawalRequest->specialist_id);
    }

    public function via(mixed $notifiable): array
    {
        return $this->gatedChannels(NotificationEvents::WITHDRAWAL_APPROVED_SPECIALIST, ['database', 'sms'], $notifiable);
    }

    public function toDatabase(mixed $notifiable): array
    {
        return [
            'type' => 'withdrawal_approved',
            'withdrawal_request_id' => $this->withdrawalRequest->id,
            'message' => sprintf(
                'درخواست برداشت %s تومان شما تایید و پرداخت شد.',
                number_format($this->withdrawalRequest->net_amount),
            ),
        ];
    }

    public function toSms(mixed $notifiable): bool
    {
        $message = sprintf(
            "همکار گرامی، درخواست برداشت شما به مبلغ %s تومان تایید و به حساب شما واریز شد.\n🔢 کد پیگیری: %s",
            number_format($this->withdrawalRequest->net_amount),
            $this->withdrawalRequest->reference_code,
        );

        return app(SMSService::class)->send($notifiable->phone, $message);
    }
}
