<?php

namespace Tests\Feature\Admin;

use App\Models\LoyaltyPoint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HTTP-level coverage for the manual loyalty points admin panel
 * (App\Http\Controllers\Admin\Loyalty\Point\AdminLoyaltyPointsController).
 *
 * This controller was rebuilt in test-writing session 10 after the user decided
 * (option B, in response to the open item logged at the end of session 9) that
 * LoyaltyAdminService::addPoints()/deductPoints() should be given a real route/UI
 * rather than removed, since they were tested at the service level
 * (LoyaltyAdminServiceTest) but had no HTTP entry point.
 */
class AdminLoyaltyPointsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_index_renders_without_a_search(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/loyalty/points')
            ->assertOk();
    }

    public function test_search_finds_a_user_by_name_or_phone(): void
    {
        $user = User::factory()->create(['name' => 'زهرا محمدی', 'phone' => '09121234567']);
        User::factory()->create(['name' => 'دیگری', 'phone' => '09129999999']);

        $response = $this->actingAs($this->admin)
            ->get('/admin/loyalty/points?search=زهرا');

        $response->assertOk();
        $response->assertSee($user->phone);
        $response->assertDontSee('09129999999');
    }

    public function test_selecting_a_user_shows_balance_and_history(): void
    {
        $user = User::factory()->create();
        LoyaltyPoint::factory()->create(['user_id' => $user->id, 'type' => 'earned', 'points' => 300]);
        LoyaltyPoint::factory()->spent()->create(['user_id' => $user->id, 'points' => -100]);

        $response = $this->actingAs($this->admin)
            ->get('/admin/loyalty/points?user_id='.$user->id);

        $response->assertOk();
        $response->assertSee('300'); // earned appears somewhere (formatted)
    }

    public function test_selecting_a_nonexistent_user_id_shows_not_found_message(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/loyalty/points?user_id=999999');

        $response->assertOk();
        $response->assertSee('کاربر مورد نظر یافت نشد');
    }

    public function test_add_points_creates_an_earned_point_row_and_redirects_to_the_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)->post("/admin/loyalty/points/{$user->id}/add", [
            'points' => 150,
            'description' => 'جبران خطای سیستم',
        ]);

        $response->assertRedirect(route('admin.loyalty.points.index', ['user_id' => $user->id]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('loyalty_points', [
            'user_id' => $user->id,
            'points' => 150,
            'type' => 'earned',
            'description' => 'جبران خطای سیستم',
        ]);
    }

    public function test_add_points_accepts_an_optional_future_expiry(): void
    {
        $user = User::factory()->create();
        $expiry = now()->addDays(10)->format('Y-m-d');

        $this->actingAs($this->admin)->post("/admin/loyalty/points/{$user->id}/add", [
            'points' => 50,
            'description' => 'پاداش موقت',
            'expires_at' => $expiry,
        ])->assertRedirect();

        $point = LoyaltyPoint::where('user_id', $user->id)->first();
        $this->assertNotNull($point->expires_at);
        $this->assertTrue($point->expires_at->isSameDay(now()->addDays(10)));
    }

    public function test_add_points_rejects_a_past_expiry_date(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)->post("/admin/loyalty/points/{$user->id}/add", [
            'points' => 50,
            'description' => 'پاداش',
            'expires_at' => now()->subDay()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('expires_at');
        $this->assertDatabaseMissing('loyalty_points', ['user_id' => $user->id]);
    }

    public function test_add_points_rejects_zero_or_missing_points(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin)->post("/admin/loyalty/points/{$user->id}/add", [
            'points' => 0,
            'description' => 'نامعتبر',
        ])->assertSessionHasErrors('points');

        $this->actingAs($this->admin)->post("/admin/loyalty/points/{$user->id}/add", [
            'description' => 'بدون امتیاز',
        ])->assertSessionHasErrors('points');
    }

    public function test_add_points_requires_a_description(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin)->post("/admin/loyalty/points/{$user->id}/add", [
            'points' => 20,
        ])->assertSessionHasErrors('description');
    }

    public function test_deduct_points_creates_a_spent_row_and_clears_cache(): void
    {
        $user = User::factory()->create();
        LoyaltyPoint::factory()->create(['user_id' => $user->id, 'type' => 'earned', 'points' => 200]);
        \Illuminate\Support\Facades\Cache::put("user:{$user->id}:loyalty_points", 999, 60);

        $response = $this->actingAs($this->admin)->post("/admin/loyalty/points/{$user->id}/deduct", [
            'points' => 80,
            'description' => 'خطای اعطای امتیاز قبلی',
        ]);

        $response->assertRedirect(route('admin.loyalty.points.index', ['user_id' => $user->id]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('loyalty_points', [
            'user_id' => $user->id,
            'points' => -80,
            'type' => 'spent',
        ]);
        $this->assertFalse(\Illuminate\Support\Facades\Cache::has("user:{$user->id}:loyalty_points"));
    }

    public function test_deduct_points_fails_gracefully_when_balance_is_insufficient(): void
    {
        $user = User::factory()->create();
        LoyaltyPoint::factory()->create(['user_id' => $user->id, 'type' => 'earned', 'points' => 30]);

        $response = $this->actingAs($this->admin)->post("/admin/loyalty/points/{$user->id}/deduct", [
            'points' => 500,
            'description' => 'کسر بیش از موجودی',
        ]);

        $response->assertRedirect(route('admin.loyalty.points.index', ['user_id' => $user->id]));
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('loyalty_points', ['user_id' => $user->id, 'type' => 'spent']);
    }

    public function test_deduct_points_requires_points_and_description(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin)->post("/admin/loyalty/points/{$user->id}/deduct", [
            'description' => 'بدون امتیاز',
        ])->assertSessionHasErrors('points');

        $this->actingAs($this->admin)->post("/admin/loyalty/points/{$user->id}/deduct", [
            'points' => 10,
        ])->assertSessionHasErrors('description');
    }

    public function test_a_non_admin_cannot_access_the_points_panel(): void
    {
        $regularUser = User::factory()->create(['is_admin' => false]);
        $target = User::factory()->create();

        $this->actingAs($regularUser)->get('/admin/loyalty/points')->assertForbidden();
        $this->actingAs($regularUser)->post("/admin/loyalty/points/{$target->id}/add", [
            'points' => 10,
            'description' => 'x',
        ])->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/loyalty/points')->assertRedirect(route('login'));
    }
}
