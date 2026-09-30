<?php

namespace Tests\Feature;

use App\Jobs\Send2faVerificationCodeJob;
use App\Jobs\SendLoginVerificationCodeJob;
use App\Jobs\SendPhoneVerificationCodeJob;
use App\Models\Salon;
use App\Models\SalonSmsUsage;
use App\Models\User;
use App\Services\Sms\SmsQuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * پیامک‌های احراز هویت (کد ورود، ۲FA، تأیید تلفن) خرج پلتفرم‌اند: با تمام شدن سهمیه‌ی سالن قطع نمی‌شوند و از سقف کم
 * نمی‌شوند، ولی جدا شمرده می‌شوند تا مصرف غیرعادی یک سالن دیده شود.
 */
class SmsOtpOutsideQuotaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // SMSService واقعی (TestCase آن را mock می‌کند)؛ در محیط testing بدون تماس با کاوه‌نگار true برمی‌گرداند.
        $this->app->instance(\App\Services\SMSService::class, new \App\Services\SMSService);
    }

    private function exhaustedSalonCustomer(): array
    {
        $salon = Salon::factory()->create(['sms_quota_per_month' => 2]);
        SalonSmsUsage::create([
            'salon_id' => $salon->id,
            'period' => app(SmsQuotaService::class)->currentPeriod(),
            'used_count' => 2,
        ]);
        $customer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $salon->id]);

        return [$salon, $customer];
    }

    public static function otpJobs(): array
    {
        return [
            'login' => [SendLoginVerificationCodeJob::class],
            '2fa' => [Send2faVerificationCodeJob::class],
            'phone' => [SendPhoneVerificationCodeJob::class],
        ];
    }

    #[DataProvider('otpJobs')]
    public function test_an_otp_is_sent_even_when_the_salons_quota_is_exhausted_and_counted_separately(string $job): void
    {
        [$salon, $customer] = $this->exhaustedSalonCustomer();
        Log::spy();

        app()->call([new $job($customer->id, '123456'), 'handle']);

        Log::shouldNotHaveReceived('error');
        $usage = SalonSmsUsage::where('salon_id', $salon->id)->first();
        $this->assertSame(2, (int) $usage->used_count);
        $this->assertSame(1, (int) $usage->otp_count);
    }

    public function test_an_otp_does_not_use_up_the_quota(): void
    {
        $salon = Salon::factory()->create(['sms_quota_per_month' => 5]);
        $customer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $salon->id]);

        app()->call([new SendLoginVerificationCodeJob($customer->id, '123456'), 'handle']);

        $this->assertSame(5, app(SmsQuotaService::class)->remaining($salon));
        $this->assertSame(1, app(SmsQuotaService::class)->otpCount($salon));
    }

    public function test_the_billing_page_shows_quota_usage_and_otp_count_separately(): void
    {
        $salon = app(\App\Support\CurrentSalon::class)->get();
        SalonSmsUsage::create([
            'salon_id' => $salon->id,
            'period' => app(SmsQuotaService::class)->currentPeriod(),
            'used_count' => 37,
            'otp_count' => 412,
        ]);
        $owner = User::factory()->create(['is_admin' => true]);

        $this->actingAs($owner)->get('/admin/billing')
            ->assertOk()
            ->assertSee('پیامک‌های این ماه')
            ->assertSee(to_persian_num('37'))
            ->assertSee(to_persian_num('412'));
    }

    public function test_the_super_admin_sees_the_otp_count_of_a_salon(): void
    {
        [$salon] = $this->exhaustedSalonCustomer();
        SalonSmsUsage::where('salon_id', $salon->id)->update(['otp_count' => 987]);
        $superAdmin = User::factory()->create();
        $superAdmin->roles()->attach(\App\Models\Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین'])->id);

        $this->actingAs($superAdmin)->get(route('superadmin.salons.edit', $salon))
            ->assertOk()
            ->assertSee('کدهای تأیید')
            ->assertSee(to_persian_num('987'));
    }
}
