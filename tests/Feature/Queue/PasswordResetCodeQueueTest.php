<?php

namespace Tests\Feature\Queue;

use App\Jobs\SendPasswordResetCodeJob;
use App\Models\Salon;
use App\Models\User;
use App\Services\SMSService;
use App\Support\Queues;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * کد بازیابی رمز (کارمند و مشتری) مثل سه کد تأیید دیگر: job صف otp؛ روی هاست بدون worker بعد از پاسخ.
 * پیش از این تغییر، کاوه‌نگار داخل خود درخواست صدا زده می‌شد.
 */
class PasswordResetCodeQueueTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    private User $staff;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salon = Salon::factory()->create(['slug' => 'reset-queue']);
        $this->staff = User::factory()->create(['user_type' => 'staff', 'salon_id' => null]);
        DB::table('salon_admins')->insert(['salon_id' => $this->salon->id, 'user_id' => $this->staff->id, 'role' => 'owner', 'created_at' => now(), 'updated_at' => now()]);
        $this->customer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $this->salon->id]);
    }

    private function requestBothCodes(): void
    {
        $this->post('/forgot-password', ['phone' => $this->staff->phone])->assertRedirect()->assertSessionHasNoErrors();
        $this->post('/s/'.$this->salon->slug.'/forgot-password', ['phone' => $this->customer->phone])->assertRedirect()->assertSessionHasNoErrors();
    }

    public function test_the_code_is_not_sent_inside_the_request_when_the_queue_is_async(): void
    {
        config(['queue.default' => 'database', 'queue.work_via_scheduler' => false]);
        $this->mock(SMSService::class, fn ($m) => $m->shouldNotReceive('sendAuthTemplate'));

        $this->requestBothCodes();

        $this->assertSame(2, DB::table('jobs')->where('queue', Queues::OTP)->count());
    }

    public function test_both_codes_go_to_the_otp_queue_with_the_salon_resolved_at_request_time(): void
    {
        config(['queue.work_via_scheduler' => false]);
        Bus::fake();

        $this->requestBothCodes();

        Bus::assertDispatchedTimes(SendPasswordResetCodeJob::class, 2);
        Bus::assertDispatched(SendPasswordResetCodeJob::class, fn ($j) => $j->queue === Queues::OTP && $j->salonId() === $this->salon->id);
        Bus::assertNotDispatchedAfterResponse(SendPasswordResetCodeJob::class);
    }

    public function test_the_code_is_sent_right_after_the_response_on_hosts_without_a_worker(): void
    {
        config(['queue.work_via_scheduler' => true]);
        Bus::fake();

        $this->requestBothCodes();

        Bus::assertDispatchedAfterResponseTimes(SendPasswordResetCodeJob::class, 2);
    }

    public function test_the_job_sends_the_stored_code_as_a_counted_verification_code(): void
    {
        $sent = [];
        $this->mock(SMSService::class, function ($m) use (&$sent) {
            $m->shouldReceive('sendAuthTemplate')->andReturnUsing(function ($phone, $template, $tokens, $salonId) use (&$sent) {
                $sent[] = [$phone, $tokens[0], $salonId];

                return true;
            });
        });

        $this->requestBothCodes(); // صف sync در تست: job همین‌جا اجرا می‌شود

        $this->assertSame([
            [$this->staff->phone, (string) $this->staff->fresh()->verification_code, $this->salon->id],
            [$this->customer->phone, (string) $this->customer->fresh()->verification_code, $this->salon->id],
        ], $sent);
    }

    public function test_a_deleted_user_is_skipped_without_sending(): void
    {
        $this->mock(SMSService::class, fn ($m) => $m->shouldNotReceive('sendAuthTemplate'));

        (new SendPasswordResetCodeJob(999999, '123456', $this->salon->id))->handle(app(SMSService::class), app(\App\Repositories\Contracts\UserRepositoryInterface::class));

        $this->assertTrue(true);
    }
}
