<?php

namespace Tests\Feature\Queue;

use App\Jobs\Send2faVerificationCodeJob;
use App\Jobs\SendLoginVerificationCodeJob;
use App\Jobs\SendPhoneVerificationCodeJob;
use App\Models\User;
use App\Services\PhoneVerificationService;
use App\Services\TwoFactorAuthService;
use App\Support\Queues;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * کد تأیید باید فوری برود: با worker دائمی روی صف otp (worker اختصاصی)؛ روی هاست بدون worker (QUEUE_WORK_VIA_SCHEDULER=true)
 * صف فقط هر دقیقه خالی می‌شود، پس کد بعد از فرستادن پاسخ در همان درخواست ارسال می‌شود، نه تا ۶۰ ثانیه بعد.
 */
class OtpDispatchTest extends TestCase
{
    use RefreshDatabase;

    private function sendAllCodes(): void
    {
        $user = User::factory()->create();
        app(PhoneVerificationService::class)->sendCode($user);
        app(PhoneVerificationService::class)->sendLoginCode($user);
        app(TwoFactorAuthService::class)->generateCode($user);
    }

    public function test_codes_go_to_the_otp_queue_when_a_worker_runs(): void
    {
        config(['queue.work_via_scheduler' => false]);
        Bus::fake();

        $this->sendAllCodes();

        foreach ([SendPhoneVerificationCodeJob::class, SendLoginVerificationCodeJob::class, Send2faVerificationCodeJob::class] as $job) {
            Bus::assertDispatched($job, fn ($j) => $j->queue === Queues::OTP);
            Bus::assertNotDispatchedAfterResponse($job);
        }
    }

    public function test_codes_are_sent_right_after_the_response_on_hosts_without_a_worker(): void
    {
        config(['queue.work_via_scheduler' => true]);
        Bus::fake();

        $this->sendAllCodes();

        foreach ([SendPhoneVerificationCodeJob::class, SendLoginVerificationCodeJob::class, Send2faVerificationCodeJob::class] as $job) {
            Bus::assertDispatchedAfterResponse($job);
        }
    }
}
