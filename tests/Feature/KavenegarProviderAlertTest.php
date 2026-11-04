<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Notifications\SuperAdmin\PlatformAlertNotification;
use App\Services\SMSService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Kavenegar\Exceptions\ApiException;
use Kavenegar\Exceptions\HttpException;
use Kavenegar\KavenegarApi;
use ReflectionClass;
use Tests\TestCase;

/**
 * ⭐ ۲۰۲۶-۱۱-۰۴: خطاهای سطح حساب کاوه‌نگار (اعتبار تمام، کلید نامعتبر، الگو تأییدنشده، سرویس پیشرفته، …) که تا رفع نشوند هیچ کد ورودی نمی‌رسد،
 * قبلاً فقط در laravel.log بودند. حالا اعلان داخلی ابرمدیر، هر کد حداکثر یک بار در ساعت.
 */
class KavenegarProviderAlertTest extends TestCase
{
    use RefreshDatabase;

    private User $super;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['env'] = 'production';
        config(['services.kavenegar.log_only' => false]);
        Notification::fake();
        Cache::flush();
        $this->super = User::factory()->create(['is_admin' => true, 'phone' => '09120009999']);
        $this->super->assignRole(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));
    }

    private function serviceFailingWith(\Throwable $e): SMSService
    {
        $service = new SMSService;
        $api = \Mockery::mock(KavenegarApi::class);
        $api->shouldReceive('VerifyLookup')->andThrow($e);
        $api->shouldReceive('Send')->andThrow($e);
        (new ReflectionClass($service))->getProperty('api')->setValue($service, $api);

        return $service;
    }

    public function test_running_out_of_credit_tells_the_super_admin_once_an_hour(): void
    {
        $service = $this->serviceFailingWith(new ApiException('اعتبار شما کافی نمی‌باشد', 418));

        $this->assertFalse($service->sendTemplate('09121234567', 'login-verify', ['123456']));
        $this->assertFalse($service->send('09121234567', 'نوبت شما تایید شد'));

        Notification::assertSentToTimes($this->super, PlatformAlertNotification::class, 1);
        Notification::assertSentTo($this->super, PlatformAlertNotification::class, fn ($n) => $n->kind === 'sms_provider'
            && str_contains($n->message, 'اعتبار حساب کاوه‌نگار تمام شده است') && str_contains($n->message, '418')
            && str_contains($n->message, 'login-verify') && $n->link === '/superadmin/sms');

        $this->travel(61)->minutes();
        $service->send('09121234567', 'نوبت شما تایید شد');
        Notification::assertSentToTimes($this->super, PlatformAlertNotification::class, 2);
    }

    public function test_each_account_level_code_has_its_own_alert(): void
    {
        foreach ([401, 403, 424, 426] as $code) {
            $this->serviceFailingWith(new ApiException('x', $code))->sendTemplate('09121234567', 'two-factor-auth', ['1']);
        }

        Notification::assertSentToTimes($this->super, PlatformAlertNotification::class, 4);
        Notification::assertSentTo($this->super, PlatformAlertNotification::class, fn ($n) => str_contains($n->message, 'سرویس «پیشرفته»'));
    }

    public function test_message_level_errors_and_network_failures_do_not_alert(): void
    {
        $this->serviceFailingWith(new ApiException('دریافت کننده نامعتبر است', 411))->send('0912', 'x');
        $this->serviceFailingWith(new ApiException('پیام خالی است', 413))->send('09121234567', '');
        $this->serviceFailingWith(new HttpException('timeout', 0))->send('09121234567', 'x');

        Notification::assertNothingSentTo($this->super);
    }

    public function test_the_kind_has_a_label_in_the_inbox(): void
    {
        $this->assertArrayHasKey('sms_provider', PlatformAlertNotification::KINDS);
    }
}
