<?php

namespace Tests\Feature\Loyalty;

use App\Models\Booking;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltySetting;
use App\Models\Reward;
use App\Models\User;
use App\Services\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyServiceTest extends TestCase
{
    use RefreshDatabase;

    private LoyaltyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(LoyaltyService::class);
    }

    // ── redeemReward() ───────────────────────────────────────────────────

    public function test_redeem_reward_creates_a_discount_code_and_deducts_points(): void
    {
        $user = User::factory()->create();
        LoyaltyPoint::create(['user_id' => $user->id, 'points' => 1000, 'type' => 'earned', 'description' => 'seed']);
        $reward = Reward::factory()->create([
            'required_points' => 500, 'discount_type' => 'fixed', 'discount_amount' => 20000, 'is_active' => true,
        ]);

        $discountCode = $this->service->redeemReward($user->id, $reward);

        $this->assertSame('fixed', $discountCode->type);
        $this->assertSame(20000.0, (float) $discountCode->amount);
        $this->assertSame($user->id, $discountCode->user_id);
        $this->assertSame(500, $this->service->getCurrentPoints($user->id)); // 1000 - 500
    }

    public function test_redeem_reward_operates_on_the_target_user_not_the_authenticated_user(): void
    {
        // Regression guard for the documented bug: this method must check points/notify based on
        // $userId, never on auth()->user() — critical for the admin-on-behalf-of-user flow
        // (LoyaltyAdminService::redeemRewardForUser).
        $admin = User::factory()->create(['is_admin' => true]);
        $targetUser = User::factory()->create();
        LoyaltyPoint::create(['user_id' => $targetUser->id, 'points' => 1000, 'type' => 'earned', 'description' => 'seed']);
        // The admin deliberately has zero points of their own.
        $reward = Reward::factory()->create(['required_points' => 500, 'is_active' => true]);

        $this->actingAs($admin);
        $discountCode = $this->service->redeemReward($targetUser->id, $reward);

        // Must succeed based on the target user's points, and the code must belong to them.
        $this->assertSame($targetUser->id, $discountCode->user_id);
        $this->assertSame(500, $this->service->getCurrentPoints($targetUser->id));
        // The admin's own (nonexistent) points balance must remain completely untouched.
        $this->assertSame(0, $this->service->getCurrentPoints($admin->id));
    }

    public function test_redeem_reward_throws_when_user_has_insufficient_points(): void
    {
        $user = User::factory()->create();
        LoyaltyPoint::create(['user_id' => $user->id, 'points' => 100, 'type' => 'earned', 'description' => 'seed']);
        $reward = Reward::factory()->create(['required_points' => 500, 'is_active' => true]);

        $this->expectException(\Exception::class);

        $this->service->redeemReward($user->id, $reward);
    }

    public function test_redeem_reward_throws_when_reward_is_inactive(): void
    {
        $user = User::factory()->create();
        LoyaltyPoint::create(['user_id' => $user->id, 'points' => 1000, 'type' => 'earned', 'description' => 'seed']);
        $reward = Reward::factory()->create(['required_points' => 500, 'is_active' => false]);

        $this->expectException(\Exception::class);

        $this->service->redeemReward($user->id, $reward);
    }

    public function test_redeem_reward_increments_reward_usage_count(): void
    {
        $user = User::factory()->create();
        LoyaltyPoint::create(['user_id' => $user->id, 'points' => 1000, 'type' => 'earned', 'description' => 'seed']);
        $reward = Reward::factory()->create(['required_points' => 500, 'is_active' => true, 'used_count' => 0]);

        $this->service->redeemReward($user->id, $reward);

        $this->assertSame(1, $reward->fresh()->used_count);
    }

    // ── earnPointsFromBooking() ──────────────────────────────────────────

    public function test_earn_points_from_booking_uses_configured_points_per_amount(): void
    {
        LoyaltySetting::where('key', 'points_per_amount')->update(['value' => '5000']);
        $user = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id, 'prepayment_amount' => 47500]);

        $point = $this->service->earnPointsFromBooking($user->id, $booking->id);

        // floor(47500 / 5000) = 9 (note: no "+5" constant here — that's specific to
        // BookingObserver::addLoyaltyPoints(), a separate, actually-wired code path).
        $this->assertSame(9, $point->points);
        $this->assertSame('earned', $point->type);
    }

    public function test_earn_points_from_booking_notifies_the_target_user_not_the_authenticated_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $targetUser = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $targetUser->id, 'prepayment_amount' => 50000]);

        $this->actingAs($admin);
        $point = $this->service->earnPointsFromBooking($targetUser->id, $booking->id);

        $this->assertSame($targetUser->id, $point->user_id);
        // The notification must land in the target user's own notifications, not the admin's.
        $this->assertDatabaseHas('user_notifications', [
            'notifiable_id' => $targetUser->id,
            'notifiable_type' => User::class,
        ]);
    }
}
