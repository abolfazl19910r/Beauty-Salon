<?php

namespace App\Notifications\Withdrawal\Approved;

use App\Models\WithdrawalRequest;
use App\Services\SMSService;
use Illuminate\Notifications\Notification;

class WithdrawalApprovedNotification extends Notification
{
    private WithdrawalRequest $withdrawalRequest;
    private SMSService $smsService;

    public function __construct(WithdrawalRequest $withdrawalRequest)
    {
        $this->withdrawalRequest = $withdrawalRequest;
        $this->smsService = new SMSService();
    }

    public function via(mixed $notifiable): array
    {
        return ['database', 'sms'];
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

        return $this->smsService->send($notifiable->phone, $message);
    }
}
