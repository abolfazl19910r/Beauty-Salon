<?php

namespace Tests\Feature\Booking;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\IdempotencyKey;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Models\User;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Eloquent\BookingRepository;
use App\Services\Booking\BookingService;
use App\Support\CurrentSalon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\RunsConcurrentProcess;
use Tests\TestCase;

/**
 * سقف روزانه‌ی مشتری زیر هم‌زمانی (بسته‌ی 5ه، تصمیم ۱۲): همان مشتری هم‌زمان برای دو متخصص مختلف در یک روز — قفل ردیف متخصص جلویش را
 * نمی‌گیرد (دو متخصص جدا)؛ فقط قفل ردیف مشتری پیش از شمارش. دو پردازه (RunsConcurrentProcess)، فقط MySQL/MariaDB.
 */
class CustomerDailyLimitConcurrencyTest extends TestCase
{
    use RefreshDatabase;
    use RunsConcurrentProcess;

    protected array $connectionsToTransact = [null];

    private ?Salon $salon = null;

    private array $users = [];

    protected function setUp(): void
    {
        $this->withoutTransactionOnConcurrentDatabase();
        parent::setUp();
        $this->skipUnlessConcurrentDatabase();
        $this->salon = Salon::factory()->create(['slug' => 'daily-limit-race-'.uniqid()]);
        app(CurrentSalon::class)->set($this->salon);
    }

    protected function tearDown(): void
    {
        if ($this->salon) {
            Booking::withoutGlobalScopes()->where('salon_id', $this->salon->id)->delete();
            Specialist::withoutGlobalScopes()->withTrashed()->where('salon_id', $this->salon->id)->forceDelete();
            BeautyService::withoutGlobalScopes()->where('salon_id', $this->salon->id)->delete();
            IdempotencyKey::whereIn('owner_id', $this->users)->where('scope', 'booking')->delete();
            User::whereKey($this->users)->delete();
            Salon::whereKey($this->salon->id)->delete();
        }

        parent::tearDown();
    }

    public function test_two_concurrent_bookings_of_one_customer_on_one_day_cannot_both_pass_the_daily_limit(): void
    {
        $customer = User::factory()->create(['salon_id' => $this->salon->id, 'user_type' => 'customer']);
        $this->users = [$customer->id];
        $service = BeautyService::factory()->create(['salon_id' => $this->salon->id, 'duration' => 30, 'price' => 100000]);
        $day = now()->addDays(2);
        $specialists = [];
        foreach ([1, 2] as $i) {
            $specialists[$i] = Specialist::factory()->create(['salon_id' => $this->salon->id]);
            SpecialistSchedule::factory()->create(['specialist_id' => $specialists[$i]->id, 'day_of_week' => $day->dayOfWeek, 'start_time' => '08:00', 'end_time' => '20:00', 'is_active' => true]);
        }
        $at = fn (string $t) => $day->format('Y-m-d').' '.$t.':00';

        // درخواست دوم همان مشتری (متخصص دیگر، ساعت دیگر، همان روز) وقتی شروع می‌شود که نوبت اول شمرده و درج شده ولی هنوز commit نشده
        $this->app->instance(BookingRepositoryInterface::class, new class(fn () => $this->startConcurrent('create-booking', (string) $this->salon->id, (string) $customer->id, (string) $service->id, (string) $specialists[2]->id, $at('15:00'), (string) Str::uuid())) extends BookingRepository
        {
            private bool $fired = false;

            public function __construct(private readonly \Closure $then)
            {
                parent::__construct(new Booking);
            }

            public function create(array $data): Model
            {
                if (! $this->fired) {
                    $this->fired = true;
                    ($this->then)();
                }

                return parent::create($data);
            }
        });

        app(BookingService::class)->createBooking($customer->id, $service->id, $specialists[1]->id, $at('10:00'));
        $second = $this->finishConcurrent();

        $this->assertSame(1, Booking::withoutGlobalScopes()->where('user_id', $customer->id)->count(), "second={$second}");
        $this->assertSame('limit', $second);
    }
}
