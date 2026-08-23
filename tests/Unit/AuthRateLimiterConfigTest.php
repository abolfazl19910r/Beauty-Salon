<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Regression guard (test-writing session 11): the 'auth' rate limiter registered in
 * RouteServiceProvider::configureRateLimiting() used to hardcode Limit::perMinute(5) (5
 * attempts, 1-minute decay — Laravel's own default decay), completely ignoring the
 * MAX_LOGIN_ATTEMPTS/LOGIN_THROTTLE_MINUTES keys .env.example shipped (never actually read
 * anywhere in the app). It now reads auth.max_login_attempts / auth.login_throttle_minutes.
 *
 * This is the first coverage of this rate limiter definition at all — it was previously
 * entirely untested (and, as documented in Rasta_unified_prompt.md, not currently attached to
 * any route via `throttle:auth` middleware; it is registered and available for any route that
 * opts in, which is exactly what RateLimiter::for() + config wiring is meant to provide).
 */
class AuthRateLimiterConfigTest extends TestCase
{
    public function test_default_limit_allows_five_attempts_per_minute(): void
    {
        $limiter = RateLimiter::limiter('auth');
        $request = Request::create('/login', 'POST');
        $request->server->set('REMOTE_ADDR', '10.0.0.1');

        $limit = $limiter($request);

        $this->assertSame(5, $limit->maxAttempts);
        $this->assertSame(60, $limit->decaySeconds);
    }

    public function test_max_login_attempts_respects_a_configured_override(): void
    {
        config(['auth.max_login_attempts' => 3]);

        $limiter = RateLimiter::limiter('auth');
        $request = Request::create('/login', 'POST');
        $request->server->set('REMOTE_ADDR', '10.0.0.2');

        $limit = $limiter($request);

        $this->assertSame(3, $limit->maxAttempts);
    }

    public function test_login_throttle_minutes_respects_a_configured_override(): void
    {
        config(['auth.login_throttle_minutes' => 15]);

        $limiter = RateLimiter::limiter('auth');
        $request = Request::create('/login', 'POST');
        $request->server->set('REMOTE_ADDR', '10.0.0.3');

        $limit = $limiter($request);

        $this->assertSame(15 * 60, $limit->decaySeconds);
    }

    public function test_the_limit_key_is_scoped_by_ip_address(): void
    {
        $limiter = RateLimiter::limiter('auth');

        $requestA = Request::create('/login', 'POST');
        $requestA->server->set('REMOTE_ADDR', '10.0.0.10');
        $limitA = $limiter($requestA);

        $requestB = Request::create('/login', 'POST');
        $requestB->server->set('REMOTE_ADDR', '10.0.0.20');
        $limitB = $limiter($requestB);

        $this->assertNotSame($limitA->key, $limitB->key);
    }
}
