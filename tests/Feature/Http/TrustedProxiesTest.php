<?php

namespace Tests\Feature\Http;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * ⭐ پروکسی‌های مورد اعتماد (config/trustedproxy.php). باگ واقعی، با probe بازتولید شد: پشت Cloudflare همه‌ی مشتری‌هایی
 * که از یک لبه می‌اومدن یک سهمیه‌ی ورود مشترک داشتن — اولین تلاش مشتری B با ۴۲۹ رد می‌شد چون مشتری A سهمیه رو تموم کرده بود.
 */
class TrustedProxiesTest extends TestCase
{
    use RefreshDatabase;

    private const CLOUDFLARE_EDGE = '162.158.10.20';

    protected function setUp(): void
    {
        parent::setUp();
        Route::get('/_proxy-probe', fn (Request $r) => ['ip' => $r->ip(), 'secure' => $r->secure(), 'url' => url('/x')]);
    }

    private function probe(string $remote, array $headers): array
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $remote])->withHeaders($headers)->getJson('/_proxy-probe')->json();
    }

    public function test_behind_cloudflare_the_real_customer_ip_and_https_are_seen(): void
    {
        $seen = $this->probe(self::CLOUDFLARE_EDGE, ['X-Forwarded-For' => '5.1.1.1', 'X-Forwarded-Proto' => 'https']);

        $this->assertSame('5.1.1.1', $seen['ip']);
        $this->assertTrue($seen['secure']);
        $this->assertStringStartsWith('https://', $seen['url'], 'آدرس بازگشت درگاه‌ها و لینک‌ها https ساخته می‌شن');
    }

    public function test_customers_behind_the_same_cloudflare_edge_do_not_share_one_login_budget(): void
    {
        config(['auth.max_login_attempts' => 3]);
        $user = User::factory()->create(['password' => bcrypt('password123'), 'user_type' => 'staff']);
        $attempt = fn (string $client) => $this->withServerVariables(['REMOTE_ADDR' => self::CLOUDFLARE_EDGE])
            ->withHeaders(['X-Forwarded-For' => $client])->postJson('/login', ['phone' => $user->phone, 'password' => 'wrong'])->status();

        for ($i = 0; $i < 3; $i++) {
            $attempt('5.1.1.1');
        }

        $this->assertSame(429, $attempt('5.1.1.1'), 'خود مهاجم همچنان محدود می‌شه');
        $this->assertNotSame(429, $attempt('5.2.2.2'), 'مشتری دیگری پشت همون لبه قفل نمی‌شه');
    }

    public function test_a_forged_forwarded_header_from_anyone_else_is_ignored(): void
    {
        $seen = $this->probe('5.5.5.5', ['X-Forwarded-For' => '1.2.3.4', 'X-Forwarded-Proto' => 'https']);
        $this->assertSame('5.5.5.5', $seen['ip']);
        $this->assertFalse($seen['secure']);

        // مهاجمی که از پشت Cloudflare آدرس جعلی به سرآیند اضافه کنه: آدرسی که خود Cloudflare اضافه کرده ملاکه
        $viaCloudflare = $this->probe(self::CLOUDFLARE_EDGE, ['X-Forwarded-For' => '1.2.3.4, 5.1.1.1']);
        $this->assertSame('5.1.1.1', $viaCloudflare['ip']);
    }

    public function test_rotating_a_forged_header_does_not_reset_the_login_budget(): void
    {
        config(['auth.max_login_attempts' => 2]);
        $user = User::factory()->create(['password' => bcrypt('password123'), 'user_type' => 'staff']);
        $statuses = [];
        foreach (['9.9.9.1', '9.9.9.2', '9.9.9.3'] as $fake) {
            $statuses[] = $this->withServerVariables(['REMOTE_ADDR' => '5.5.5.5'])->withHeaders(['X-Forwarded-For' => $fake])
                ->postJson('/login', ['phone' => $user->phone, 'password' => 'wrong'])->status();
        }

        $this->assertSame(429, end($statuses));
    }

    public function test_the_trusted_list_is_configurable(): void
    {
        config(['trustedproxy.proxies' => 'none']);
        $this->assertSame(self::CLOUDFLARE_EDGE, $this->probe(self::CLOUDFLARE_EDGE, ['X-Forwarded-For' => '5.1.1.1'])['ip']);

        config(['trustedproxy.proxies' => 'cloudflare,172.16.0.0/12']); // nginx میزبان جلوی Docker
        $this->assertSame('5.1.1.1', $this->probe('172.18.0.1', ['X-Forwarded-For' => '5.1.1.1'])['ip']);
        $this->assertSame('5.1.1.1', $this->probe(self::CLOUDFLARE_EDGE, ['X-Forwarded-For' => '5.1.1.1'])['ip']);

        config(['trustedproxy.proxies' => '*']);
        $this->assertSame('5.1.1.1', $this->probe('8.8.8.8', ['X-Forwarded-For' => '5.1.1.1'])['ip']);
    }

    public function test_the_default_is_cloudflare_only(): void
    {
        $this->assertSame('cloudflare', config('trustedproxy.proxies'));
        $this->assertCount(22, config('trustedproxy.cloudflare'), 'cloudflare.com/ips: ۱۵ IPv4 + ۷ IPv6');
    }
}
