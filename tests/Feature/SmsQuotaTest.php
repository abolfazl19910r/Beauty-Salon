<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Salon;
use App\Models\SalonSmsUsage;
use App\Models\User;
use App\Notifications\Sms\SmsQuotaExhaustedNotification;
use App\Services\Sms\SmsQuotaService;
use App\Services\SMSService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Kavenegar\KavenegarApi;
use ReflectionClass;
use Tests\TestCase;

/**
 * ⭐ فیچر «سقف/قطع پیامک ماهانه» (تصمیم صریح ابوالفضل، ۲۰۲۶-۰۹-۲۰، config/billing.php +
 * app/Services/Sms/SmsQuotaService.php + SMSService::send()/sendTemplate()'s new $salonId
 * پارامتر). این تست‌ها با یک Mockery spy جای کلاینت واقعی Kavenegar (همون الگوی
 * SmsServiceEnvironmentGuardTest) تأیید می‌کنن که بعد از اتمام سهمیه، API واقعی اصلاً صدا زده
 * نمی‌شه — نه فقط این‌که پاسخ false برمی‌گرده.
 */
class SmsQuotaTest extends TestCase
{
    use RefreshDatabase;

    private function serviceWithApiSpy(): array
    {
        $service = new SMSService;
        $apiSpy = \Mockery::mock(KavenegarApi::class);

        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('api');
        $property->setAccessible(true);
        $property->setValue($service, $apiSpy);

        return [$service, $apiSpy];
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.kavenegar.send_in_local' => true]); // force the real code path under the spy
    }

    public function test_send_is_allowed_and_usage_recorded_while_under_quota(): void
    {
        $salon = Salon::factory()->create(['sms_quota_per_month' => 5]);
        [$service, $apiSpy] = $this->serviceWithApiSpy();
        $apiSpy->shouldReceive('Send')->once()->andReturn(true);

        $result = $service->send('09121234567', 'test', $salon->id);

        $this->assertTrue($result);
        $this->assertSame(1, SalonSmsUsage::where('salon_id', $salon->id)->first()->used_count);
    }

    public function test_send_is_blocked_once_quota_is_exhausted_and_never_touches_the_real_api(): void
    {
        $salon = Salon::factory()->create(['sms_quota_per_month' => 2]);
        SalonSmsUsage::create([
            'salon_id' => $salon->id,
            'period' => app(SmsQuotaService::class)->currentPeriod(),
            'used_count' => 2,
        ]);

        [$service, $apiSpy] = $this->serviceWithApiSpy();
        $apiSpy->shouldNotReceive('Send');

        $result = $service->send('09121234567', 'test', $salon->id);

        $this->assertFalse($result);
    }

    public function test_send_template_is_also_gated_by_the_same_quota(): void
    {
        $salon = Salon::factory()->create(['sms_quota_per_month' => 1]);
        SalonSmsUsage::create([
            'salon_id' => $salon->id,
            'period' => app(SmsQuotaService::class)->currentPeriod(),
            'used_count' => 1,
        ]);

        [$service, $apiSpy] = $this->serviceWithApiSpy();
        $apiSpy->shouldNotReceive('VerifyLookup');

        $result = $service->sendTemplate('09121234567', 'login-verify', ['123456'], $salon->id);

        $this->assertFalse($result);
    }

    public function test_admins_and_super_admin_are_notified_exactly_once_when_quota_is_first_exhausted(): void
    {
        Notification::fake();

        $role = Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']);
        $superAdmin = User::factory()->create(['is_admin' => true]);
        $superAdmin->roles()->attach($role);

        $salon = Salon::factory()->create(['sms_quota_per_month' => 1]);
        $salonAdmin = User::factory()->create(['salon_id' => $salon->id]);
        $salon->admins()->attach($salonAdmin, ['role' => 'owner']);

        SalonSmsUsage::create([
            'salon_id' => $salon->id,
            'period' => app(SmsQuotaService::class)->currentPeriod(),
            'used_count' => 1, // already at quota
        ]);

        [$service] = $this->serviceWithApiSpy();

        // First blocked send → exactly one notification round, to both recipients.
        $service->send('09121234567', 'first blocked', $salon->id);

        Notification::assertSentTo($salonAdmin, SmsQuotaExhaustedNotification::class);
        Notification::assertSentTo($superAdmin, SmsQuotaExhaustedNotification::class);
        Notification::assertCount(2);

        // Second blocked send in the SAME period → must NOT notify again.
        $service->send('09121234567', 'second blocked', $salon->id);

        Notification::assertCount(2);
    }

    public function test_quota_resets_in_a_new_calendar_period(): void
    {
        $salon = Salon::factory()->create(['sms_quota_per_month' => 1]);
        SalonSmsUsage::create([
            'salon_id' => $salon->id,
            'period' => '2020-01', // an old, unrelated period
            'used_count' => 999,
        ]);

        [$service, $apiSpy] = $this->serviceWithApiSpy();
        $apiSpy->shouldReceive('Send')->once()->andReturn(true);

        $result = $service->send('09121234567', 'test', $salon->id);

        $this->assertTrue($result, 'مصرف یک ماه قبل نباید روی سهمیه‌ی ماه جاری اثر بگذارد');
    }

    public function test_per_salon_override_column_takes_precedence_over_the_platform_default(): void
    {
        config(['billing.sms_quota_per_month' => 1000]);
        $salon = Salon::factory()->create(['sms_quota_per_month' => 3]);

        $this->assertSame(3, app(SmsQuotaService::class)->quotaFor($salon));
    }

    public function test_null_salon_id_skips_quota_entirely_for_backward_compatibility(): void
    {
        [$service, $apiSpy] = $this->serviceWithApiSpy();
        $apiSpy->shouldReceive('Send')->once()->andReturn(true);

        $result = $service->send('09121234567', 'no salon context');

        $this->assertTrue($result);
        $this->assertSame(0, SalonSmsUsage::count());
    }

    public function test_nonexistent_salon_id_fails_open_and_still_sends(): void
    {
        [$service, $apiSpy] = $this->serviceWithApiSpy();
        $apiSpy->shouldReceive('Send')->once()->andReturn(true);

        $result = $service->send('09121234567', 'test', 999999);

        $this->assertTrue($result);
    }
}
