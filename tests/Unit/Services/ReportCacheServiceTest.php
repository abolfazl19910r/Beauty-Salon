<?php

namespace Tests\Unit\Services;

use App\Models\Salon;
use App\Services\ReportCacheService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * ⭐ Fix (preventive، ۲۰۲۶-۰۹-۱۹ — پیگیری محور «۳»): این تست قفل می‌کنه که کلید کش تولیدشده
 * توسط این سرویس شامل شناسه‌ی سالن جاری باشه — دقیقاً همون الگوی نشتی که در HomeController
 * پیدا و فیکس شد (کلید کش مشترک بین همه‌ی سالن‌ها)، اینجا preventive فیکس شد چون remember()/
 * put() این کلاس فعلاً هیچ‌جای کدبیس واقعاً صدا زده نمی‌شن.
 */
class ReportCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cache_keys_are_scoped_per_salon(): void
    {
        Cache::flush();
        $service = app(ReportCacheService::class);

        $salonA = Salon::factory()->make(['id' => 1001]);
        $salonB = Salon::factory()->make(['id' => 1002]);

        app(CurrentSalon::class)->set($salonA);
        $service->put('some_report', 'value-for-a');

        app(CurrentSalon::class)->set($salonB);
        $service->put('some_report', 'value-for-b');

        app(CurrentSalon::class)->set($salonA);
        $this->assertSame('value-for-a', $service->get('some_report'));

        app(CurrentSalon::class)->set($salonB);
        $this->assertSame('value-for-b', $service->get('some_report'));
    }

    /**
     * flush() روی file و database (storeهای بدون تگ) قبلاً Error می‌داد و تأیید/لغو نوبت
     * را با ۵۰۰ می‌شکست. حالا باید روی هر store کار کند و واقعاً گزارش‌ها را باطل کند.
     */
    public static function untaggedStores(): array
    {
        return [['file'], ['database'], ['array']];
    }

    #[DataProvider('untaggedStores')]
    public function test_flush_invalidates_reports_on_every_store(string $store): void
    {
        config(['cache.default' => $store]);
        Cache::store($store)->flush();
        $service = app(ReportCacheService::class);
        app(CurrentSalon::class)->set(Salon::factory()->make(['id' => 1003]));

        $service->put('daily', 'old');
        $this->assertSame('old', $service->get('daily'));

        $this->assertTrue($service->flush());

        $this->assertNull($service->get('daily'));
        $this->assertSame('fresh', $service->remember('daily', fn () => 'fresh'));
        $this->assertSame('fresh', $service->get('daily'));

        Cache::store($store)->flush();
    }

    public function test_flush_never_throws_when_cache_breaks(): void
    {
        Cache::shouldReceive('forever')->andThrow(new \Error('store exploded'));
        Cache::shouldReceive('get')->andReturn('0');

        $this->assertFalse(app(ReportCacheService::class)->flush());
    }
}
