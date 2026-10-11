<?php

namespace Tests\Feature\Loyalty;

use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\Salon;
use App\Models\User;
use App\Repositories\Contracts\LoyaltyPointRepositoryInterface;
use App\Repositories\Eloquent\LoyaltyPointRepository;
use App\Services\LoyaltyService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\RunsConcurrentProcess;
use Tests\TestCase;

/**
 * دو درخواست هم‌زمان «دریافت پاداش» (دو بار زدن دکمه، یا وب و اپ) با امتیاز کافی برای فقط یکی (۲۰۲۶-۱۰-۱۱).
 * قبلاً چک امتیاز بیرون از تراکنش و بدون قفل بود: هر دو رد می‌شدند، امتیاز منفی و دو کد تخفیف.
 */
class RewardRedeemConcurrencyTest extends TestCase
{
    use RefreshDatabase;
    use RunsConcurrentProcess;

    protected array $connectionsToTransact = [null];

    private ?Salon $salon = null;

    protected function setUp(): void
    {
        $this->withoutTransactionOnConcurrentDatabase();
        parent::setUp();
        $this->skipUnlessConcurrentDatabase();
        Notification::fake();

        $this->salon = Salon::factory()->create(['slug' => 'redeem-race-'.uniqid()]);
        app(CurrentSalon::class)->set($this->salon);
    }

    protected function tearDown(): void
    {
        if ($this->salon) {
            $userIds = User::where('salon_id', $this->salon->id)->pluck('id');
            DB::table('discount_codes')->whereIn('user_id', $userIds)->delete();
            DB::table('loyalty_points')->whereIn('user_id', $userIds)->delete();
            DB::table('rewards')->where('salon_id', $this->salon->id)->delete();
            User::whereIn('id', $userIds)->delete();
            Salon::whereKey($this->salon->id)->delete();
        }

        parent::tearDown();
    }

    public function test_two_simultaneous_redemptions_spend_the_points_only_once(): void
    {
        $user = User::factory()->create(['salon_id' => $this->salon->id, 'user_type' => 'customer']);
        LoyaltyPoint::create(['user_id' => $user->id, 'points' => 100, 'type' => 'earned', 'description' => 'test']);
        $reward = Reward::factory()->create(['required_points' => 100, 'max_uses' => null, 'is_active' => true]);

        $salonId = $this->salon->id;
        $this->app->instance(LoyaltyPointRepositoryInterface::class, new class(fn () => $this->startConcurrent('redeem-reward', (string) $salonId, (string) $user->id, (string) $reward->id)) extends LoyaltyPointRepository
        {
            private bool $fired = false;

            public function __construct(private readonly \Closure $then)
            {
                parent::__construct(new LoyaltyPoint);
            }

            public function create(array $data): \Illuminate\Database\Eloquent\Model
            {
                if (! $this->fired) {
                    $this->fired = true;
                    ($this->then)();
                }

                return parent::create($data);
            }
        });

        $first = app(LoyaltyService::class)->redeemReward($user->id, $reward);
        $second = $this->finishConcurrent();

        $this->assertNotNull($first->code);
        $this->assertSame('rejected', $second);
        $this->assertSame(0, (int) LoyaltyPoint::where('user_id', $user->id)->sum('points'));
        $this->assertSame(1, DB::table('discount_codes')->where('user_id', $user->id)->count());
    }
}
