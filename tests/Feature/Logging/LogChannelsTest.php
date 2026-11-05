<?php

namespace Tests\Feature\Logging;

use App\Logging\MaskPersonalData;
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
}
