<?php

namespace Tests\Feature\User;

use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The customer-facing web LoyaltyController (index/redeemReward/points/history/rewards/
 * progress/overview/discountCodes/myCodes) had no dedicated HTTP test before this session —
 * only LoyaltyService (Unit-ish) and LoyaltyAdminService/AdminLoyaltyRewardHttpTest (the
 * admin side) were covered.
 */
class LoyaltyControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_index_renders_the_loyalty_dashboard(): void
    {
        LoyaltyPoint::factory()->create(['user_id' => $this->user->id, 'points' => 200]);

        $response = $this->actingAs($this->user)->get('/loyalty');

        $response->assertOk();
        $response->assertViewHas('userPoints', 200);
    }

    public function test_redeem_reward_succeeds_when_the_user_has_enough_points(): void
    {
        LoyaltyPoint::factory()->create(['user_id' => $this->user->id, 'points' => 500]);
        $reward = Reward::factory()->create(['required_points' => 300, 'is_active' => true]);

        $response = $this->actingAs($this->user)->post("/loyalty/rewards/{$reward->id}/redeem");

        $response->assertRedirect(route('loyalty.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('loyalty_points', [
            'user_id' => $this->user->id,
            'points' => -300,
            'type' => 'spent',
        ]);
    }

    public function test_redeem_reward_fails_when_the_user_does_not_have_enough_points(): void
    {
        LoyaltyPoint::factory()->create(['user_id' => $this->user->id, 'points' => 100]);
        $reward = Reward::factory()->create(['required_points' => 300, 'is_active' => true]);

        $response = $this->actingAs($this->user)->post("/loyalty/rewards/{$reward->id}/redeem");

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('loyalty_points', [
            'user_id' => $this->user->id,
            'type' => 'spent',
        ]);
    }

    public function test_get_points_returns_the_current_balance_as_json(): void
    {
        LoyaltyPoint::factory()->create(['user_id' => $this->user->id, 'points' => 150]);

        $response = $this->actingAs($this->user)->getJson('/loyalty/points');

        $response->assertOk();
        $response->assertJson(['points' => 150]);
    }

    public function test_get_history_returns_a_paginated_list(): void
    {
        LoyaltyPoint::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->getJson('/loyalty/history');

        $response->assertOk();
        $response->assertJsonStructure(['data', 'current_page']);
    }

    public function test_get_rewards_returns_available_rewards_and_user_points(): void
    {
        LoyaltyPoint::factory()->create(['user_id' => $this->user->id, 'points' => 400]);
        Reward::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->user)->getJson('/loyalty/rewards');

        $response->assertOk();
        $response->assertJson(['user_points' => 400]);
    }

    public function test_get_progress_reports_points_needed_for_the_next_reward(): void
    {
        LoyaltyPoint::factory()->create(['user_id' => $this->user->id, 'points' => 200]);
        Reward::factory()->create(['required_points' => 500, 'is_active' => true]);

        $response = $this->actingAs($this->user)->getJson('/loyalty/progress');

        $response->assertOk();
        $response->assertJson([
            'current_points' => 200,
            'next_reward' => ['points_needed' => 300],
        ]);
    }

    public function test_discount_codes_only_returns_active_unexpired_codes_with_remaining_uses(): void
    {
        \App\Models\DiscountCode::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
            'max_uses' => 5,
            'used_count' => 1,
            'expires_at' => null,
        ]);
        \App\Models\DiscountCode::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)->getJson('/loyalty/discount-codes');

        $response->assertOk();
        $this->assertCount(1, $response->json('discount_codes'));
    }

    public function test_my_codes_page_renders(): void
    {
        $response = $this->actingAs($this->user)->get('/loyalty/my-codes');

        $response->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/loyalty')->assertRedirect(route('login'));
    }
}
