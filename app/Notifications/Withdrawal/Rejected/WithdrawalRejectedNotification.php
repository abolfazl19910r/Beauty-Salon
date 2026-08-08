<?php

namespace App\Notifications\Withdrawal\Rejected;

use App\Models\WithdrawalRequest;
use App\Services\SMSService;
use Illuminate\Notifications\Notification;

class WithdrawalRejectedNotification extends Notification
{
    private WithdrawalRequest $withdrawalRequest;

    private string $reason;

    private SMSService $smsService;

    public function __construct(WithdrawalRequest $withdrawalRequest, string $reason)
    {
        $this->withdrawalRequest = $withdrawalRequest;
        $this->reason = $reason;
        $this->smsService = new SMSService;
    }

    public function via(mixed $notifiable): array
    {
        return ['database', 'sms'];
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
        $message = sprintf(
            "همکار گرامی، درخواست برداشت شما به مبلغ %s تومان رد شد و مبلغ به کیف پول شما بازگشت.\nدلیل: %s",
            number_format($this->withdrawalRequest->amount),
            $this->reason,
        );

        return $this->smsService->send($notifiable->phone, $message);
    }
}
