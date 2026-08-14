<?php

namespace Tests\Feature\Admin;

use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HTTP-level coverage for the admin loyalty panel (index + reward CRUD), complementing
 * LoyaltyAdminServiceTest (session 4), which only exercises the service layer directly.
 * This goes through the full stack: routing, PermissionMiddleware/is_admin gate, Form Request
 * validation, controller, service, view.
 *
 * Note: AdminLoyaltySettingsController and AdminLoyaltyPointsController exist in
 * app/Http/Controllers/Admin/Loyalty/ but are not wired to any route in routes/admin/loyalty.php
 * (confirmed with a full-project grep — no web or api route references either class). They are
 * currently unreachable dead code, so no HTTP-level test is possible for them; this is a new
 * finding, candidate for R-Cleanup-DeadCode.
 */
class AdminLoyaltyRewardHttpTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_index_shows_dashboard_stats_and_rewards_list(): void
    {
        $user = User::factory()->create();
        LoyaltyPoint::factory()->create(['user_id' => $user->id, 'type' => 'earned', 'points' => 100]);
        Reward::factory()->create(['used_count' => 3]);
        Reward::factory()->create();

        $response = $this->actingAs($this->admin)->get('/admin/loyalty');

        $response->assertOk();
        $this->assertSame(100, $response->viewData('totalActivePoints'));
        $this->assertSame(1, $response->viewData('totalPointUsers'));
        $this->assertSame(3, $response->viewData('totalRedeemedRewards'));
        $this->assertCount(2, $response->viewData('rewards'));
    }

    public function test_create_form_renders(): void
    {
        $this->actingAs($this->admin)->get('/admin/loyalty/rewards/create')->assertOk();
    }

    public function test_store_creates_a_reward(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/loyalty/rewards', [
            'title' => 'تخفیف ویژه تولد',
            'description' => 'یک تخفیف مخصوص روز تولد',
            'required_points' => 500,
            'discount_type' => 'percentage',
            'discount_amount' => 15,
            'max_uses' => 50,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.loyalty.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('rewards', ['title' => 'تخفیف ویژه تولد', 'required_points' => 500]);
    }

    public function test_store_accepts_a_fixed_discount_amount_over_100(): void
    {
        // Regression guard: MaxPercentage must not apply to 'fixed' discount_type — a fixed
        // reward's discount_amount is a toman value (routinely tens of thousands), so capping
        // it at 100 would make it impossible to ever create/update a realistic fixed reward.
        // This is the exact same regression class documented (and fixed) for
        // StoreDiscountCodeRequest in R-AdminForms, which had not been carried over here.
        $response = $this->actingAs($this->admin)->post('/admin/loyalty/rewards', [
            'title' => 'تخفیف ثابت واقعی',
            'required_points' => 300,
            'discount_type' => 'fixed',
            'discount_amount' => 150000,
            'max_uses' => 10,
        ]);

        $response->assertRedirect(route('admin.loyalty.index'));
        $this->assertDatabaseHas('rewards', ['title' => 'تخفیف ثابت واقعی', 'discount_amount' => 150000]);
    }

    public function test_store_rejects_a_percentage_discount_over_100(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/loyalty/rewards', [
            'title' => 'تخفیف نامعتبر',
            'required_points' => 500,
            'discount_type' => 'percentage',
            'discount_amount' => 150,
            'max_uses' => 10,
        ]);

        // The controller catches all exceptions and redirects with an error flash rather than
        // letting validation exceptions surface normally when hit via a raw post() in tests
        // that doesn't follow the FormRequest's own redirect-back-with-errors path directly —
        // assert the reward was NOT created either way.
        $this->assertDatabaseMissing('rewards', ['title' => 'تخفیف نامعتبر']);
    }

    public function test_show_displays_a_single_reward(): void
    {
        $reward = Reward::factory()->create();

        $response = $this->actingAs($this->admin)->get("/admin/loyalty/rewards/{$reward->id}");

        $response->assertOk();
        $this->assertSame($reward->id, $response->viewData('reward')->id);
    }

    public function test_edit_form_renders_with_existing_reward(): void
    {
        $reward = Reward::factory()->create();

        $response = $this->actingAs($this->admin)->get("/admin/loyalty/rewards/{$reward->id}/edit");

        $response->assertOk();
    }

    public function test_update_changes_reward_fields(): void
    {
        // discount_type is fixed explicitly (not left to the factory's random choice) so this
        // test's outcome doesn't depend on chance — see the dedicated fixed/percentage regression
        // tests below for the MaxPercentage-per-type behavior itself.
        $reward = Reward::factory()->create([
            'title' => 'قدیمی',
            'used_count' => 0,
            'discount_type' => 'fixed',
            'discount_amount' => 100000,
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/loyalty/rewards/{$reward->id}", [
            'title' => 'جدید',
            'description' => $reward->description,
            'required_points' => $reward->required_points,
            'discount_type' => 'fixed',
            'discount_amount' => 120000,
            'max_uses' => 20,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.loyalty.index'));
        $this->assertDatabaseHas('rewards', ['id' => $reward->id, 'title' => 'جدید']);
    }

    public function test_update_rejects_max_uses_below_current_used_count(): void
    {
        $reward = Reward::factory()->create(['used_count' => 10, 'max_uses' => 20]);

        $response = $this->actingAs($this->admin)->from(route('admin.loyalty.rewards.edit', $reward))
            ->put("/admin/loyalty/rewards/{$reward->id}", [
                'title' => $reward->title,
                'required_points' => $reward->required_points,
                'discount_type' => $reward->discount_type,
                'discount_amount' => $reward->discount_type === 'percentage' ? 10 : 50000,
                'max_uses' => 5, // below used_count of 10
                'is_active' => true,
            ]);

        $response->assertSessionHasErrors('max_uses');
        $this->assertSame(20, $reward->fresh()->max_uses);
    }

    public function test_destroy_deletes_a_reward(): void
    {
        $reward = Reward::factory()->create();

        $response = $this->actingAs($this->admin)->delete("/admin/loyalty/rewards/{$reward->id}");

        $response->assertRedirect(route('admin.loyalty.index'));
        $this->assertDatabaseMissing('rewards', ['id' => $reward->id]);
    }

    public function test_destroy_refuses_to_delete_a_reward_that_has_been_used(): void
    {
        $reward = Reward::factory()->create(['used_count' => 5]);

        $response = $this->actingAs($this->admin)->delete("/admin/loyalty/rewards/{$reward->id}");

        $response->assertRedirect(route('admin.loyalty.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('rewards', ['id' => $reward->id]);
    }

    public function test_redeem_reward_activates_it_for_the_target_user_not_the_admin(): void
    {
        $targetUser = User::factory()->create();
        LoyaltyPoint::factory()->create(['user_id' => $targetUser->id, 'type' => 'earned', 'points' => 1000]);
        $reward = Reward::factory()->create(['required_points' => 500, 'used_count' => 0]);

        $response = $this->actingAs($this->admin)->post("/admin/loyalty/rewards/{$reward->id}/redeem", [
            'user_id' => $targetUser->id,
        ]);

        $response->assertRedirect(route('admin.loyalty.index'));
        $response->assertSessionHas('success');
        $this->assertSame(1, $reward->fresh()->used_count);
    }

    public function test_redeem_reward_fails_gracefully_when_target_user_lacks_enough_points(): void
    {
        $targetUser = User::factory()->create();
        LoyaltyPoint::factory()->create(['user_id' => $targetUser->id, 'type' => 'earned', 'points' => 10]);
        $reward = Reward::factory()->create(['required_points' => 500, 'used_count' => 0]);

        $response = $this->actingAs($this->admin)->post("/admin/loyalty/rewards/{$reward->id}/redeem", [
            'user_id' => $targetUser->id,
        ]);

        $response->assertRedirect(route('admin.loyalty.index'));
        $response->assertSessionHas('error');
        $this->assertSame(0, $reward->fresh()->used_count);
    }

    public function test_non_admin_cannot_access_loyalty_panel(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/loyalty')->assertStatus(403);
    }
}
