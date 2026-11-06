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

    /**
     * ۲۰۲۶-۱۱-۰۶ (آزمون دستی داکر): با CACHE_STORE=database (پیش‌فرض داکر تک‌کانتینری) و دیتابیس خالی، خواندن همین کش در boot خطای
     * «Table 'mahru.cache' doesn't exist» می‌داد ← حتی `php artisan migrate` بالا نمی‌آمد و کانتینر در حلقه‌ی راه‌اندازی دوباره می‌ماند.
     */
    public function test_boot_survives_a_cache_store_whose_table_does_not_exist_yet(): void
    {
        config(['cache.default' => 'database', 'cache.stores.database.table' => 'no_such_cache_table']);
        Cache::forgetDriver('database');
        URL::defaults(['salon_slug' => null]);
        $oldest = \App\Models\Salon::query()->orderBy('id')->value('slug');

        (new AppServiceProvider($this->app))->applyDefaultSalonSlug();

        $this->assertSame($oldest, URL::getDefaultParameters()['salon_slug'] ?? null, 'بی کش هم مستقیم از دیتابیس');
    }
}
