<?php

namespace Tests\Feature\Security;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * بررسی امنیتی ۲۰۲۶-۱۱-۰۴، M4: پنل ابرمدیر فقط از SUPERADMIN_ALLOWED_IPS (خالی = همه؛ تصمیم فعلی: باز، فقط آماده).
 */
class SuperAdminIpRestrictionTest extends TestCase
{
    use RefreshDatabase;

    private User $super;

    protected function setUp(): void
    {
        parent::setUp();
        $this->super = User::factory()->create(['user_type' => 'staff', 'is_admin' => true]);
        $this->super->assignRole(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));
    }

    private function fromIp(string $ip): static
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip]);
    }

    public function test_an_empty_list_keeps_the_panel_open_as_today(): void
    {
        config(['security.superadmin_allowed_ips' => []]);

        $this->fromIp('203.0.113.50')->actingAs($this->super)->get('/superadmin/dashboard')->assertOk();
    }

    public function test_only_listed_ips_and_ranges_reach_the_panel(): void
    {
        config(['security.superadmin_allowed_ips' => ['198.51.100.7', '10.20.0.0/16', '2001:db8::/32']]);

        $this->fromIp('198.51.100.7')->actingAs($this->super)->get('/superadmin/dashboard')->assertOk();
        $this->fromIp('10.20.5.9')->actingAs($this->super)->get('/superadmin/dashboard')->assertOk();
        $this->fromIp('2001:db8::1')->actingAs($this->super)->get('/superadmin/dashboard')->assertOk();

        $this->fromIp('198.51.100.8')->actingAs($this->super)->get('/superadmin/dashboard')->assertNotFound();
        $this->fromIp('198.51.100.8')->actingAs($this->super)->post('/superadmin/salons', [])->assertNotFound();
    }

    public function test_a_guest_from_another_ip_gets_404_not_a_login_redirect(): void
    {
        config(['security.superadmin_allowed_ips' => ['198.51.100.7']]);

        $this->fromIp('203.0.113.9')->get('/superadmin/dashboard')->assertNotFound();
        $this->fromIp('198.51.100.7')->get('/superadmin/dashboard')->assertRedirect();
    }

    public function test_every_superadmin_route_carries_the_ip_middleware(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($r) => str_starts_with($r->uri(), 'superadmin'));

        $this->assertGreaterThan(20, $routes->count());
        foreach ($routes as $route) {
            $this->assertContains('superadmin.ip', $route->gatherMiddleware(), $route->uri());
        }
    }

    public function test_env_list_is_parsed_with_spaces_and_blanks(): void
    {
        putenv('SUPERADMIN_ALLOWED_IPS= 1.2.3.4 , ,10.0.0.0/8');
        $_ENV['SUPERADMIN_ALLOWED_IPS'] = $_SERVER['SUPERADMIN_ALLOWED_IPS'] = ' 1.2.3.4 , ,10.0.0.0/8';
        try {
            $config = require config_path('security.php');
            $this->assertSame(['1.2.3.4', '10.0.0.0/8'], $config['superadmin_allowed_ips']);
        } finally {
            putenv('SUPERADMIN_ALLOWED_IPS');
            unset($_ENV['SUPERADMIN_ALLOWED_IPS'], $_SERVER['SUPERADMIN_ALLOWED_IPS']);
        }
    }
}
