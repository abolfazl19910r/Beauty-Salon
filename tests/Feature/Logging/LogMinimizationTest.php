<?php

namespace Tests\Feature\Logging;

use App\Jobs\SendLoginVerificationCodeJob;
use App\Models\Category;
use App\Models\User;
use App\Services\CategoryService;
use App\Services\Review\ReviewService;
use App\Services\SMSService;
use App\Services\TwoFactorAuthService;
use App\Support\Logging\PersonalData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Kavenegar\KavenegarApi;
use ReflectionClass;
use Tests\TestCase;

/**
 * بررسی لاگ‌ها ۲۰۲۶-۱۱-۰۵: خط‌های تکراری حذف، خط‌های روزمره debug، و داده‌ی شخصی فقط به اندازه‌ی لازم.
 */
class LogMinimizationTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<MessageLogged> */
    private array $logged = [];

    protected function setUp(): void
    {
        parent::setUp();
        Event::listen(MessageLogged::class, function (MessageLogged $event) {
            $this->logged[] = $event;
        });
    }

    private function realSending(): void
    {
        $this->app['env'] = 'production';
        config(['services.kavenegar.log_only' => false]);
    }

    /** @return list<MessageLogged> */
    private function lines(string $message): array
    {
        return array_values(array_filter($this->logged, fn (MessageLogged $e) => $e->message === $message));
    }

    private function serviceWithApi(): SMSService
    {
        $service = new SMSService;
        $api = \Mockery::mock(KavenegarApi::class);
        $api->shouldReceive('Send')->andReturn([]);
        $api->shouldReceive('VerifyLookup')->andReturn([]);
        (new ReflectionClass($service))->getProperty('api')->setValue($service, $api);

        return $service;
    }

    public function test_real_sms_is_logged_at_debug_without_its_text(): void
    {
        $this->realSending();

        $this->serviceWithApi()->send('09121234567', 'نوبت شما ثبت شد: https://arisheh.ir/l/SECRETLINK');

        [$line] = $this->lines('SMS: در حال ارسال');
        $this->assertSame('debug', $line->level);
        $this->assertArrayNotHasKey('message', $line->context);
        $this->assertSame('0912***4567', $line->context['mobile']);
        $this->assertStringNotContainsString('SECRETLINK', json_encode($this->logged, JSON_UNESCAPED_UNICODE));
    }

    public function test_log_only_sms_keeps_its_text_at_info(): void
    {
        $this->realSending();
        config(['services.kavenegar.log_only' => true]);

        $this->serviceWithApi()->send('09121234567', 'کد شما 482913');
        $this->serviceWithApi()->sendTemplate('09121234567', 'login-verify', ['482913']);

        [$plain] = $this->lines('SMS: در حال ارسال');
        $this->assertSame('info', $plain->level);
        $this->assertSame('کد شما 482913', $plain->context['message']);
        [$template] = $this->lines('SMS Template: در حال ارسال');
        $this->assertSame('info', $template->level);
        $this->assertSame(['482913'], $template->context['tokens']);
    }

    public function test_queued_code_lines_are_debug_when_sms_really_goes_out(): void
    {
        $this->realSending();
        Bus::fake();
        $user = User::factory()->create(['phone' => '09121234567']);

        app(\App\Services\PhoneVerificationService::class)->sendLoginCode($user);
        app(TwoFactorAuthService::class)->generateCode($user);

        foreach (['Queued login verification code for sending', '2FA code generated'] as $message) {
            [$line] = $this->lines($message);
            $this->assertSame('debug', $line->level, $message);
            $this->assertArrayNotHasKey('phone', $line->context, $message);
        }
    }

    public function test_failed_code_jobs_log_the_user_not_the_phone(): void
    {
        $user = User::factory()->create(['phone' => '09121234567']);
        $sms = \Mockery::mock(SMSService::class);
        $sms->shouldReceive('sendTemplate', 'sendAuthTemplate')->andReturn(false);
        $this->app->instance(SMSService::class, $sms);

        app()->call([new SendLoginVerificationCodeJob($user->id, '123456'), 'handle']);

        [$line] = $this->lines('SendLoginVerificationCodeJob: failed to send login verification code');
        $this->assertSame($user->id, $line->context['user_id']);
        $this->assertArrayNotHasKey('phone', $line->context);
    }

    public function test_enabling_two_factor_is_logged_once(): void
    {
        $user = User::factory()->create(['two_factor_enabled' => false]);
        $user->update(['two_factor_code' => \App\Support\Auth\SmsCode::hash('123456'), 'two_factor_code_expires_at' => now()->addMinutes(2)]);

        $this->actingAs($user)->postJson(route('security.2fa.enable'), ['code' => '123456'])->assertOk();

        $this->assertCount(1, $this->lines('2FA enabled'), 'کنترلر و سرویس هر دو می‌نوشتند');
        $this->assertCount(1, $this->lines('2FA verified successfully'));
    }

    public function test_category_crud_writes_no_routine_info_lines(): void
    {
        $service = app(CategoryService::class);
        $category = $service->create(['name' => 'رنگ مو', 'slug' => 'hair-color-'.uniqid()]);
        $service->update($category, ['name' => 'رنگ و مش']);

        $this->assertSame([], array_filter($this->logged, fn ($e) => str_starts_with($e->message, 'دسته‌بندی')));
        $this->assertInstanceOf(Category::class, $category);
    }

    public function test_review_tokens_are_logged_as_fingerprints(): void
    {
        $this->assertNull(app(ReviewService::class)->validateToken('raw-review-token-123'));

        $all = json_encode(array_map(fn ($e) => [$e->message, $e->context], $this->logged), JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('raw-review-token-123', $all);
        $this->assertStringContainsString(PersonalData::fingerprint('raw-review-token-123'), $all);
    }
}
