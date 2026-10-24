<?php

namespace Tests\Feature\Console;

use App\Models\SecurityLog;
use App\Support\Retention;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * نگهداری داده و پاک‌سازی خودکار (بسته‌ی مجوز 5د؛ ماده‌ی ۶۶۸ آیین دادرسی کیفری): IP و هویت دست‌کم شش ماه، بعد خودکار پاک.
 */
class RetentionPruneTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = storage_path('framework/testing/retention-'.uniqid());
        File::ensureDirectoryExists($this->dir);
        config(['retention.access_log_path' => $this->dir]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    public function test_the_default_is_six_months_and_cannot_be_set_below_the_legal_minimum(): void
    {
        $this->assertSame(190, Retention::days('security_logs_days'));

        config(['retention.security_logs_days' => 30]);
        $this->assertSame(184, Retention::days('security_logs_days'), 'کمتر از شش ماه پذیرفته نمی‌شود');

        config(['retention.security_logs_days' => 400]);
        $this->assertSame(400, Retention::days('security_logs_days'));
    }

    public function test_security_log_file_keeps_six_months_not_ninety_days(): void
    {
        // 🐞 پیش‌تر 'days' => 90
        $this->assertGreaterThanOrEqual(184, config('logging.channels.security.days'));
    }

    public function test_old_security_log_rows_are_deleted_and_recent_ones_kept(): void
    {
        $old = SecurityLog::forceCreate(['event' => 'login_attempt', 'ip_address' => '5.1.1.1', 'created_at' => now()->subDays(191)]);
        $recent = SecurityLog::forceCreate(['event' => 'login_attempt', 'ip_address' => '5.1.1.2', 'created_at' => now()->subDays(189)]);

        $this->artisan('retention:prune')->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
    }

    public function test_dry_run_deletes_nothing(): void
    {
        $old = SecurityLog::forceCreate(['event' => 'x', 'created_at' => now()->subYear()]);
        file_put_contents($this->dir.'/access-2020-01-01.log', 'x');

        $this->artisan('retention:prune --dry-run')->assertSuccessful();

        $this->assertModelExists($old);
        $this->assertFileExists($this->dir.'/access-2020-01-01.log');
    }

    public function test_access_logs_are_compressed_after_a_day_and_deleted_after_the_period(): void
    {
        $day = fn (int $ago) => now('UTC')->subDays($ago)->format('Y-m-d');
        $line = '{"ip":"5.1.1.1"}'."\n";
        foreach ([0, 1, 2, 100] as $ago) {
            file_put_contents("{$this->dir}/access-{$day($ago)}.log", str_repeat($line, 50));
        }
        file_put_contents("{$this->dir}/access-{$day(200)}.log", $line);
        file_put_contents("{$this->dir}/access-{$day(300)}.log.gz", gzencode($line));
        file_put_contents("{$this->dir}/other.log", 'keep');

        $this->artisan('retention:prune')->assertSuccessful();

        $this->assertFileExists("{$this->dir}/access-{$day(0)}.log", 'امروز');
        $this->assertFileExists("{$this->dir}/access-{$day(1)}.log", 'دیروز: nginx شاید هنوز بنویسد');
        $this->assertFileDoesNotExist("{$this->dir}/access-{$day(2)}.log");
        $this->assertSame(str_repeat($line, 50), gzdecode(file_get_contents("{$this->dir}/access-{$day(2)}.log.gz")));
        $this->assertFileExists("{$this->dir}/access-{$day(100)}.log.gz");
        $this->assertFileDoesNotExist("{$this->dir}/access-{$day(200)}.log");
        $this->assertFileDoesNotExist("{$this->dir}/access-{$day(300)}.log.gz");
        $this->assertFileExists("{$this->dir}/other.log", 'فقط فایل‌های access-تاریخ');
        $this->assertSame([], glob($this->dir.'/*.tmp'));
    }

    public function test_it_runs_every_night(): void
    {
        $this->artisan('schedule:list')->assertSuccessful();
        $event = collect(app(Schedule::class)->events())->first(fn ($e) => str_contains((string) $e->command, 'retention:prune'));

        $this->assertNotNull($event);
        $this->assertSame('50 3 * * *', $event->expression);
    }
}
