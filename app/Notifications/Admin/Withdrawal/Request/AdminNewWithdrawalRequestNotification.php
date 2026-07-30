<?php

namespace App\Notifications\Admin\Withdrawal\Request;

use App\Models\WithdrawalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AdminNewWithdrawalRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly WithdrawalRequest $withdrawalRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $specialistName = $this->withdrawalRequest->specialist->name ?? 'نامشخص';
        $amount = number_format($this->withdrawalRequest->amount);

        return [
            'type' => 'new_withdrawal_request_admin',
            'withdrawal_request_id' => $this->withdrawalRequest->id,
            'message' => "درخواست برداشت جدید: {$amount} تومان توسط {$specialistName}",
            'link' => route('admin.wallet.withdrawals.show', $this->withdrawalRequest->id, false),
        ];
    }
}
