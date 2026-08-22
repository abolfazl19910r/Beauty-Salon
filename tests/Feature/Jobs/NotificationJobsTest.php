<?php

namespace Tests\Feature\Jobs;

use App\Jobs\Send2faVerificationCodeJob;
use App\Jobs\SendBookingReminderJob;
use App\Jobs\SendLoginVerificationCodeJob;
use App\Jobs\SendPhoneVerificationCodeJob;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use App\Services\SMSService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class NotificationJobsTest extends TestCase
{
    use RefreshDatabase;

    // ── SendBookingReminderJob ───────────────────────────────────────────

    public function test_reminder_job_sends_a_message_to_both_customer_and_specialist(): void
    {
        $user = User::factory()->create(['phone' => '09120000001', 'name' => 'مشتری تست']);
        $specialist = Specialist::factory()->create(['phone' => '09120000002', 'name' => 'متخصص تست']);
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'specialist_id' => $specialist->id,
        ]);

        $sentTo = [];
        $this->mock(SMSService::class, function ($mock) use (&$sentTo) {
            $mock->shouldReceive('send')
                ->twice()
                ->andReturnUsing(function ($phone, $message) use (&$sentTo) {
                    $sentTo[] = $phone;

                    return true;
                });
        });

        (new SendBookingReminderJob($booking->id))->handle(app(SMSService::class));

        $this->assertContains($user->phone, $sentTo);
        $this->assertContains($specialist->phone, $sentTo);
    }

    public function test_reminder_job_skips_silently_when_booking_no_longer_exists(): void
    {
        $this->mock(SMSService::class, function ($mock) {
            $mock->shouldNotReceive('send');
        });

        (new SendBookingReminderJob(999999))->handle(app(SMSService::class));

        $this->assertTrue(true); // no exception thrown = pass
    }

    public function test_reminder_job_logs_an_error_when_one_of_the_two_sms_fails_but_does_not_throw(): void
    {
        $user = User::factory()->create();
        $specialist = Specialist::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'specialist_id' => $specialist->id,
        ]);

        $this->mock(SMSService::class, function ($mock) {
            $mock->shouldReceive('send')->twice()->andReturn(true, false);
        });

        Log::shouldReceive('error')->once()->withArgs(fn ($message) => str_contains($message, 'یکی از دو پیامک'));
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        (new SendBookingReminderJob($booking->id))->handle(app(SMSService::class));
    }

    // ── Send2faVerificationCodeJob / SendLoginVerificationCodeJob ───────

    public function test_2fa_job_sends_the_code_as_the_template_token(): void
    {
        $user = User::factory()->create();

        $this->mock(SMSService::class, function ($mock) use ($user) {
            $mock->shouldReceive('sendTemplate')
                ->once()
                ->withArgs(fn ($phone, $template, $tokens) => $phone === $user->phone && $tokens === ['482913'])
                ->andReturn(true);
        });

        (new Send2faVerificationCodeJob($user->id, '482913'))->handle(app(SMSService::class));
    }

    public function test_2fa_job_skips_silently_for_a_deleted_user(): void
    {
        $this->mock(SMSService::class, function ($mock) {
            $mock->shouldNotReceive('sendTemplate');
        });

        (new Send2faVerificationCodeJob(999999, '123456'))->handle(app(SMSService::class));

        $this->assertTrue(true);
    }

    public function test_login_job_sends_the_code_as_the_template_token(): void
    {
        $user = User::factory()->create();

        $this->mock(SMSService::class, function ($mock) use ($user) {
            $mock->shouldReceive('sendTemplate')
                ->once()
                ->withArgs(fn ($phone, $template, $tokens) => $phone === $user->phone && $tokens === ['771122'])
                ->andReturn(true);
        });

        (new SendLoginVerificationCodeJob($user->id, '771122'))->handle(app(SMSService::class));
    }

    // ── SendPhoneVerificationCodeJob ─────────────────────────────────────
    // ⭐ Fix (test-writing session 10): PhoneVerificationService::sendCode() (used by
    // both registration and the post-auth phone-verification-notice flow) used to send
    // its SMS synchronously — the same bug class already fixed for login, now fixed
    // here too via this job.

    public function test_phone_verification_job_sends_the_code_as_the_template_token(): void
    {
        $user = User::factory()->create();

        $this->mock(SMSService::class, function ($mock) use ($user) {
            $mock->shouldReceive('sendTemplate')
                ->once()
                ->withArgs(fn ($phone, $template, $tokens) => $phone === $user->phone && $tokens === ['556677'])
                ->andReturn(true);
        });

        (new SendPhoneVerificationCodeJob($user->id, '556677'))->handle(app(SMSService::class));
    }

    public function test_phone_verification_job_skips_silently_for_a_deleted_user(): void
    {
        $this->mock(SMSService::class, function ($mock) {
            $mock->shouldNotReceive('sendTemplate');
        });

        (new SendPhoneVerificationCodeJob(999999, '123456'))->handle(app(SMSService::class));

        $this->assertTrue(true); // no exception thrown = pass
    }
}
