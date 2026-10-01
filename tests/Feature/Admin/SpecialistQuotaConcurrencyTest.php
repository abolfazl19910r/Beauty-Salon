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
use Tests\Concerns\RunsConcurrentProcess;
use Tests\TestCase;

/**
 * دو «ساخت متخصص» هم‌زمان برای سالنی که یک جای خالی دارد: فقط یکی باید ساخته شود.
 *
 * درخواست اول بعد از شمردن متخصص‌ها درخواست دوم را در پردازه‌ی جدا اجرا می‌کند (RunsConcurrentProcess). بدون قفل، دومی همان
 * شمارش قدیمی را می‌بیند و هر دو از سقف رد می‌شوند. همچنین کم کردن سقف توسط سوپرادمین هم‌زمان با ساخت متخصص.
 */
class SpecialistQuotaConcurrencyTest extends TestCase
{
    use RefreshDatabase;
    use RunsConcurrentProcess;

    /** روی MySQL/MariaDB در setUp خالی می‌شود: بدون تراکنش دور تست، پردازه‌ی دوم داده‌ی commit‌شده را می‌بیند */
    protected array $connectionsToTransact = [null];

    private ?Salon $salon = null;

    protected function setUp(): void
    {
        $this->withoutTransactionOnConcurrentDatabase();
        parent::setUp();

        $this->skipUnlessConcurrentDatabase();
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

        $this->pauseAfterFirstSpecialistCount(fn () => $this->startConcurrent(
            'create-specialist', (string) $this->salon->id, '09120000102', 'race-second-'.uniqid().'@example.test', (string) $service->id,
        ));

        $first = $this->createFirstSpecialist($service->id);
        $secondResult = $this->finishConcurrent();
        $total = Specialist::withoutGlobalScopes()->where('salon_id', $this->salon->id)->count();

        $this->assertSame(2, $total, "سقف ۲ رد شد: اول={$first}، دوم={$secondResult}");
        $this->assertEqualsCanonicalizing(['created', 'quota'], [$first, $secondResult]);
    }

    public function test_lowering_the_limit_while_a_specialist_is_being_created_cannot_leave_the_salon_over_its_limit(): void
    {
        $this->salon = Salon::factory()->create(['slug' => 'race-'.uniqid(), 'max_specialists_count' => 3]);
        app(CurrentSalon::class)->set($this->salon);
        Specialist::factory()->count(2)->create(['salon_id' => $this->salon->id]);
        $service = BeautyService::factory()->create(['salon_id' => $this->salon->id]);

        // سوپرادمین سقف را به ۲ (تعداد فعلی) کم می‌کند، درست وقتی مدیر سالن متخصص سوم را می‌سازد
        $this->pauseAfterFirstSpecialistCount(fn () => $this->startConcurrent('update-salon-limit', (string) $this->salon->id, '2'));

        $first = $this->createFirstSpecialist($service->id);
        $update = $this->finishConcurrent();
        $total = Specialist::withoutGlobalScopes()->where('salon_id', $this->salon->id)->count();
        $max = Salon::whereKey($this->salon->id)->value('max_specialists_count');

        $this->assertLessThanOrEqual($max, $total, "سالن {$total} متخصص با سقف {$max} دارد (ساخت={$first}، کاهش سقف={$update})");
        $this->assertSame(['created', 'invalid'], [$first, $update]);
    }

    private function createFirstSpecialist(int $serviceId): string
    {
        try {
            app(AdminSpecialistService::class)->create(
                ['name' => 'متخصص اول', 'phone' => '09120000101', 'email' => 'race-first-'.uniqid().'@example.test', 'services' => [$serviceId]],
                null,
            );

            return 'created';
        } catch (SpecialistQuotaExceededException) {
            return 'quota';
        }
    }

    private function pauseAfterFirstSpecialistCount(\Closure $then): void
    {
        $real = app(SpecialistRepositoryInterface::class);
        $this->app->instance(SpecialistRepositoryInterface::class, new class($real, $then) extends \App\Repositories\Eloquent\SpecialistRepository
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
    }
}
