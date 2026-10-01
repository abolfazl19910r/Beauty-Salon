<?php

namespace Tests\Feature\Wallet;

use App\Models\Salon;
use App\Models\Specialist;
use App\Models\SpecialistWallet;
use App\Models\WalletSetting;
use App\Models\WithdrawalRequest;
use App\Repositories\Contracts\WithdrawalRequestRepositoryInterface;
use App\Repositories\Eloquent\WithdrawalRequestRepository;
use App\Services\Specialist\SpecialistWalletService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\RunsConcurrentProcess;
use Tests\TestCase;

/**
 * برداشت متخصص در برابر دو درخواست هم‌زمان (دو پردازه، RunsConcurrentProcess): موجودی هرگز دو بار خرج نشود و لغو
 * هرگز دو بار پول برنگرداند.
 */
class WithdrawalConcurrencyTest extends TestCase
{
    use RefreshDatabase;
    use RunsConcurrentProcess;

    protected array $connectionsToTransact = [null];

    private ?Salon $salon = null;

    private Specialist $specialist;

    private SpecialistWallet $wallet;

    protected function setUp(): void
    {
        $this->withoutTransactionOnConcurrentDatabase();
        parent::setUp();
        $this->skipUnlessConcurrentDatabase();

        $this->salon = Salon::factory()->create(['slug' => 'wd-race-'.uniqid()]);
        app(CurrentSalon::class)->set($this->salon);
        WalletSetting::get()->update(['minimum_withdrawal_amount' => 10000, 'maximum_withdrawal_amount' => 50000000, 'withdrawal_fee_percentage' => 0]);
        $this->specialist = Specialist::factory()->create(['salon_id' => $this->salon->id]);
        $this->wallet = $this->specialist->getOrCreateWallet();
        $this->wallet->update(['balance' => 100000, 'iban' => 'IR062960000000100324200001', 'account_holder_name' => 'متخصص']);
    }

    protected function tearDown(): void
    {
        if ($this->salon) {
            // کیف پول، تراکنش‌ها و برداشت‌ها با متخصص cascade می‌شوند
            Specialist::withoutGlobalScopes()->withTrashed()->where('salon_id', $this->salon->id)->forceDelete();
            DB::table('wallet_settings')->where('salon_id', $this->salon->id)->delete();
            Salon::whereKey($this->salon->id)->delete();
        }

        parent::tearDown();
    }

    /** نقطه‌ی مکث: اولین فراخوانی repository برداشت ($method) در درخواست اول، پردازه‌ی دوم را اجرا می‌کند */
    private function pauseOnFirst(string $method, \Closure $then): void
    {
        $this->app->instance(WithdrawalRequestRepositoryInterface::class, new class($method, $then) extends WithdrawalRequestRepository
        {
            private bool $fired = false;

            public function __construct(private readonly string $method, private readonly \Closure $then)
            {
                parent::__construct(new WithdrawalRequest);
            }

            private function fire(string $method): void
            {
                if (! $this->fired && $method === $this->method) {
                    $this->fired = true;
                    ($this->then)();
                }
            }

            public function create(array $data): \Illuminate\Database\Eloquent\Model
            {
                $this->fire('create');

                return parent::create($data);
            }

            public function update(\Illuminate\Database\Eloquent\Model $model, array $data): \Illuminate\Database\Eloquent\Model
            {
                $this->fire('update');

                return parent::update($model, $data);
            }
        });
    }

    public function test_two_concurrent_withdrawals_cannot_spend_the_same_balance_twice(): void
    {
        $this->pauseOnFirst('create', fn () => $this->startConcurrent('create-withdrawal', (string) $this->specialist->id, '100000'));

        $first = app(SpecialistWalletService::class)->createWithdrawal($this->specialist->fresh(), ['amount' => 100000, 'method' => 'iban']);
        $second = $this->finishConcurrent();

        $requests = WithdrawalRequest::where('wallet_id', $this->wallet->id)->count();
        $balance = (float) $this->wallet->fresh()->balance;

        $this->assertSame(1, $requests, 'second='.$second);
        $this->assertSame(0.0, $balance, 'موجودی منفی شد: '.$balance);
        $this->assertTrue($first['success']);
        $this->assertSame('rejected', $second);
    }

    public function test_two_concurrent_cancellations_refund_only_once(): void
    {
        $withdrawal = app(SpecialistWalletService::class)
            ->createWithdrawal($this->specialist->fresh(), ['amount' => 60000, 'method' => 'iban'])['withdrawal_request'];
        $this->assertSame(40000.0, (float) $this->wallet->fresh()->balance);

        $this->pauseOnFirst('update', fn () => $this->startConcurrent('cancel-withdrawal', (string) $this->specialist->id, (string) $withdrawal->id));

        app(SpecialistWalletService::class)->cancelWithdrawal($this->specialist->fresh(), WithdrawalRequest::find($withdrawal->id));
        $second = $this->finishConcurrent();

        $this->assertSame(100000.0, (float) $this->wallet->fresh()->balance, 'second='.$second);
        $this->assertSame(1, DB::table('wallet_transactions')->where('wallet_id', $this->wallet->id)->where('type', 'refund')->count());
        $this->assertSame('not-cancelled', $second);
    }
}
