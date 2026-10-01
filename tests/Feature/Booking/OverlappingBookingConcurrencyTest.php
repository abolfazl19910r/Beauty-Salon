<?php

namespace Tests\Feature\Booking;

use App\Exceptions\BookingNotAvailableException;
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
 * دو مشتری هم‌زمان، دو ساعت شروع متفاوت ولی هم‌پوشان (۱۰:۰۰ خدمت ۶۰ دقیقه‌ای و ۱۰:۳۰): قید یکتای active_slot جلویش را
 * نمی‌گیرد؛ فقط قفل ردیف متخصص + سنجش کل بازه زیر قفل. دو پردازه (RunsConcurrentProcess)، فقط MySQL/MariaDB.
 */
class OverlappingBookingConcurrencyTest extends TestCase
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
        $this->salon = Salon::factory()->create(['slug' => 'overlap-race-'.uniqid()]);
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

    public function test_two_concurrent_overlapping_bookings_with_different_start_times_cannot_both_be_created(): void
    {
        [$a, $b] = [User::factory()->create(['salon_id' => $this->salon->id]), User::factory()->create(['salon_id' => $this->salon->id])];
        $this->users = [$a->id, $b->id];
        $long = BeautyService::factory()->create(['salon_id' => $this->salon->id, 'duration' => 60, 'price' => 100000]);
        $short = BeautyService::factory()->create(['salon_id' => $this->salon->id, 'duration' => 30, 'price' => 100000]);
        $specialist = Specialist::factory()->create(['salon_id' => $this->salon->id]);
        $day = now()->addDays(2);
        SpecialistSchedule::factory()->create(['specialist_id' => $specialist->id, 'day_of_week' => $day->dayOfWeek, 'start_time' => '08:00', 'end_time' => '20:00', 'is_active' => true]);
        $at = fn (string $t) => $day->format('Y-m-d').' '.$t.':00';

        // مشتری ب ساعت ۱۰:۳۰ را وقتی می‌گیرد که نوبت ۱۰:۰۰ مشتری الف (۶۰ دقیقه) هنوز commit نشده
        $this->app->instance(BookingRepositoryInterface::class, new class(fn () => $this->startConcurrent('create-booking', (string) $this->salon->id, (string) $b->id, (string) $short->id, (string) $specialist->id, $at('10:30'), (string) Str::uuid())) extends BookingRepository
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

        try {
            app(BookingService::class)->createBooking($a->id, $long->id, $specialist->id, $at('10:00'));
            $first = 'created';
        } catch (BookingNotAvailableException) {
            $first = 'slot-taken';
        }
        $second = $this->finishConcurrent();

        $this->assertSame(1, Booking::withoutGlobalScopes()->where('specialist_id', $specialist->id)->count(), "first={$first} second={$second}");
        $this->assertSame(['created', 'slot-taken'], [$first, $second]);
    }
}
