<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Services\Customer\CustomerRegistrationService;
use App\Services\SalonSignup\SalonSignupChallenge;
use App\Services\SMSService;
use App\Services\Specialist\SpecialistActivationService;
use App\Support\CurrentSalon;
use App\Support\Sms\OtpLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Kavenegar\KavenegarApi;
use ReflectionClass;
use Tests\TestCase;

/**
 * بررسی امنیتی ۲۰۲۶-۱۱-۰۴ — M2 (کد ورود همیشه در لاگ info بود) و M6 (کل ورودی شارژ کیف پول در لاگ).
 */
class SecretsNotLoggedTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{string, array}> */
    private array $logged = [];

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake();
        Log::listen(function ($event) {
            $this->logged[] = [$event->message, $event->context];
        });
    }

    private function realSending(): void
    {
        $this->app['env'] = 'production';
        config(['services.kavenegar.log_only' => false]);
    }

    private function assertNoLoggedValue(string $secret): void
    {
        foreach ($this->logged as [$message, $context]) {
            $this->assertStringNotContainsString($secret, $message.json_encode($context, JSON_UNESCAPED_UNICODE), "راز در لاگ: {$message}");
        }
    }

    private function openAllChallenges(): array
    {
        $salonId = app(CurrentSalon::class)->id();
        $codes = [];
        foreach ([
            [app(CustomerRegistrationService::class), ['phone' => '09121000001', 'salon_id' => $salonId]],
            [app(SpecialistActivationService::class), ['phone' => '09121000002', 'salon_id' => $salonId]],
            [app(SalonSignupChallenge::class), ['phone' => '09121000003']],
        ] as [$service, $data]) {
            $service->open($data);
            $codes[] = Bus::dispatched(\App\Jobs\SendRegistrationCodeJob::class)->last()->code;
        }

        return $codes;
    }

    public function test_registration_codes_are_not_logged_when_sms_really_goes_out(): void
    {
        $this->realSending();

        $codes = $this->openAllChallenges();

        $this->assertCount(3, $codes);
        foreach ($codes as $code) {
            $this->assertNoLoggedValue($code);
        }
        $this->assertNotEmpty($this->logged, 'لاگ «Queued … code» همچنان نوشته می‌شود، فقط بی کد');
    }

    public function test_codes_are_still_logged_in_log_only_mode(): void
    {
        $this->realSending();
        config(['services.kavenegar.log_only' => true]);

        $codes = $this->openAllChallenges();

        $all = json_encode($this->logged, JSON_UNESCAPED_UNICODE);
        foreach ($codes as $code) {
            $this->assertStringContainsString($code, $all);
        }
    }

    public function test_login_code_is_not_logged_when_sms_really_goes_out(): void
    {
        $this->realSending();
        $user = User::factory()->create();

        app(\App\Services\PhoneVerificationService::class)->sendLoginCode($user);

        $job = Bus::dispatched(\App\Jobs\SendLoginVerificationCodeJob::class)->last();
        $code = (new ReflectionClass($job))->getProperty('code')->getValue($job);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertNoLoggedValue($code);
    }

    public function test_template_tokens_are_masked_unless_log_only(): void
    {
        $this->realSending();
        $service = new SMSService;
        $api = \Mockery::mock(KavenegarApi::class);
        $api->shouldReceive('VerifyLookup')->andReturn([]);
        (new ReflectionClass($service))->getProperty('api')->setValue($service, $api);

        $service->sendTemplate('09121234567', 'login-verify', ['482913']);

        $this->assertNoLoggedValue('482913');
        $this->assertSame(['***'], OtpLog::tokens(['482913']));
        config(['services.kavenegar.log_only' => true]);
        $this->assertSame(['482913'], OtpLog::tokens(['482913']));
    }

    public function test_wallet_charge_validation_error_logs_only_the_amount(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('wallet.charge.process'), ['amount' => '5', 'secret_field' => 'TOPSECRET-123'])
            ->assertSessionHasErrors('amount');

        $this->assertNoLoggedValue('TOPSECRET-123');
        $this->assertTrue(collect($this->logged)->contains(fn ($l) => ($l[1]['amount'] ?? null) === '5'), 'مبلغ همچنان لاگ می‌شود');
    }
}
