<?php

namespace Tests\Feature\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ تاریخچه: تا ۲۰۲۶-۰۹-۲۰ این تست مستند می‌کرد که با CENTRAL_DOMAIN خالی (پیش‌فرض)، زدن
 * دامنه‌ی خام (`/` بدون `/s/{slug}`) عمداً ۴۰۴ می‌ده، با این یادداشت که «بدون یک تصمیم صریح نباید
 * بی‌سروصدا عوض بشه».
 *
 * ⭐ تصمیم صریح ابوالفضل (۲۰۲۶-۰۹-۲۳): کسی که هنوز آدرس هیچ سالنی رو نداره، چه با
 * http://127.0.0.1:8000/ بیاد چه با rasta-app.test، باید صفحه‌ی خرید اشتراک
 * (CentralLandingController) رو ببینه. پس `/` حالا همیشه — مستقل از CENTRAL_DOMAIN — صفحه‌ی
 * فروش رو نشون می‌ده؛ مسیر /s/{slug} مثل قبل دست‌نخورده‌ست.
 */
class BareDomainWithoutCentralDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_bare_root_shows_the_landing_page_when_central_domain_is_not_configured(): void
    {
        $this->assertEmpty(config('app.central_domain'), 'این تست فقط برای حالت پیش‌فرض (CENTRAL_DOMAIN خالی) معتبره.');

        $this->get('/')
            ->assertOk()
            ->assertViewIs('central.landing')
            // بدون CENTRAL_DOMAIN پیش‌نمایش آدرس باید همون شکل /s/{slug} باشه.
            ->assertViewHas('addressPrefix', fn ($prefix) => str_ends_with($prefix, '/s/'));
    }

    public function test_slash_s_slug_path_still_works_when_central_domain_is_not_configured(): void
    {
        $this->get('/s/rasta')->assertOk();
    }
}
