<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Invoice;
use App\Models\Salon;
use App\Support\CurrentSalon;
use App\Support\SuperAdmin\SalonStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * بسته‌ی ۱ ظاهری (۲۰۲۶-۱۱-۲۴): دسته‌های وضعیت سالن جدا از هم‌اند و SQL و PHP همیشه یک جواب می‌دهند.
 */
class SalonStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(CurrentSalon::class)->clear();
        $this->freezeTime();
    }

    /** @return array<string, Salon> */
    private function salons(): array
    {
        $paidTrial = Salon::factory()->create(['slug' => 'paid-in-trial', 'subscription_ends_at' => now()->addMonths(3), 'trial_ends_at' => now()->addDays(5)]);
        Invoice::factory()->paid()->create(['salon_id' => $paidTrial->id]);

        return [
            'active' => Salon::factory()->create(['slug' => 'a', 'subscription_ends_at' => now()->addMonths(2)]),
            'trial' => Salon::factory()->create(['slug' => 't', 'subscription_ends_at' => now()->addDays(3), 'trial_ends_at' => now()->addDays(3)]),
            'expiring_soon' => Salon::factory()->create(['slug' => 's', 'subscription_ends_at' => now()->addDays(7)]),
            'expired' => Salon::factory()->create(['slug' => 'e', 'subscription_ends_at' => now()->subMinute()]),
            'no_date' => Salon::factory()->create(['slug' => 'n', 'subscription_ends_at' => null]),
            'suspended' => Salon::factory()->create(['slug' => 'x', 'subscription_ends_at' => now()->subDay(), 'is_suspended' => true]),
            'paid_in_trial' => $paidTrial,
        ];
    }

    public function test_buckets_are_exclusive_and_sql_agrees_with_php(): void
    {
        $salons = $this->salons();

        $this->assertSame('active', SalonStatus::of($salons['active']));
        $this->assertSame('trial', SalonStatus::of($salons['trial']));
        $this->assertSame('expiring_soon', SalonStatus::of($salons['expiring_soon']));
        $this->assertSame('expired', SalonStatus::of($salons['expired']));
        $this->assertSame('expired', SalonStatus::of($salons['no_date']));
        $this->assertSame('suspended', SalonStatus::of($salons['suspended']));
        // خرید در دوره‌ی آزمایشی = دیگر آزمایشی نیست
        $this->assertSame('active', SalonStatus::of($salons['paid_in_trial']));

        $counts = SalonStatus::counts();
        $this->assertSame(Salon::query()->count(), $counts['total']);
        $this->assertSame($counts['total'], array_sum(array_intersect_key($counts, SalonStatus::LABELS)));

        foreach (Salon::query()->get() as $salon) {
            $bucket = SalonStatus::of($salon);
            $this->assertTrue(SalonStatus::scope(Salon::query(), $bucket)->whereKey($salon->id)->exists(), "{$salon->slug} not in {$bucket}");
        }
        foreach (SalonStatus::keys() as $key) {
            $this->assertSame($counts[$key], SalonStatus::scope(Salon::query(), $key)->count(), $key);
        }
    }

    public function test_paid_invoice_flag_avoids_a_query_per_row(): void
    {
        $this->salons();
        $loaded = Salon::query()->withPaidInvoiceFlag()->get();

        DB::enableQueryLog();
        $loaded->each(fn (Salon $s) => SalonStatus::of($s));
        $this->assertCount(0, DB::getQueryLog());
        $this->assertTrue($loaded->firstWhere('slug', 'paid-in-trial')->hasPaidInvoice());
    }

    public function test_remaining_labels(): void
    {
        $this->assertSame(['text' => '۷ روز مانده', 'urgent' => true], SalonStatus::remaining(now()->addDays(7)));
        $this->assertSame(['text' => '۳۰ روز مانده', 'urgent' => false], SalonStatus::remaining(now()->addDays(30)));
        $this->assertSame('۹ ماه مانده', SalonStatus::remaining(now()->addMonths(9)->addDay())['text']);
        $this->assertSame('۳ روز پیش تمام شد', SalonStatus::remaining(now()->subDays(3))['text']);
        $this->assertNull(SalonStatus::remaining(null));
    }
}
