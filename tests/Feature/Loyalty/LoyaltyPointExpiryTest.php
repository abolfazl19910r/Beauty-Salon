<?php

namespace Tests\Feature\Loyalty;

use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\User;
use App\Services\Loyalty\LoyaltyLedger;
use App\Services\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * انقضای امتیاز باشگاه مشتریان (تصمیم ۲۰۲۶-۱۰-۱۱، گزینه‌ی «الف»). باگ: امتیاز منقضی‌شده همچنان خرج می‌شد — هیچ‌چیز انقضا را
 * ثبت نمی‌کرد و چک پاداش کل ردیف‌ها را جمع می‌زد.
 */
class LoyaltyPointExpiryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->user = User::factory()->create();
    }

    private function earn(int $points, ?string $expiresAt, string $description = 'نوبت'): LoyaltyPoint
    {
        return LoyaltyPoint::create(['user_id' => $this->user->id, 'points' => $points, 'type' => 'earned', 'description' => $description,
            'expires_at' => $expiresAt ? now()->modify($expiresAt) : null]);
    }

    private function spend(int $points): void
    {
        LoyaltyPoint::create(['user_id' => $this->user->id, 'points' => -$points, 'type' => 'spent', 'description' => 'پاداش']);
    }

    private function ledger(): LoyaltyLedger
    {
        return app(LoyaltyLedger::class);
    }

    public function test_expired_points_are_not_part_of_the_balance_even_before_the_command_runs(): void
    {
        $this->earn(100, '-1 day');
        $this->earn(40, '+2 months');

        $this->assertSame(40, $this->ledger()->balance($this->user->id));
        $this->assertSame(40, $this->user->getTotalLoyaltyPoints());
        $this->assertSame(40, app(LoyaltyService::class)->getCurrentPoints($this->user->id));
        $this->assertSame(40, LoyaltyPoint::getCurrentBalance($this->user->id));
    }

    public function test_spending_uses_the_points_that_expire_first(): void
    {
        $this->earn(100, '+10 days');
        $this->earn(100, '+6 months');
        $this->spend(120);

        // ۱۰۰ زودتر منقضی‌شونده کامل و ۲۰ از بعدی خرج شد؛ ۱۰ روز دیگر چیزی از بسته‌ی اول نمی‌سوزد
        $this->assertSame(80, $this->ledger()->balance($this->user->id));
        $this->assertSame(0, $this->ledger()->expiringSoon($this->user->id, 30));

        $this->travel(11)->days();
        $this->assertSame(80, $this->ledger()->balance($this->user->id));
    }

    public function test_the_command_records_only_the_unspent_rest_and_is_idempotent(): void
    {
        $this->earn(100, '+5 days', 'نوبت #7');
        $this->earn(50, null);
        $this->spend(30);
        $this->travel(6)->days();

        $this->artisan('loyalty:expire-points')->assertSuccessful();
        $this->artisan('loyalty:expire-points')->assertSuccessful();

        $expired = LoyaltyPoint::where('type', 'expired')->get();
        $this->assertCount(1, $expired);
        $this->assertSame(-70, (int) $expired[0]->points);
        $this->assertStringContainsString('نوبت #7', $expired[0]->description);
        $this->assertSame(50, $this->ledger()->balance($this->user->id));
        $this->assertSame(50, (int) LoyaltyPoint::where('user_id', $this->user->id)->sum('points'), 'جمع خام ردیف‌ها با موجودی یکی شد');
    }

    public function test_expiring_soon_counts_only_unspent_points(): void
    {
        $this->earn(100, '+20 days');
        $this->spend(60);

        $this->assertSame(40, $this->ledger()->expiringSoon($this->user->id, 30));
        $this->assertSame(0, $this->ledger()->expiringSoon($this->user->id, 10));
    }

    public function test_a_reward_cannot_be_redeemed_with_expired_points(): void
    {
        $this->earn(150, '-1 day');
        $this->earn(20, '+1 month');
        $reward = Reward::factory()->create(['required_points' => 100, 'max_uses' => null, 'is_active' => true]);

        $this->assertFalse($reward->isAvailableForUser($this->user));
        $this->expectExceptionMessage('امتیاز کافی نیست');
        app(LoyaltyService::class)->redeemReward($this->user->id, $reward);
    }

    public function test_redeeming_records_the_expiry_first_so_the_history_explains_the_balance(): void
    {
        $this->earn(50, '-1 day');
        $this->earn(120, '+1 month');
        $reward = Reward::factory()->create(['required_points' => 100, 'max_uses' => null, 'is_active' => true]);

        app(LoyaltyService::class)->redeemReward($this->user->id, $reward);

        $this->assertSame(-50, (int) LoyaltyPoint::where('type', 'expired')->sum('points'));
        $this->assertSame(20, $this->ledger()->balance($this->user->id));
    }
}
