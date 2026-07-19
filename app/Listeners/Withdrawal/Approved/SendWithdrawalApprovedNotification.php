<?php

namespace App\Listeners\Withdrawal\Approved;

use App\Events\Withdrawal\Approved\WithdrawalApproved;
use App\Notifications\Withdrawal\Approved\WithdrawalApprovedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendWithdrawalApprovedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(WithdrawalApproved $event): void
    {
        try {
            $event->withdrawalRequest->specialist?->notify(
                new WithdrawalApprovedNotification($event->withdrawalRequest)
            );
        } catch (\Throwable $e) {
            Log::warning('❌ خطا در ارسال نوتیفیکیشن تایید برداشت', [
                'withdrawal_request_id' => $event->withdrawalRequest->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
