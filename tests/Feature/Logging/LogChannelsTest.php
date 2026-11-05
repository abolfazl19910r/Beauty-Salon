<?php

namespace Tests\Feature\Logging;

use App\Logging\MaskPersonalData;
use App\Logging\MirrorToDefaultChannel;
use App\Services\SecurityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * بررسی لاگ‌ها ۲۰۲۶-۱۱-۰۵: لاگ عمومی (۱۴ روز) ماسک‌شده، لاگ امنیت (۱۹۰ روز) و پرداخت (۳۶۵ روز) خام و کامل،
 * و هشدارهای آن دو در لاگ عمومی هم دیده می‌شوند.
 */
class LogChannelsTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = storage_path('framework/testing/logs-'.uniqid());
        File::ensureDirectoryExists($this->dir);

        config([
            'logging.default' => 'single',
            'logging.channels.single.path' => $this->dir.'/laravel.log',
            'logging.channels.security.driver' => 'single',
            'logging.channels.security.path' => $this->dir.'/security.log',
            'logging.channels.payments.driver' => 'single',
            'logging.channels.payments.path' => $this->dir.'/payments.log',
        ]);
        foreach (['single', 'security', 'payments'] as $channel) {
            Log::forgetChannel($channel);
        }
    }

    protected function tearDown(): void
    {
        foreach (['single', 'security', 'payments'] as $channel) {
            Log::forgetChannel($channel);
        }
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    private function read(string $file): string
    {
        return is_file($this->dir.'/'.$file) ? (string) file_get_contents($this->dir.'/'.$file) : '';
    }

    public function test_general_channels_mask_mobiles_and_ibans_including_exception_messages(): void
    {
        foreach (['single', 'daily', 'stderr', 'syslog', 'errorlog'] as $channel) {
            $this->assertContains(MaskPersonalData::class, config("logging.channels.{$channel}.tap", []), $channel);
        }

        Log::channel('single')->warning('SMS failed for 09121234567', ['mobile' => '09351230011', 'iban' => 'IR062960000000100324203456']);
        Log::channel('single')->error('boom', ['exception' => new \RuntimeException("Duplicate entry '09129998877'")]);

        $log = $this->read('laravel.log');
        $this->assertStringContainsString('0912***4567', $log);
        $this->assertStringContainsString('0935***0011', $log);
        $this->assertStringContainsString('0912***8877', $log);
        $this->assertStringContainsString('IR06******************3456', $log);
        foreach (['09121234567', '09351230011', '09129998877', 'IR062960000000100324203456'] as $raw) {
            $this->assertStringNotContainsString($raw, $log);
        }
    }

    public function test_masking_can_be_turned_off_for_local_debugging(): void
    {
        config(['logging.mask_personal_data' => false]);
        Log::forgetChannel('single');

        Log::channel('single')->info('to 09121234567');

        $this->assertStringContainsString('09121234567', $this->read('laravel.log'));
    }

    public function test_security_log_keeps_info_events_raw_and_mirrors_warnings_masked(): void
    {
        $this->assertSame('info', config('logging.channels.security.level'), 'پیش‌تر warning بود و «ورود موفق» و «تغییر پروفایل» هیچ‌وقت در security.log نمی‌نشست');
        $this->assertContains(MirrorToDefaultChannel::class.':warning', config('logging.channels.security.tap', []));
        config(['logging.channels.security.level' => 'info']);
        Log::forgetChannel('security');

        Log::channel('security')->info('Successful login', ['username' => '09121234567']);
        Log::channel('security')->warning('Failed login attempt', ['username' => '09351230011']);

        $security = $this->read('security.log');
        $this->assertStringContainsString('Successful login', $security);
        $this->assertStringContainsString('09121234567', $security, 'مدرک ماده‌ی ۶۶۸: خام');
        $this->assertStringContainsString('09351230011', $security);

        $general = $this->read('laravel.log');
        $this->assertStringContainsString('Failed login attempt', $general);
        $this->assertStringContainsString('0935***0011', $general);
        $this->assertStringNotContainsString('Successful login', $general, 'info فقط در فایل خودش');
    }

    public function test_payment_warnings_are_kept_in_payments_log_and_shown_in_the_general_log(): void
    {
        $this->assertContains(MirrorToDefaultChannel::class.':warning', config('logging.channels.payments.tap', []));
        $this->assertSame(365, config('logging.channels.payments.days'));

        Log::channel('payments')->info('Successful payment', ['authority' => 'A0001']);
        Log::channel('payments')->warning('Payment needs a human', ['transaction_id' => 5]);

        $this->assertStringContainsString('Successful payment', $this->read('payments.log'));
        $this->assertStringContainsString('Payment needs a human', $this->read('payments.log'));
        $this->assertStringContainsString('payments.WARNING: Payment needs a human', $this->read('laravel.log'));
        $this->assertStringNotContainsString('Successful payment', $this->read('laravel.log'));
    }

    public function test_failed_payment_attempt_is_written_to_the_payments_log(): void
    {
        $this->actingAs(\App\Models\User::factory()->create());

        app(SecurityLogService::class)->logPaymentAttempt('PAY-1', 250000, false, ['reason' => 'expired']);

        // پیش‌تر: info روی کانال security با سطح warning ← هیچ‌جا نوشته نمی‌شد
        $this->assertStringContainsString('Failed payment attempt', $this->read('payments.log'));
        $this->assertStringContainsString('PAY-1', $this->read('payments.log'));
    }
}
