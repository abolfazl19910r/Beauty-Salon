<?php

namespace Tests\Feature\Idempotency;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\IdempotencyKey;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Models\User;
use App\Models\WalletSetting;
use App\Models\WithdrawalRequest;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\WithdrawalRequestRepositoryInterface;
use App\Repositories\Eloquent\BookingRepository;
use App\Repositories\Eloquent\WithdrawalRequestRepository;
use App\Services\Booking\BookingService;
use App\Services\Specialist\SpecialistWalletService;
use App\Support\CurrentSalon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\RunsConcurrentProcess;
use Tests\TestCase;

/**
 * دو ارسال هم‌زمان همان فرم (همان idempotency_key) در دو پردازه: یک رکورد، و هر دو پاسخ همان رکورد را برمی‌گردانند.
 * برداشت با قفل کیف پول سریالی می‌شود؛ نوبت قفلی ندارد و درج کلید روی ایندکس یکتا دومی را منتظر نگه می‌دارد.
 */
class ConcurrentDoubleSubmitTest extends TestCase
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
        $this->salon = Salon::factory()->create(['slug' => 'idem-race-'.uniqid()]);
        app(CurrentSalon::class)->set($this->salon);
    }

    protected function tearDown(): void
    {
        if ($this->salon) {
            Booking::withoutGlobalScopes()->where('salon_id', $this->salon->id)->delete();
            Specialist::withoutGlobalScopes()->withTrashed()->where('salon_id', $this->salon->id)->forceDelete();
            BeautyService::withoutGlobalScopes()->where('salon_id', $this->salon->id)->delete();
            DB::table('wallet_settings')->where('salon_id', $this->salon->id)->delete();
            IdempotencyKey::whereIn('scope', ['booking', 'withdrawal'])->delete();
            User::whereKey($this->users)->delete();
            Salon::whereKey($this->salon->id)->delete();
        }

        parent::tearDown();
    }

    private function pauseOnFirstWithdrawalCreate(\Closure $then): void
    {
        $this->app->instance(WithdrawalRequestRepositoryInterface::class, new class($then) extends WithdrawalRequestRepository
        {
            private bool $fired = false;

            public function __construct(private readonly \Closure $then)
            {
                parent::__construct(new WithdrawalRequest);
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
    }

    private function pauseOnFirstBookingCreate(\Closure $then): void
    {
        $this->app->instance(BookingRepositoryInterface::class, new class($then) extends BookingRepository
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
    }

    public function test_two_concurrent_submits_of_one_withdrawal_form_create_one_request(): void
    {
        WalletSetting::get()->update(['minimum_withdrawal_amount' => 10000, 'maximum_withdrawal_amount' => 50000000, 'withdrawal_fee_percentage' => 0]);
        $specialist = Specialist::factory()->create(['salon_id' => $this->salon->id]);
        $wallet = $specialist->getOrCreateWallet();
        $wallet->update(['balance' => 500000, 'iban' => 'IR062960000000100324200001', 'account_holder_name' => 'متخصص']);
        $key = (string) Str::uuid();

        $this->pauseOnFirstWithdrawalCreate(fn () => $this->startConcurrent('create-withdrawal', (string) $specialist->id, '100000', $key));

        $first = app(SpecialistWalletService::class)->createWithdrawal($specialist->fresh(), ['amount' => 100000, 'method' => 'iban', 'idempotency_key' => $key]);
        $second = $this->finishConcurrent();

        $this->assertSame(1, WithdrawalRequest::where('wallet_id', $wallet->id)->count(), 'second='.$second);
        $this->assertSame(400000.0, (float) $wallet->fresh()->balance);
        $this->assertSame('created:'.$first['withdrawal_request']->id, $second);
    }

    public function test_two_concurrent_submits_of_one_booking_form_create_one_booking(): void
    {
        $user = User::factory()->create(['user_type' => 'customer', 'salon_id' => $this->salon->id]);
        $this->users[] = $user->id;
        $service = BeautyService::factory()->create(['salon_id' => $this->salon->id, 'price' => 200000, 'duration' => 30]);
        $specialist = Specialist::factory()->create(['salon_id' => $this->salon->id]);
        $target = now()->addDay()->setTime(10, 0);
        SpecialistSchedule::factory()->create(['specialist_id' => $specialist->id, 'day_of_week' => $target->dayOfWeek, 'start_time' => '08:00', 'end_time' => '20:00', 'is_active' => true]);
        $time = $target->format('Y-m-d H:i:s');
        $key = (string) Str::uuid();

        $this->pauseOnFirstBookingCreate(fn () => $this->startConcurrent('create-booking', (string) $this->salon->id, (string) $user->id, (string) $service->id, (string) $specialist->id, $time, $key));

        $first = app(BookingService::class)->createBooking($user->id, $service->id, $specialist->id, $time, null, $key);
        $second = $this->finishConcurrent();

        $this->assertSame(1, Booking::withoutGlobalScopes()->where('user_id', $user->id)->count(), 'second='.$second);
        $this->assertSame('booking:'.$first->id, $second);
    }
}
