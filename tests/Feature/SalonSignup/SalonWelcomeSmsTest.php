<?php

namespace Tests\Feature\SalonSignup;

use App\Models\Salon;
use App\Models\User;
use App\Notifications\Salon\SalonWelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\SalonContactPayload;
use Tests\TestCase;

/**
 * ⭐ پیامک خوش‌آمد به مالک سالن (۲۰۲۶-۰۹-۲۵).
 */
class SalonWelcomeSmsTest extends TestCase
{
    use RefreshDatabase;
    use SalonContactPayload;

    private function signUpAndVerify(?string $code = null): User
    {
        $this->post(route('salon-signup.store'), array_merge([
            'name' => 'سالن ماه',
            'slug' => 'mah-salon',
            'subscription_type' => '3m',
            'owner_name' => 'نگار رضایی',
            'owner_phone' => '09127778899',
            'owner_password' => 'Str0ng!Passw0rd',
            'owner_password_confirmation' => 'Str0ng!Passw0rd',
        ], $this->salonContactPayload()));

        $owner = User::where('phone', '09127778899')->firstOrFail();
        $this->post(route('salon-signup.verify.store'), ['code' => $code ?? $owner->fresh()->verification_code]);

        return $owner;
    }

    public function test_owner_gets_one_welcome_sms_with_the_booking_address_after_verifying(): void
    {
        config(['billing.trial_days' => 14]);
        Notification::fake();

        $owner = $this->signUpAndVerify();
        $salon = Salon::where('slug', 'mah-salon')->firstOrFail();

        Notification::assertSentToTimes($owner, SalonWelcomeNotification::class, 1);
        Notification::assertSentTo($owner, SalonWelcomeNotification::class, function (SalonWelcomeNotification $n, array $channels) use ($salon) {
            $text = $n->message();

            return $channels === ['sms']
                && str_contains($text, 'به ماهرو خوش آمدید')
                && str_contains($text, 'سالن ماه')
                && str_contains($text, $salon->publicUrl())
                && str_contains($text, '14 روزه');
        });
    }

    public function test_no_welcome_sms_before_the_phone_is_verified(): void
    {
        Notification::fake();

        $this->signUpAndVerify('000000');

        Notification::assertNothingSentTo(User::where('phone', '09127778899')->firstOrFail(), SalonWelcomeNotification::class);
    }

    public function test_the_welcome_sms_goes_out_even_with_an_exhausted_quota_and_does_not_consume_it(): void
    {
        $salon = Salon::factory()->create(['sms_quota_per_month' => 1]);
        \App\Models\SalonSmsUsage::create(['salon_id' => $salon->id, 'period' => now()->format('Y-m'), 'used_count' => 1]);
        $owner = User::factory()->create();
        $notification = new SalonWelcomeNotification($salon, 0);

        $this->assertTrue($notification->toSms($owner));
        $this->assertSame(1, (int) \App\Models\SalonSmsUsage::where('salon_id', $salon->id)->sum('used_count'));
        $this->assertStringNotContainsString('آزمایشی', $notification->message());
    }
}
