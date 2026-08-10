<?php

namespace Tests\Feature;

use App\Services\SMSService;
use Kavenegar\KavenegarApi;
use ReflectionClass;
use Tests\TestCase;

/**
 * Regression guard for a real, previously undocumented bug: SMSService::send()/sendTemplate()
 * only skipped the real Kavenegar API call in the 'local' environment, never in 'testing'. Since
 * phpunit.xml sets APP_ENV=testing (the standard Laravel default), running the test suite on any
 * machine with real network access and a configured Kavenegar API key silently attempted real SMS
 * sends on every notification — which is both a correctness/safety hazard (real API calls during
 * tests) and, as observed firsthand, a ~20x test-suite slowdown (203s vs ~9s) caused by the real
 * network round-trips.
 *
 * These tests replace SMSService's internal Kavenegar client with a Mockery spy via reflection
 * (rather than relying on network-reachability side effects, which differ between sandboxes) so
 * the assertion is unambiguous: the underlying API client must never be touched while running
 * under APP_ENV=testing, regardless of network availability.
 */
class SmsServiceEnvironmentGuardTest extends TestCase
{
    private function serviceWithApiSpy(): array
    {
        $service = new SMSService;
        $apiSpy = \Mockery::mock(KavenegarApi::class);

        $reflection = new ReflectionClass($service);
        $property = $reflection->getProperty('api');
        $property->setAccessible(true);
        $property->setValue($service, $apiSpy);

        return [$service, $apiSpy];
    }

    public function test_send_never_touches_the_real_api_client_while_testing(): void
    {
        config(['services.kavenegar.send_in_local' => false]);
        [$service, $apiSpy] = $this->serviceWithApiSpy();
        $apiSpy->shouldNotReceive('Send');

        $result = $service->send('09121234567', 'test message');

        $this->assertTrue($result);
    }

    public function test_send_template_never_touches_the_real_api_client_while_testing(): void
    {
        config(['services.kavenegar.send_in_local' => false]);
        [$service, $apiSpy] = $this->serviceWithApiSpy();
        $apiSpy->shouldNotReceive('VerifyLookup');

        $result = $service->sendTemplate('09121234567', 'login-verify', ['123456']);

        $this->assertTrue($result);
    }

    public function test_the_guard_can_still_be_deliberately_overridden_for_real_send_in_local_config(): void
    {
        // Confirms the pre-existing send_in_local escape hatch still works after the fix — the
        // guard is still bypassable on purpose (e.g. for a rare local integration-test session
        // against the real Kavenegar sandbox), just no longer bypassed *by accident* for every
        // ordinary `php artisan test` run.
        config(['services.kavenegar.send_in_local' => true]);
        [$service, $apiSpy] = $this->serviceWithApiSpy();
        $apiSpy->shouldReceive('Send')->once()->andReturn(true);

        $result = $service->send('09121234567', 'test message');

        $this->assertTrue($result);
    }
}
