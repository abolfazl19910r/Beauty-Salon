<?php

namespace Tests\Feature\Sms;

use App\Models\Salon;
use App\Models\SalonSmsUsage;
use App\Models\User;
use App\Notifications\Admin\Attention\AttentionRequiredNotification;
use App\Notifications\Salon\SalonWelcomeNotification;
use App\Notifications\Sms\SmsQuotaExhaustedNotification;
use App\Services\Sms\SmsQuotaService;
use App\Services\SMSService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * تصمیم‌های ۲۰۲۶-۰۹-۳۰ (ادامه ۹) درباره‌ی پیامک‌هایی که قاعده‌ی کلی «از سهمیه‌ی سالن» برایشان مناسب نیست:
 * - «سهمیه تمام شد» و خوش‌آمد سالن جدید: خرج پلتفرم (از سهمیه کم نمی‌شوند، با سهمیه‌ی تمام‌شده هم می‌روند).
 * - هشدار «نیاز به بررسی»: از سهمیه‌ی سالن کم می‌شود ولی با سهمیه‌ی تمام‌شده هم می‌رود (پول گیر کرده).
 * - کد بازیابی رمز: مثل بقیه‌ی کدهای تأیید، خرج پلتفرم و در otp_count شمرده می‌شود.
 * همه با سالنی که سهمیه‌اش تمام شده (۲ از ۲).
 */
class SmsCostRulesTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    protected function setUp(): void
    {
        parent::setUp();
        // SMSService واقعی (TestCase آن را mock می‌کند)؛ در محیط testing بدون تماس با کاوه‌نگار true برمی‌گرداند.
        $this->app->instance(SMSService::class, new SMSService);
        $this->salon = Salon::factory()->create(['sms_quota_per_month' => 2, 'slug' => 'cost-rules']);
        SalonSmsUsage::create(['salon_id' => $this->salon->id, 'period' => app(SmsQuotaService::class)->currentPeriod(), 'used_count' => 2]);
    }

    private function usage(): SalonSmsUsage
    {
        return SalonSmsUsage::where('salon_id', $this->salon->id)->first();
    }

    public function test_the_quota_exhausted_notice_and_the_welcome_are_platform_cost(): void
    {
        $admin = User::factory()->create();

        $this->assertTrue((new SmsQuotaExhaustedNotification($this->salon, 2))->toSms($admin), 'quota-exhausted notice was blocked');
        $this->assertTrue((new SalonWelcomeNotification($this->salon, 14))->toSms($admin), 'welcome SMS was blocked');

        $this->assertSame(2, (int) $this->usage()->used_count);
    }

    public function test_the_attention_alert_is_charged_to_the_salon_but_never_blocked(): void
    {
        $admin = User::factory()->create();

        $alert = new AttentionRequiredNotification('payment', 1, $this->salon->id);
        $sent = $alert->toSms($admin);

        $this->assertTrue($sent, 'attention alert was blocked by the exhausted quota');
        // سهمیه بر حسب قطعه است: همه‌ی قطعه‌های هشدار، بالای سقف
        $this->assertSame(2 + \App\Support\SmsParts::count($alert->smsText()), (int) $this->usage()->used_count);
    }

    public function test_the_staff_password_reset_code_is_a_counted_verification_code(): void
    {
        $staff = User::factory()->create(['user_type' => 'staff', 'salon_id' => null]);
        \Illuminate\Support\Facades\DB::table('salon_admins')->insert(['salon_id' => $this->salon->id, 'user_id' => $staff->id, 'role' => 'owner', 'created_at' => now(), 'updated_at' => now()]);

        $this->post('/forgot-password', ['phone' => $staff->phone])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(2, (int) $this->usage()->used_count);
        $this->assertSame(1, (int) $this->usage()->otp_count);
    }

    public function test_the_customer_password_reset_code_is_a_counted_verification_code(): void
    {
        $customer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $this->salon->id]);

        $this->post('/s/'.$this->salon->slug.'/forgot-password', ['phone' => $customer->phone])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(2, (int) $this->usage()->used_count);
        $this->assertSame(1, (int) $this->usage()->otp_count);
    }
}
