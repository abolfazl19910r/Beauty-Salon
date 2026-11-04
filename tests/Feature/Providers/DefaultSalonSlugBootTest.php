<?php

namespace Tests\Feature\Providers;

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * ⭐ ۲۰۲۶-۱۱-۰۴: پیش‌فرض salon_slug در boot هر درخواست. Schema::hasTable قبلاً بیرون از کش بود و در هر درخواست یک کوئری information_schema
 * (۴۰ تا ۸۰ میلی‌ثانیه روی MariaDB لوکال) می‌زد؛ حالا با کش گرم هیچ کوئری‌ای نمی‌رود.
 */
class DefaultSalonSlugBootTest extends TestCase
{
    use RefreshDatabase;

    private function boot(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        (new AppServiceProvider($this->app))->applyDefaultSalonSlug();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_a_warm_cache_costs_no_query_and_sets_the_url_default(): void
    {
        Cache::forget('app:default_salon_slug');
        $oldest = \App\Models\Salon::query()->orderBy('id')->value('slug');

        $this->assertGreaterThan(0, $this->boot(), 'کش سرد: یک بار جدول و سالن پرسیده می‌شود');
        $this->assertSame($oldest, Cache::get('app:default_salon_slug'));

        $this->assertSame(0, $this->boot(), 'کش گرم: نه hasTable، نه select');
        $this->assertSame($oldest, URL::getDefaultParameters()['salon_slug'] ?? null);
    }
}
