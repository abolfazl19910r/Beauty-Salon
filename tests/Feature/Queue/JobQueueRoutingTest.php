<?php

namespace Tests\Feature\Queue;

use App\Jobs\CancelUnpaidBookings;
use App\Jobs\GeneratePdfReportJob;
use App\Jobs\ProcessWithdrawalJob;
use App\Jobs\Send2faVerificationCodeJob;
use App\Jobs\SendBookingReminderJob;
use App\Jobs\SendBulkNotificationJob;
use App\Jobs\SendLoginVerificationCodeJob;
use App\Jobs\SendPasswordResetCodeJob;
use App\Jobs\SendPhoneVerificationCodeJob;
use App\Support\Queues;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * صف‌های جدا (تصمیم ۲۰۲۶-۰۹-۳۰): کد تأیید در otp (worker اختصاصی)، بقیه‌ی پیامک‌ها در sms، پول در payments، گزارش در reports.
 */
class JobQueueRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_job_lands_on_its_queue(): void
    {
        Queue::fake();

        SendLoginVerificationCodeJob::dispatch(1, '1234');
        Send2faVerificationCodeJob::dispatch(1, '1234');
        SendPhoneVerificationCodeJob::dispatch(1, '1234');
        SendPasswordResetCodeJob::dispatch(1, '1234', null);
        SendBookingReminderJob::dispatch(1);
        ProcessWithdrawalJob::dispatch(1);
        CancelUnpaidBookings::dispatch();
        GeneratePdfReportJob::dispatch(1);
        SendBulkNotificationJob::dispatch(\App\Notifications\User\NewUserRegisteredNotification::class, [], \App\Models\User::class, [1]);

        Queue::assertPushedOn(Queues::OTP, SendLoginVerificationCodeJob::class);
        Queue::assertPushedOn(Queues::OTP, Send2faVerificationCodeJob::class);
        Queue::assertPushedOn(Queues::OTP, SendPhoneVerificationCodeJob::class);
        Queue::assertPushedOn(Queues::OTP, SendPasswordResetCodeJob::class);
        Queue::assertPushedOn(Queues::SMS, SendBookingReminderJob::class);
        Queue::assertPushedOn(Queues::PAYMENTS, ProcessWithdrawalJob::class);
        Queue::assertPushedOn(Queues::PAYMENTS, CancelUnpaidBookings::class);
        Queue::assertPushedOn(Queues::REPORTS, GeneratePdfReportJob::class);
        Queue::assertPushed(SendBulkNotificationJob::class, fn ($job) => $job->queue === null);
    }

    public function test_the_scheduled_unpaid_cancellation_goes_to_the_payments_queue(): void
    {
        Queue::fake();
        $this->artisan('schedule:list')->assertSuccessful(); // routes/console.php (Schedule::job) بار شود

        $event = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->first(fn ($e) => $e->description === 'cancel-unpaid-bookings');
        $this->assertNotNull($event);
        $event->run($this->app);

        Queue::assertPushedOn(Queues::PAYMENTS, CancelUnpaidBookings::class);
    }

    public function test_worker_order_puts_codes_first_and_reports_last(): void
    {
        $this->assertSame('otp,sms,payments,default,reports', Queues::workerOrder());
    }
}
