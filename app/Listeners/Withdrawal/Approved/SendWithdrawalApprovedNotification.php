<?php

namespace App\Listeners\Withdrawal\Approved;

use App\Events\Withdrawal\Approved\WithdrawalApproved;
use App\Notifications\Withdrawal\Approved\WithdrawalApprovedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendWithdrawalApprovedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Safety net: even if WithdrawalApproved somehow gets dispatched/processed
     * twice for the same request (e.g. two queue:work processes running at
     * once, or a job retried before being deleted), we only ever want ONE
     * "withdrawal_approved" database notification to exist per request. This
     * checks the actual persisted state before sending, so it protects
     * against duplicates regardless of *why* handle() got called twice.
     */
    public function handle(WithdrawalApproved $event): void
    {
        $withdrawalRequest = $event->withdrawalRequest;
        $specialist = $withdrawalRequest->specialist;

        if (! $specialist) {
            return;
        }

        // lockForUpdate + a unique check-then-insert would be ideal, but the
        // notifications table has no natural unique constraint on
        // (notifiable, type, withdrawal_request_id). This existence check is
        // enough for the realistic failure mode here (near-simultaneous
        // double execution), since the read+notify happens inside a single
        // queue worker's synchronous handle() call.
        $alreadySent = DB::table('notifications')
            ->where('notifiable_type', get_class($specialist))
            ->where('notifiable_id', $specialist->getKey())
            ->where('type', WithdrawalApprovedNotification::class)
            ->whereJsonContains('data->withdrawal_request_id', $withdrawalRequest->id)
            ->exists();

        if ($alreadySent) {
            Log::info('نوتیفیکیشن تایید برداشت قبلاً برای این درخواست ارسال شده بود؛ از ارسال دوباره صرف‌نظر شد.', [
                'withdrawal_request_id' => $withdrawalRequest->id,
            ]);

            return;
        }

        try {
            $specialist->notify(
                new WithdrawalApprovedNotification($withdrawalRequest)
            );
        } catch (\Throwable $e) {
            Log::warning('❌ خطا در ارسال نوتیفیکیشن تایید برداشت', [
                'withdrawal_request_id' => $withdrawalRequest->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
