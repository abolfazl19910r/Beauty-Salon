<?php

namespace App\Notifications\Withdrawal\Rejected;

use App\Models\WithdrawalRequest;
use App\Notifications\Concerns\QueuesOnlySmsChannel;
use App\Services\SMSService;
use App\Support\Notifications\NotificationEvents;
use App\Traits\RespectsNotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class WithdrawalRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable, QueuesOnlySmsChannel, RespectsNotificationSettings, SerializesModels;

    private WithdrawalRequest $withdrawalRequest;

    private string $reason;

    public function __construct(WithdrawalRequest $withdrawalRequest, string $reason)
    {
        $this->withdrawalRequest = $withdrawalRequest;
        $this->reason = $reason;
    }

    protected function settingsSalonId(): ?int
    {
        return \App\Support\SalonOfNotifiable::ofSpecialist($this->withdrawalRequest->specialist_id);
    }

    public function via(mixed $notifiable): array
    {
        return $this->gatedChannels(NotificationEvents::WITHDRAWAL_REJECTED_SPECIALIST, ['database', 'sms'], $notifiable);
    }

    public function toDatabase(mixed $notifiable): array
    {
        return [
            'type' => 'withdrawal_rejected',
            'withdrawal_request_id' => $this->withdrawalRequest->id,
            'message' => sprintf(
                'درخواست برداشت %s تومان شما رد شد. مبلغ به کیف پول شما بازگشت. دلیل: %s',
                number_format($this->withdrawalRequest->amount),
                $this->reason,
            ),
        ];
    }

    public function toSms(mixed $notifiable): bool
    {
        $message = \App\Support\Sms\SmsText::withdrawalRejected((float) $this->withdrawalRequest->amount, $this->reason);

        return app(SMSService::class)->send($notifiable->phone, $message, $this->settingsSalonId());
    }
}
