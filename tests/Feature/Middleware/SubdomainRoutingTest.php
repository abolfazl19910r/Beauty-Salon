<?php

namespace Tests\Feature\Middleware;

use App\Models\Salon;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * ⭐ فاز ۲ SaaS، محور «۳. ساب‌دامین اختصاصی» (شروع‌شده، ۲۰۲۶-۰۹-۱۹).
 *
 * config('app.central_domain') یک تصمیم سطح-boot است، نه per-test (به docblock کنار
 * 'central_domain' در config/app.php نگاه کن) — routes/web.php فقط یک‌بار، در لحظه‌ی بوت
 * اپلیکیشن، بین Route::domain(...) و Route::prefix('s/{slug}') انتخاب می‌کند. به همین دلیل
 * این کلاس با phpunit.xml اصلی اجرا نمی‌شود، بلکه با phpunit.subdomain.xml (که CENTRAL_DOMAIN
 * را در سطح <php><env>، قبل از هر بوت اپ، ست می‌کند — دقیقاً مثل DB_CONNECTION):
 *
 *     vendor/bin/phpunit -c phpunit.subdomain.xml
 *
 * (عمداً از env runtime mutation داخل setUp() استفاده نشده — یک‌بار امتحان شد و در اجرای کل
 * سوییت به‌خاطر cache استاتیک/immutable خودِ Illuminate\Support\Env قابل‌اعتماد نبود؛
 * phpunit.subdomain.xml این مشکل را کامل کنار می‌گذارد چون در یک پردازش PHP کاملاً جدا اجرا
 * می‌شود.)
 */
class SubdomainRoutingTest extends TestCase
{
    use RefreshDatabase;

    private const CENTRAL_DOMAIN = 'rasta-test.local';

    public function test_active_salon_subdomain_resolves_the_correct_tenant(): void
    {
        $salon = Salon::factory()->create(['slug' => 'sobhan-beauty']);

        $response = $this->get('http://sobhan-beauty.'.self::CENTRAL_DOMAIN.'/');

        $response->assertOk();
        $this->assertSame($salon->id, app(CurrentSalon::class)->id());
    }

    public function test_route_helper_builds_a_subdomain_url_when_central_domain_is_configured(): void
    {
        Salon::factory()->create(['slug' => 'sobhan-beauty']);
        URL::defaults(['salon_slug' => 'sobhan-beauty']);

        $url = route('services.index');

        $this->assertStringStartsWith('http://sobhan-beauty.'.self::CENTRAL_DOMAIN, $url);
        $this->assertStringEndsWith('/services', $url);
    }

    public function test_suspended_salon_subdomain_returns_404(): void
    {
        $salon = Salon::factory()->suspended()->create(['slug' => 'closed-salon']);

        $response = $this->get('http://closed-salon.'.self::CENTRAL_DOMAIN.'/');

        $response->assertNotFound();
    }

    public function test_expired_salon_subdomain_returns_404(): void
    {
        Salon::factory()->expired()->create(['slug' => 'expired-salon']);

        $response = $this->get('http://expired-salon.'.self::CENTRAL_DOMAIN.'/');

        $response->assertNotFound();
    }

    public function test_unknown_salon_subdomain_returns_404(): void
    {
        $response = $this->get('http://no-such-salon.'.self::CENTRAL_DOMAIN.'/');

        $response->assertNotFound();
    }

    public function test_bare_central_domain_shows_the_placeholder_page(): void
    {
        $response = $this->get('http://'.self::CENTRAL_DOMAIN.'/');

        $response->assertOk();
        $response->assertViewIs('central.placeholder');
    }

    public function test_path_based_slash_s_slug_url_is_not_registered_once_central_domain_is_set(): void
    {
        // ⭐ محدودیت شناخته‌شده و مستندشده‌ی همین قدم اول (به config/app.php نگاه کن): وقتی
        // central_domain ست باشد، مسیرهای قدیمی /s/{slug} دیگر ثبت نمی‌شوند — این تست همان
        // رفتار فعلی را قفل می‌کند تا تغییرش (اگر در نشست بعدی تصمیم گرفته شد که هر دو هم‌زمان
        // زنده بمانند) عمدی باشد، نه یک رگرسیون خاموش.
        $salon = Salon::factory()->create(['slug' => 'sobhan-beauty']);

        $response = $this->get('http://'.self::CENTRAL_DOMAIN.'/s/'.$salon->slug.'/');

        $response->assertNotFound();
    }
}
