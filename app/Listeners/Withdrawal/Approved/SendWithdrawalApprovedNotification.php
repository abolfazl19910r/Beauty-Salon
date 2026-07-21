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

    /**
     * Safety net: even if WithdrawalApproved somehow gets dispatched/processed
     * twice for the same request (e.g. two queue:work processes running at
     * once, or a job retried before being deleted), we only ever want ONE
     * "withdrawal_approved" database notification to exist per request. This
     * checks the actual persisted state before sending, so it protects
     * against duplicates regardless of *why* handle() got called twice.
     *
     * NOTE: this project's notifications table is `user_notifications` (see
     * App\Models\UserNotification + the
     * 2024_01_28_173700_create_user_notifications_table migration), NOT the
     * Laravel-default `notifications` table. A previous version of this
     * guard queried DB::table('notifications') directly and crashed every
     * time with "Base table or view not found: 1146" because that table has
     * never existed in this project. Using the specialist->notifications()
     * Eloquent relation avoids hardcoding the table name.
     */
    public function handle(WithdrawalApproved $event): void
    {
        $withdrawalRequest = $event->withdrawalRequest;
        $specialist = $withdrawalRequest->specialist;

        if (! $specialist) {
            return;
        }

        try {
            $alreadySent = $specialist->notifications()
                ->where('type', WithdrawalApprovedNotification::class)
                ->where('data->withdrawal_request_id', $withdrawalRequest->id)
                ->exists();

            if ($alreadySent) {
                Log::info('نوتیفیکیشن تایید برداشت قبلاً برای این درخواست ارسال شده بود؛ از ارسال دوباره صرف‌نظر شد.', [
                    'withdrawal_request_id' => $withdrawalRequest->id,
                ]);

                return;
            }

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
