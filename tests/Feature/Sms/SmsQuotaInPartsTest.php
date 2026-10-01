<?php

namespace Tests\Feature\Sms;

use App\Models\Salon;
use App\Models\SalonSmsUsage;
use App\Services\Sms\SmsQuotaService;
use App\Services\SMSService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * تصمیم ۲۰۲۶-۰۹-۳۰: سهمیه بر اساس «قطعه» شمرده می‌شود، همان‌طور که کاوه‌نگار فاکتور می‌کند. قبلاً هر ارسال یک واحد بود، چه
 * یک قطعه چه هفت قطعه.
 */
class SmsQuotaInPartsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(SMSService::class, new SMSService);
    }

    private function usage(Salon $salon): int
    {
        return (int) SalonSmsUsage::where('salon_id', $salon->id)->value('used_count');
    }

    public function test_a_long_persian_sms_uses_as_many_units_as_it_has_parts(): void
    {
        $salon = Salon::factory()->create(['sms_quota_per_month' => 100]);

        app(SMSService::class)->send('09121234567', str_repeat('س', 140), $salon->id); // ۳ قطعه
        app(SMSService::class)->send('09121234567', 'نوبت شما تایید شد', $salon->id);   // ۱ قطعه

        $this->assertSame(4, $this->usage($salon));
    }

    public function test_an_sms_larger_than_what_is_left_is_blocked_whole(): void
    {
        $salon = Salon::factory()->create(['sms_quota_per_month' => 10]);
        SalonSmsUsage::create(['salon_id' => $salon->id, 'period' => app(SmsQuotaService::class)->currentPeriod(), 'used_count' => 9]);

        $this->assertFalse(app(SMSService::class)->send('09121234567', str_repeat('س', 100), $salon->id)); // ۲ قطعه، ۱ مانده
        $this->assertSame(9, $this->usage($salon));
        $this->assertTrue(app(SMSService::class)->send('09121234567', 'کوتاه', $salon->id));
        $this->assertSame(10, $this->usage($salon));
    }

    public function test_the_default_quotas_are_in_parts(): void
    {
        $this->assertSame(2000, (int) config('billing.sms_quota_per_month'));
        $this->assertSame(400, (int) config('billing.trial_sms_quota'));
    }

    public function test_the_migration_keeps_each_salons_used_fraction_of_the_month(): void
    {
        $period = app(SmsQuotaService::class)->currentPeriod();
        $normal = Salon::factory()->create(['sms_quota_per_month' => null]);
        $trial = Salon::factory()->create(['sms_quota_per_month' => 300]);
        SalonSmsUsage::create(['salon_id' => $normal->id, 'period' => $period, 'used_count' => 750]);
        SalonSmsUsage::create(['salon_id' => $trial->id, 'period' => $period, 'used_count' => 300]);
        SalonSmsUsage::create(['salon_id' => $normal->id, 'period' => '2020-01', 'used_count' => 1500]);

        $migration = require database_path('migrations/2026_10_01_000001_convert_sms_quota_to_parts.php');
        $migration->up();

        $this->assertSame(1000, (int) SalonSmsUsage::where('salon_id', $normal->id)->where('period', $period)->value('used_count'));
        $this->assertSame(400, (int) SalonSmsUsage::where('salon_id', $trial->id)->where('period', $period)->value('used_count'));
        $this->assertSame(1500, (int) SalonSmsUsage::where('salon_id', $normal->id)->where('period', '2020-01')->value('used_count'));
        $this->assertNull($normal->fresh()->sms_quota_per_month);
        $this->assertSame(400, (int) $trial->fresh()->sms_quota_per_month);

        $migration->down();
        $this->assertSame(750, (int) SalonSmsUsage::where('salon_id', $normal->id)->where('period', $period)->value('used_count'));
        $this->assertSame(300, (int) DB::table('salons')->where('id', $trial->id)->value('sms_quota_per_month'));
    }
}
