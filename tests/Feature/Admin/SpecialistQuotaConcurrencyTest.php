<?php

namespace Tests\Feature\Admin;

use App\Exceptions\SpecialistQuotaExceededException;
use App\Models\BeautyService;
use App\Models\Salon;
use App\Models\Specialist;
use App\Repositories\Contracts\SpecialistRepositoryInterface;
use App\Services\Admin\Specialist\AdminSpecialistService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * دو «ساخت متخصص» هم‌زمان برای سالنی که یک جای خالی دارد: فقط یکی باید ساخته شود.
 *
 * درخواست اول بعد از شمردن متخصص‌ها، پردازه‌ی دوم (tests/Fixtures/create_specialist_in_salon.php، اتصال دیتابیس جدا) را
 * اجرا می‌کند و صبر می‌کند تا یا تمام شود یا پشت قفل بماند؛ بعد کار خودش را تمام می‌کند. بدون قفل، پردازه‌ی دوم همان
 * شمارش قدیمی را می‌بیند و هر دو از سقف رد می‌شوند.
 *
 * فقط MySQL/MariaDB: SQLite :memory: بین دو پردازه مشترک نیست. داده باید commit شود تا پردازه‌ی دوم ببیند، پس این
 * تست تراکنش RefreshDatabase را ندارد و خودش پاک می‌کند.
 */
class SpecialistQuotaConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    /** روی MySQL/MariaDB در setUp خالی می‌شود: بدون تراکنش دور تست، پردازه‌ی دوم داده‌ی commit‌شده را می‌بیند */
    protected array $connectionsToTransact = [null];

    private ?Salon $salon = null;

    protected function setUp(): void
    {
        // پیش از بوت و فقط روی MySQL/MariaDB. روی SQLite :memory: بدون تراکنش، RefreshDatabase اتصال مهاجرت‌شده را نگه
        // نمی‌داشت و تست‌های بعدی همان پردازه جدول نداشتند (no such table) — حتی وقتی این تست skip می‌شد.
        if ((getenv('DB_CONNECTION') ?: ($_ENV['DB_CONNECTION'] ?? null)) === 'mysql') {
            $this->connectionsToTransact = [];
        }

        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('هم‌زمانی واقعی بین دو پردازه فقط روی MySQL/MariaDB قابل بازتولید است.');
        }
    }

    protected function tearDown(): void
    {
        if ($this->salon) {
            Specialist::withoutGlobalScopes()->withTrashed()->where('salon_id', $this->salon->id)->forceDelete();
            BeautyService::withoutGlobalScopes()->where('salon_id', $this->salon->id)->delete();
            Salon::whereKey($this->salon->id)->delete();
        }

        parent::tearDown();
    }

    public function test_two_concurrent_creations_cannot_pass_the_specialist_limit(): void
    {
        $this->salon = Salon::factory()->create(['slug' => 'race-'.uniqid(), 'max_specialists_count' => 2]);
        app(CurrentSalon::class)->set($this->salon);
        Specialist::factory()->create(['salon_id' => $this->salon->id]);
        $service = BeautyService::factory()->create(['salon_id' => $this->salon->id]);

        $second = null;
        $real = app(SpecialistRepositoryInterface::class);
        $this->app->instance(SpecialistRepositoryInterface::class, new class($real, function () use (&$second, $service) {
            $second = $this->startSecondCreation($service->id);
        }) extends \App\Repositories\Eloquent\SpecialistRepository
        {

            private bool $fired = false;

            public function __construct(private readonly SpecialistRepositoryInterface $inner, private readonly \Closure $afterCount)
            {
                parent::__construct(new Specialist);
            }

            public function count(): int
            {
                return $this->afterFirstCount($this->inner->count());
            }

            public function countBySalonIgnoringScope(int $salonId): int
            {
                return $this->afterFirstCount($this->inner->countBySalonIgnoringScope($salonId));
            }

            private function afterFirstCount(int $count): int
            {
                if (! $this->fired) {
                    $this->fired = true;
                    ($this->afterCount)();
                }

                return $count;
            }
        });

        $first = 'created';
        try {
            app(AdminSpecialistService::class)->create(
                ['name' => 'متخصص اول', 'phone' => '09120000101', 'email' => 'race-first-'.uniqid().'@example.test', 'services' => [$service->id]],
                null,
            );
        } catch (SpecialistQuotaExceededException) {
            $first = 'quota';
        }

        $secondResult = $this->finishSecondCreation($second);
        $total = Specialist::withoutGlobalScopes()->where('salon_id', $this->salon->id)->count();

        $this->assertSame(2, $total, "سقف ۲ رد شد: اول={$first}، دوم={$secondResult}");
        $this->assertEqualsCanonicalizing(['created', 'quota'], [$first, $secondResult]);
    }

    /** @return array{0: resource, 1: array} */
    private function startSecondCreation(int $serviceId): array
    {
        $command = [PHP_BINARY, base_path('tests/Fixtures/create_specialist_in_salon.php'), (string) $this->salon->id,
            '09120000102', 'race-second-'.uniqid().'@example.test', (string) $serviceId];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());

        // صبر تا پردازه‌ی دوم تمام شود (بدون قفل) یا پشت قفل ردیف سالن بماند (با قفل) — حداکثر ۲۰ ثانیه
        $deadline = microtime(true) + 20;
        while (microtime(true) < $deadline) {
            if (! proc_get_status($process)['running']) {
                break;
            }
            // پردازه‌ی دوم پشت قفل ردیف سالن است (روی MariaDB در innodb_trx دیده نمی‌شود؛ processlist هر دو را نشان می‌دهد)
            $waiting = DB::selectOne("select count(*) as c from information_schema.processlist where id <> connection_id() and db = database() and info like '%for update%'");
            if ((int) $waiting->c > 0) {
                break;
            }
            usleep(50_000);
        }

        return [$process, $pipes];
    }

    private function finishSecondCreation(?array $second): string
    {
        $this->assertNotNull($second, 'پردازه‌ی دوم اجرا نشد');
        [$process, $pipes] = $second;
        $out = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
        proc_close($process);

        return trim($out);
    }
}
