<?php

namespace Tests\Feature\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ بررسی (۲۰۲۶-۰۹-۲۰): «۴۰۴ روی دامنه‌ی اصلی بدون ساب‌دامین» که در قدم‌های باز قبلی flag شده
 * بود، ریشه‌یابی و بازتولید شد — یک باگ کد نیست، رفتار مستندشده‌ی خودِ فاز فعلیه.
 *
 * علت واقعی: config('app.central_domain') (از env('CENTRAL_DOMAIN')) در `.env` و
 * `.env.example` هر دو به‌صورت پیش‌فرض خالیه. طبق کامنت بالای routes/web.php («خالیه (پیش‌فرض؛
 * همه‌ی تست‌های اصلی این حالت رو می‌بینن) → فقط Route::prefix('s/{salon_slug}')، بدون هیچ تغییر
 * رفتاری»)، وقتی CENTRAL_DOMAIN خالیه، بلوک `if ($centralDomain)` کلاً اجرا نمی‌شه — یعنی نه
 * Route::domain($centralDomain) (صفحه‌ی placeholder) نه Route::domain('{slug}.'.central_domain)
 * (ساب‌دامین‌ها) اصلاً ثبت نمی‌شن. تنها روت زنده، Route::prefix('s/{salon_slug}') است که همیشه
 * (بدون قید) ثبت می‌شه — یعنی زدن دامنه‌ی خام (`/` بدون `/s/{slug}`) واقعاً هیچ روتی نداره و
 * ۴۰۴ کاملاً درسته، نه یک باگ.
 *
 * با یک تست دستی واقعی (php artisan serve + curl با Host header) هم تکرار و تأیید شد: با
 * CENTRAL_DOMAIN=rasta-app.test در .env، زدن `http://rasta-app.test:8000/` بدون هیچ subdomain
 * یا `/s/slug`، صفحه‌ی placeholder رو با status ۲۰۰ درست نشون داد (دقیقاً مطابق
 * SubdomainRoutingTest::test_bare_central_domain_shows_the_placeholder_page، که فقط با
 * phpunit.subdomain.xml اجرا می‌شه). ۴۰۴ گزارش‌شده‌ی قبلی احتمالاً یا در حالت CENTRAL_DOMAIN خالی
 * (پیش‌فرض) دیده شده، یا هاست تست‌شده با مقدار واقعی CENTRAL_DOMAIN در آن لحظه مطابقت نداشته —
 * هر دو یک مشکل environment/config‌اند، نه کد.
 *
 * این تست همون رفتار پیش‌فرض (CENTRAL_DOMAIN خالی، همون چیزی که ۱۰۵۹ تست دیگه‌ی این سوییت هم
 * توش اجرا می‌شن) رو مستند می‌کنه: بدون یک تصمیم صریح («محور ۴. ثبت‌نام عمومی سالن (self-service)»
 * که این فایل به‌عنوان قدم باز مستندش می‌کنه)، دامنه‌ی خام باید ۴۰۴ بده — این انتظار درسته و
 * نباید بی‌سروصدا عوض بشه.
 */
class BareDomainWithoutCentralDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_bare_root_404s_when_central_domain_is_not_configured(): void
    {
        $this->assertEmpty(config('app.central_domain'), 'این تست فقط برای حالت پیش‌فرض (CENTRAL_DOMAIN خالی) معتبره.');

        $this->get('/')->assertNotFound();
    }

    public function test_slash_s_slug_path_still_works_when_central_domain_is_not_configured(): void
    {
        $this->get('/s/rasta')->assertOk();
    }
}
