<?php

namespace App\Listeners\Withdrawal\Rejected;

use App\Events\Withdrawal\Rejected\WithdrawalRejected;
use App\Notifications\Withdrawal\Rejected\WithdrawalRejectedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendWithdrawalRejectedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(WithdrawalRejected $event): void
    {
        try {
            $event->withdrawalRequest->specialist?->notify(
                new WithdrawalRejectedNotification($event->withdrawalRequest, $event->reason)
            );
        } catch (\Throwable $e) {
            Log::warning('❌ خطا در ارسال نوتیفیکیشن رد برداشت', [
                'withdrawal_request_id' => $event->withdrawalRequest->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
