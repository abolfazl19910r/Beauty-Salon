<?php

namespace Tests\Feature\Loyalty;

use App\Exceptions\InsufficientLoyaltyPointsException;
use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\User;
use App\Services\Admin\Loyalty\LoyaltyAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyAdminServiceTest extends TestCase
{
    use RefreshDatabase;

    private LoyaltyAdminService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(LoyaltyAdminService::class);
    }

    // ── Reward CRUD ──────────────────────────────────────────────────────

    public function test_create_reward(): void
    {
        $reward = $this->service->createReward([
            'title' => 'تخفیف ویژه',
            'description' => 'توضیحات',
            'required_points' => 500,
            'discount_type' => 'fixed',
            'discount_amount' => 20000,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('rewards', ['title' => 'تخفیف ویژه', 'required_points' => 500]);
        $this->assertSame(500, $reward->required_points);
    }

    public function test_update_reward(): void
    {
        $reward = Reward::factory()->create(['title' => 'قدیمی']);

        $this->service->updateReward($reward, ['title' => 'جدید']);

        $this->assertSame('جدید', $reward->fresh()->title);
    }

    public function test_delete_an_unused_reward(): void
    {
        $reward = Reward::factory()->create(['used_count' => 0]);

        $this->service->deleteReward($reward);

        $this->assertDatabaseMissing('rewards', ['id' => $reward->id]);
    }

    public function test_delete_a_used_reward_throws(): void
    {
        $reward = Reward::factory()->create(['used_count' => 5]);

        $this->expectException(\Exception::class);

        $this->service->deleteReward($reward);
        $this->assertDatabaseHas('rewards', ['id' => $reward->id]);
    }

    // ── redeemRewardForUser() ────────────────────────────────────────────

    public function test_redeem_reward_for_user_operates_on_the_target_user_not_the_logged_in_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $targetUser = User::factory()->create();
        LoyaltyPoint::create(['user_id' => $targetUser->id, 'points' => 1000, 'type' => 'earned', 'description' => 'seed']);
        $reward = Reward::factory()->create(['required_points' => 500, 'is_active' => true]);

        $this->actingAs($admin);
        $discountCode = $this->service->redeemRewardForUser($targetUser->id, $reward);

        $this->assertSame($targetUser->id, $discountCode->user_id);
        $this->assertDatabaseHas('loyalty_points', ['user_id' => $targetUser->id, 'type' => 'spent']);
        $this->assertDatabaseMissing('loyalty_points', ['user_id' => $admin->id]);
    }

    public function test_redeem_reward_for_user_fails_when_target_user_has_insufficient_points(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $targetUser = User::factory()->create();
        LoyaltyPoint::create(['user_id' => $targetUser->id, 'points' => 100, 'type' => 'earned', 'description' => 'seed']);
        $reward = Reward::factory()->create(['required_points' => 500, 'is_active' => true]);

        $this->actingAs($admin);

        $this->expectException(\Exception::class);
        $this->service->redeemRewardForUser($targetUser->id, $reward);
    }

    // ── addPoints() / deductPoints() ─────────────────────────────────────

    public function test_add_points_creates_an_earned_entry_and_clears_the_nav_cache(): void
    {
        $user = User::factory()->create();
        \Illuminate\Support\Facades\Cache::put("user:{$user->id}:loyalty_points", 999, 300);

        $point = $this->service->addPoints($user, 250, 'جایزه تولد');

        $this->assertSame(250, $point->points);
        $this->assertSame('earned', $point->type);
        $this->assertFalse(\Illuminate\Support\Facades\Cache::has("user:{$user->id}:loyalty_points"));
    }

    public function test_add_points_sets_expiry_to_end_of_the_given_day(): void
    {
        $user = User::factory()->create();

        $point = $this->service->addPoints($user, 100, 'test', '2027-01-15');

        $this->assertSame('2027-01-15 23:59:59', $point->expires_at->toDateTimeString());
    }

    public function test_deduct_points_creates_a_negative_spent_entry(): void
    {
        $user = User::factory()->create();
        LoyaltyPoint::create(['user_id' => $user->id, 'points' => 500, 'type' => 'earned', 'description' => 'seed']);

        $point = $this->service->deductPoints($user, 200, 'جریمه');

        $this->assertSame(-200, $point->points);
        $this->assertSame('spent', $point->type);
        $this->assertSame(300, (int) LoyaltyPoint::where('user_id', $user->id)->sum('points'));
    }

    public function test_deduct_points_throws_when_balance_is_insufficient(): void
    {
        $user = User::factory()->create();
        LoyaltyPoint::create(['user_id' => $user->id, 'points' => 50, 'type' => 'earned', 'description' => 'seed']);

        $this->expectException(InsufficientLoyaltyPointsException::class);

        $this->service->deductPoints($user, 200, 'test');
    }

    public function test_deduct_points_does_not_create_a_record_when_it_throws(): void
    {
        $user = User::factory()->create();
        LoyaltyPoint::create(['user_id' => $user->id, 'points' => 50, 'type' => 'earned', 'description' => 'seed']);

        try {
            $this->service->deductPoints($user, 200, 'test');
        } catch (InsufficientLoyaltyPointsException $e) {
            // expected
        }

        $this->assertSame(1, LoyaltyPoint::where('user_id', $user->id)->count());
    }

    // ── Dashboard stats ──────────────────────────────────────────────────

    public function test_dashboard_stats_computes_average_points_per_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        LoyaltyPoint::create(['user_id' => $userA->id, 'points' => 300, 'type' => 'earned', 'description' => 'seed']);
        LoyaltyPoint::create(['user_id' => $userB->id, 'points' => 100, 'type' => 'earned', 'description' => 'seed']);

        $stats = $this->service->getDashboardStats();

        $this->assertSame(400, $stats['totalActivePoints']);
        $this->assertSame(2, $stats['totalPointUsers']);
        $this->assertEquals(200, $stats['averageUserPoints']); // round(400/2)
    }

    public function test_dashboard_stats_average_is_zero_when_no_users_have_points(): void
    {
        $stats = $this->service->getDashboardStats();

        $this->assertSame(0, $stats['averageUserPoints']);
    }
}
