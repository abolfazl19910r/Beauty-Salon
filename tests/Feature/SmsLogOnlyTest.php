<?php

namespace Tests\Feature;

use App\Services\SMSService;
use Illuminate\Support\Facades\Log;
use Kavenegar\KavenegarApi;
use ReflectionClass;
use Tests\TestCase;

/**
 * SMS_LOG_ONLY (2026-10-24): on the real server, before the Kavenegar line and templates are ready, no SMS may reach Kavenegar —
 * even with APP_ENV=production — while the text (and the login code) still lands in the log so the owner can sign in.
 */
class SmsLogOnlyTest extends TestCase
{
    private function serviceWithApiSpy(): array
    {
        $service = new SMSService;
        $apiSpy = \Mockery::mock(KavenegarApi::class);

        $property = (new ReflectionClass($service))->getProperty('api');
        $property->setValue($service, $apiSpy);

        return [$service, $apiSpy];
    }

    private function asProduction(): void
    {
        $this->app['env'] = 'production';
    }

    public function test_log_only_blocks_plain_sms_in_production(): void
    {
        $this->asProduction();
        config(['services.kavenegar.log_only' => true]);
        [$service, $apiSpy] = $this->serviceWithApiSpy();
        $apiSpy->shouldNotReceive('Send');
        Log::spy();

        $this->assertTrue($service->send('09121234567', 'test message'));

        Log::shouldHaveReceived('info')->withArgs(fn ($message, $context = []) => $message === 'SMS: در حال ارسال'
            && ($context['message'] ?? null) === 'test message');
    }

    public function test_log_only_blocks_the_login_code_in_production_and_logs_it(): void
    {
        $this->asProduction();
        config(['services.kavenegar.log_only' => true]);
        [$service, $apiSpy] = $this->serviceWithApiSpy();
        $apiSpy->shouldNotReceive('VerifyLookup');
        Log::spy();

        $this->assertTrue($service->sendTemplate('09121234567', 'login-verify', ['482913']));

        Log::shouldHaveReceived('info')->withArgs(fn ($message, $context = []) => $message === 'SMS Template: در حال ارسال'
            && ($context['tokens'] ?? null) === ['482913']);
    }

    public function test_log_only_wins_over_send_in_local(): void
    {
        config(['services.kavenegar.log_only' => true, 'services.kavenegar.send_in_local' => true]);
        [$service, $apiSpy] = $this->serviceWithApiSpy();
        $apiSpy->shouldNotReceive('Send');

        $this->assertTrue($service->send('09121234567', 'test message'));
    }

    public function test_production_without_log_only_still_sends_for_real(): void
    {
        $this->asProduction();
        config(['services.kavenegar.log_only' => false]);
        [$service, $apiSpy] = $this->serviceWithApiSpy();
        $apiSpy->shouldReceive('Send')->once()->andReturn(true);
        $apiSpy->shouldReceive('VerifyLookup')->once()->andReturn(true);

        $this->assertTrue($service->send('09121234567', 'test message'));
        $this->assertTrue($service->sendTemplate('09121234567', 'login-verify', ['482913']));
    }

    public function test_env_value_is_parsed_as_a_boolean(): void
    {
        $this->assertFalse(filter_var('false', FILTER_VALIDATE_BOOL));
        $source = file_get_contents(config_path('services.php'));
        $this->assertStringContainsString("filter_var(env('SMS_LOG_ONLY', false), FILTER_VALIDATE_BOOL)", $source);
    }

    public function test_a_missing_api_key_does_not_kill_the_process(): void
    {
        // KavenegarApi calls die() for a null key; before the cast this would end the whole PHP process.
        config(['services.kavenegar.api_key' => null]);

        $this->assertInstanceOf(SMSService::class, new SMSService);
    }
}
