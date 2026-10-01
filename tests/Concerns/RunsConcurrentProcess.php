<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * هم‌زمانی واقعی برای تست: درخواست اول (همین پردازه) در یک نقطه‌ی میانی، پردازه‌ی دوم
 * (tests/Fixtures/concurrent_action.php، اتصال دیتابیس جدا) را اجرا می‌کند و صبر می‌کند تا یا تمام شود یا پشت یک قفل
 * بماند؛ بعد کار خودش را تمام می‌کند. بدون قفل، پردازه‌ی دوم داده‌ی قدیمی را می‌بیند.
 *
 * فقط MySQL/MariaDB: SQLite :memory: بین دو پردازه مشترک نیست. داده باید commit شود تا پردازه‌ی دوم ببیند، پس روی
 * MySQL تست تراکنش RefreshDatabase را ندارد و ردیف‌هایش را خودش پاک می‌کند. کلاس تست:
 *   protected array $connectionsToTransact = [null];
 *   setUp(): $this->withoutTransactionOnConcurrentDatabase(); parent::setUp(); $this->skipUnlessConcurrentDatabase();
 */
trait RunsConcurrentProcess
{
    /** @var array{0: resource, 1: array}|null */
    private ?array $concurrent = null;

    /**
     * پیش از parent::setUp. فقط روی MySQL/MariaDB تراکنش دور تست برداشته می‌شود. روی SQLite :memory: بدون تراکنش،
     * RefreshDatabase اتصال مهاجرت‌شده را نگه نمی‌داشت و تست‌های بعدی همان پردازه جدول نداشتند (no such table) — حتی
     * وقتی این تست skip می‌شد.
     */
    protected function withoutTransactionOnConcurrentDatabase(): void
    {
        if ((getenv('DB_CONNECTION') ?: ($_ENV['DB_CONNECTION'] ?? null)) === 'mysql') {
            $this->connectionsToTransact = [];
        }
    }

    protected function skipUnlessConcurrentDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('هم‌زمانی واقعی بین دو پردازه فقط روی MySQL/MariaDB قابل بازتولید است.');
        }
    }

    protected function startConcurrent(string $action, string ...$args): void
    {
        $command = [PHP_BINARY, base_path('tests/Fixtures/concurrent_action.php'), $action, ...$args];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());

        // تا ۲۰ ثانیه: یا پردازه‌ی دوم تمام شود، یا کوئری‌ای از اتصال دیگر روی همین دیتابیس دست‌کم ۱ ثانیه منتظر بماند
        // (یعنی پشت قفل ردیف؛ روی MariaDB انتظار SELECT ... FOR UPDATE در innodb_trx دیده نمی‌شود، processlist هر دو را دارد)
        $deadline = microtime(true) + 20;
        while (microtime(true) < $deadline && proc_get_status($process)['running']) {
            $blocked = DB::selectOne('select count(*) as c from information_schema.processlist
                where id <> connection_id() and db = database() and info is not null and time >= 1');
            if ((int) $blocked->c > 0) {
                break;
            }
            usleep(50_000);
        }

        $this->concurrent = [$process, $pipes];
    }

    protected function finishConcurrent(): string
    {
        $this->assertNotNull($this->concurrent, 'پردازه‌ی دوم اجرا نشد');
        [$process, $pipes] = $this->concurrent;
        $out = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
        proc_close($process);
        $this->concurrent = null;

        return trim($out);
    }
}
