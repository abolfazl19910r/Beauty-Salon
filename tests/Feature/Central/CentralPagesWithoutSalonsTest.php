<?php

namespace Tests\Feature\Central;

use App\Models\Salon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 🐞 ۲۰۲۶-۱۱-۱۱ (سرور production، اولین ورود ابرمدیر): https://arisheh.ir/login ← ۵۰۰
 * «Missing required parameter for [Route: home] … [Missing parameter: salon_slug] (View: layouts/guest.blade.php)».
 * سرور واقعی بی داده‌ی نمونه است (MAHRU_SEED=false) و هنوز هیچ سالنی ندارد؛ URL::defaults ‏salon_slug را از قدیمی‌ترین سالن می‌گیرد
 * (AppServiceProvider::applyDefaultSalonSlug) ← بدون سالن، هر route('home') و مشابهش روی صفحه‌های دامنه‌ی اصلی می‌شکند. لوکال و تست‌ها
 * همیشه سالن seed‌شده داشتند. این تست صفحه‌های GET دامنه‌ی اصلی را با صفر سالن می‌سنجد.
 */
class CentralPagesWithoutSalonsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // مثل سرور تازه: هیچ سالنی نیست (TestCase سالن پیش‌فرض «rasta» را می‌سازد و URL::defaults را با آن پر می‌کند)
        $this->app->make(\App\Support\CurrentSalon::class)->clear();
        \Illuminate\Support\Facades\Schema::withoutForeignKeyConstraints(fn () => Salon::query()->withoutGlobalScopes()->delete());
        $this->assertSame(0, Salon::query()->count());
        Cache::forget('app:default_salon_slug');
        \Illuminate\Support\Facades\URL::defaults(['salon_slug' => null]);
    }

    public static function pages(): array
    {
        return [
            'landing' => ['/'],
            'login' => ['/login'],
            'forgot password' => ['/forgot-password'],
            'register' => ['/register'],
            'my salons' => ['/my-salons'],
            'salon signup' => ['/salon-signup'],
            'privacy' => ['/privacy'],
            'terms' => ['/terms'],
            'contact' => ['/contact'],
            'app download' => ['/app'],
            'offline' => ['/offline'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function test_central_page_renders_with_no_salon_in_the_database(string $uri): void
    {
        $response = $this->get($uri);

        $this->assertLessThan(500, $response->getStatusCode(), $uri.' ← '.$response->getStatusCode().' '.substr(strip_tags((string) $response->getContent()), 0, 300));
    }

    public static function superAdminPages(): array
    {
        return array_map(fn ($p) => ['/superadmin/'.$p], array_combine($list = [
            'dashboard', 'salons', 'salons/create', 'plans', 'plans/create', 'settings', 'payments', 'payment-attention', 'sms', 'push',
            'push/devices', 'app-releases', 'announcements', 'announcements/create', 'audit-log', 'demo-requests', 'failed-jobs',
            'notifications', 'retained-data', 'support-tickets', 'templates', 'verifications', 'search?q=test',
        ], $list));
    }

    /** اولین کار روی سرور تازه: ورود ابرمدیر و ساختن پلن‌ها/سالن — همه بی هیچ سالنی */
    #[\PHPUnit\Framework\Attributes\DataProvider('superAdminPages')]
    public function test_super_admin_panel_works_before_the_first_salon_exists(string $uri): void
    {
        $admin = \App\Models\User::factory()->create(['is_admin' => true, 'salon_id' => null]);
        $admin->assignRole(\App\Models\Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));

        $response = $this->actingAs($admin)->get($uri);

        $this->assertLessThan(500, $response->getStatusCode(), $uri.' ← '.$response->getStatusCode());
        $this->assertNotSame(404, $response->getStatusCode(), $uri);
    }

    public function test_the_first_super_admin_login_works_end_to_end_on_an_empty_server(): void
    {
        $admin = \App\Models\User::factory()->create([
            'password' => bcrypt('password123'), 'user_type' => 'staff', 'is_admin' => true, 'salon_id' => null,
        ]);
        $admin->assignRole(\App\Models\Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));

        $this->get('/login')->assertOk();
        $this->post('/login', ['phone' => $admin->phone, 'password' => 'password123'])->assertRedirect(route('login.verify.show'));
        $this->get(route('login.verify.show'))->assertOk();
        $this->post('/login/verify', ['code' => $this->lastSmsCode()])->assertRedirect('/superadmin/dashboard');
        $this->assertAuthenticatedAs($admin);
        $this->get('/superadmin/dashboard')->assertOk();
    }

    public function test_an_unknown_url_is_a_404_not_a_500(): void
    {
        $this->get('/no-such-page-here')->assertNotFound();
    }
}
