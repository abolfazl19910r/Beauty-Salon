<?php

namespace Tests\Unit\Services;

use App\Models\Salon;
use App\Services\ReportCacheService;
use App\Support\CurrentSalon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * ⭐ Fix (preventive، ۲۰۲۶-۰۹-۱۹ — پیگیری محور «۳»): این تست قفل می‌کنه که کلید کش تولیدشده
 * توسط این سرویس شامل شناسه‌ی سالن جاری باشه — دقیقاً همون الگوی نشتی که در HomeController
 * پیدا و فیکس شد (کلید کش مشترک بین همه‌ی سالن‌ها)، اینجا preventive فیکس شد چون remember()/
 * put() این کلاس فعلاً هیچ‌جای کدبیس واقعاً صدا زده نمی‌شن.
 */
class ReportCacheServiceTest extends TestCase
{
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
}
