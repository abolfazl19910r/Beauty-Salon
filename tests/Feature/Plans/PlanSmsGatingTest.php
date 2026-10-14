<?php

namespace Tests\Feature\Plans;

use App\Jobs\SendBookingReminderJob;
use App\Jobs\SendLoginVerificationCodeJob;
use App\Models\Booking;
use App\Models\Plan;
use App\Models\Salon;
use App\Models\SalonSmsTemplate;
use App\Models\Specialist;
use App\Models\User;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\Sms\SmsQuotaService;
use App\Services\SMSService;
use App\Support\CurrentSalon;
use App\Support\Features\Feature;
use App\Support\Sms\SmsTemplates;
use App\Support\Sms\SmsTemplateStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * پیامک‌های سالن پشت امکان پلن (بسته‌ی پلن‌ها): پیامکی که پلن ندارد ساخته و شمرده نمی‌شود؛ کد تأیید هرگز بسته نمی‌شود.
 * SMSService واقعی (TestCase آن را mock می‌کند)؛ در محیط testing بدون تماس با کاوه‌نگار true برمی‌گرداند.
 */
class PlanSmsGatingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(SMSService::class, new SMSService);
    }

    private function salonOnPlan(array $features): Salon
    {
        $plan = Plan::create(['key' => 'p'.uniqid(), 'name' => 'آزمایشی', 'is_trial' => false, 'sms_quota_per_month' => 400, 'price_1m' => 1]);
        $plan->syncFeatures($features);

        return Salon::factory()->onPlan($plan)->create();
    }

    private function bookingIn(Salon $salon): Booking
    {
        return app(CurrentSalon::class)->withSalon($salon, function () use ($salon) {
            $user = User::factory()->create(['user_type' => 'customer', 'salon_id' => $salon->id, 'phone' => '09120000011']);
            $specialist = Specialist::factory()->create(['phone' => '09120000012']);

            return Booking::factory()->create(['user_id' => $user->id, 'specialist_id' => $specialist->id, 'status' => 'confirmed', 'booking_time' => now()->addHour()]);
        });
    }

    private function used(Salon $salon): int
    {
        return app(SmsQuotaService::class)->usedCount($salon);
    }

    public function test_a_plan_without_reminders_sends_no_reminder_sms_and_uses_no_quota(): void
    {
        $salon = $this->salonOnPlan([Feature::SmsCustomer, Feature::SmsStaff]);
        $booking = $this->bookingIn($salon);
        Log::spy();

        app(CurrentSalon::class)->withSalon($salon, fn () => (new SendBookingReminderJob($booking->id))->handle(app(SMSService::class), app(BookingRepositoryInterface::class)));

        $this->assertSame(0, $this->used($salon));
        Log::shouldHaveReceived('info')->withArgs(fn ($m, $c = []) => $m === 'SMS: feature_blocked' && ($c['feature'] ?? null) === 'sms_reminders')->twice();
        Log::shouldNotHaveReceived('error');
    }

    public function test_a_plan_with_reminders_sends_both_and_counts_them(): void
    {
        $salon = $this->salonOnPlan([Feature::SmsReminders]);
        $booking = $this->bookingIn($salon);

        app(CurrentSalon::class)->withSalon($salon, fn () => (new SendBookingReminderJob($booking->id))->handle(app(SMSService::class), app(BookingRepositoryInterface::class)));

        $this->assertGreaterThan(0, $this->used($salon));
    }

    public function test_customer_and_staff_sms_are_gated_separately(): void
    {
        $salon = $this->salonOnPlan([Feature::SmsStaff]);
        $sms = app(SMSService::class);

        $this->assertFalse($sms->send('09120000001', 'تأیید نوبت', $salon->id, Feature::SmsCustomer));
        $this->assertSame(0, $this->used($salon));

        $this->assertTrue($sms->send('09120000002', 'نوبت جدید', $salon->id, Feature::SmsStaff));
        $this->assertSame(1, $this->used($salon));
    }

    #[\PHPUnit\Framework\Attributes\TestWith([true])]
    #[\PHPUnit\Framework\Attributes\TestWith([false])]
    public function test_a_booking_status_notice_sends_sms_only_with_customer_sms_but_always_records_the_in_app_notice(bool $customerSms): void
    {
        $salon = $this->salonOnPlan($customerSms ? [Feature::SmsCustomer] : [Feature::SmsStaff]);
        $booking = $this->bookingIn($salon);

        app(CurrentSalon::class)->withSalon($salon, function () use ($booking) {
            $booking->user->notify(new \App\Notifications\Booking\BookingStatusUpdated($booking->fresh(), 'confirmed'));
        });

        $this->assertSame($customerSms, $this->used($salon) > 0);
        $this->assertTrue(\App\Models\UserNotification::withoutGlobalScopes()->where('notifiable_id', $booking->user_id)->exists());
    }

    public function test_verification_codes_are_never_gated_even_with_every_feature_off(): void
    {
        $salon = $this->salonOnPlan([]);
        $customer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $salon->id]);
        Log::spy();

        app()->call([new SendLoginVerificationCodeJob($customer->id, '123456'), 'handle']);

        Log::shouldNotHaveReceived('info', fn ($m) => $m === 'SMS: feature_blocked');
        $this->assertSame(1, app(SmsQuotaService::class)->otpCount($salon));
    }

    public function test_without_the_templates_feature_the_default_text_is_used_and_the_custom_one_is_kept(): void
    {
        $salon = $this->salonOnPlan([]);
        SmsTemplateStore::save($salon->id, 'reminder_customer', 'متن سالن {ساعت}');

        $this->assertSame(SmsTemplates::get('reminder_customer')['default'], SmsTemplateStore::body($salon->id, 'reminder_customer'));
        $this->assertTrue(SalonSmsTemplate::withoutGlobalScope('salon')->where('salon_id', $salon->id)->exists());

        $salon->featureOverrides()->create(['feature' => 'sms_templates', 'enabled' => true]);
        $this->assertSame('متن سالن {ساعت}', SmsTemplateStore::body($salon->id, 'reminder_customer'));
    }
}
