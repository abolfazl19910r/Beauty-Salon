<?php

namespace Tests\Concerns;

use App\Jobs\SendRegistrationCodeJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;

/**
 * ثبت‌نام سالن از ۲۰۲۶-۱۰-۱۱ تا تأیید کد هیچ ردیفی نمی‌سازد (SalonSignupChallenge) و کد فقط هش‌شده در cache است؛ تست‌ها کد را
 * از کار صف‌شده‌ی SendRegistrationCodeJob می‌خوانند. Laravel این setUp را خودکار صدا می‌زند (setUp{نام trait}).
 */
trait CompletesSalonSignup
{
    protected function setUpCompletesSalonSignup(): void
    {
        Queue::fake([SendRegistrationCodeJob::class]);
    }

    protected function lastSignupCode(): string
    {
        $job = Queue::pushed(SendRegistrationCodeJob::class)->last();
        $this->assertNotNull($job, 'کد ثبت‌نام سالن فرستاده نشد');

        return $job->code;
    }

    protected function confirmSalonSignup(?string $code = null): TestResponse
    {
        return $this->post(route('salon-signup.verify.store'), ['code' => $code ?? $this->lastSignupCode()]);
    }
}
