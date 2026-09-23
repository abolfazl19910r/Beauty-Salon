<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\MatchSessionCookieDomainToHost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ رگرسیون‌گارد 419 Page Expired روی http://127.0.0.1:8000 (۲۰۲۶-۰۹-۲۳): با
 * SESSION_DOMAIN=.rasta-app.test، کوکی session/XSRF روی 127.0.0.1 با Domain=.rasta-app.test ارسال
 * می‌شد و مرورگر ذخیره‌اش نمی‌کرد → هر POST (ساخت سالن، خرید فوری، ورود) 419 می‌گرفت.
 */
class MatchSessionCookieDomainToHostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['session.domain' => '.rasta-app.test']);
    }

    private function cookieDomains(string $url): array
    {
        $domains = [];
        foreach ($this->get($url)->headers->getCookies() as $cookie) {
            $domains[$cookie->getName()] = $cookie->getDomain();
        }

        return $domains;
    }

    public function test_cookies_are_host_only_on_127_0_0_1(): void
    {
        $domains = $this->cookieDomains('http://127.0.0.1/login');

        $this->assertArrayHasKey('XSRF-TOKEN', $domains);
        $this->assertNull($domains['XSRF-TOKEN']);
        $this->assertNull($domains[config('session.cookie')]);
    }

    public function test_cookies_keep_the_shared_domain_on_the_central_domain_and_its_subdomains(): void
    {
        $this->assertSame('.rasta-app.test', $this->cookieDomains('http://rasta-app.test/login')['XSRF-TOKEN']);
        $this->assertSame('.rasta-app.test', $this->cookieDomains('http://almas.rasta-app.test/login')['XSRF-TOKEN']);
    }

    public function test_the_config_change_does_not_leak_into_the_next_request(): void
    {
        $this->cookieDomains('http://127.0.0.1/login');
        config(['session.domain' => '.rasta-app.test']);

        $this->assertSame('.rasta-app.test', $this->cookieDomains('http://rasta-app.test/login')['XSRF-TOKEN']);
    }

    public function test_domain_match_rule(): void
    {
        $this->assertTrue(MatchSessionCookieDomainToHost::hostMatchesCookieDomain('rasta-app.test', '.rasta-app.test'));
        $this->assertTrue(MatchSessionCookieDomainToHost::hostMatchesCookieDomain('almas.rasta-app.test', '.rasta-app.test'));
        $this->assertTrue(MatchSessionCookieDomainToHost::hostMatchesCookieDomain('ALMAS.Rasta-App.test', 'rasta-app.test'));
        $this->assertFalse(MatchSessionCookieDomainToHost::hostMatchesCookieDomain('127.0.0.1', '.rasta-app.test'));
        $this->assertFalse(MatchSessionCookieDomainToHost::hostMatchesCookieDomain('localhost', '.rasta-app.test'));
        $this->assertFalse(MatchSessionCookieDomainToHost::hostMatchesCookieDomain('evilrasta-app.test', '.rasta-app.test'));
    }
}
