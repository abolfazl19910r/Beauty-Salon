<?php

namespace Tests\Feature\User;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\DiscountCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * BookingDiscountController (check/apply/applyApi) had zero test coverage before this
 * session, despite being the exact controller at the center of the documented R-DiscountLogic
 * bug chain (payment_reference, wrong Form Request namespace, BookingPolicy 403s, dead API
 * routes, apply-discount vs check-discount confusion). This is the first regression guard for
 * the whole "apply a discount code" flow end to end via real HTTP requests.
 */
class BookingDiscountControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_check_previews_the_discount_for_a_service_without_creating_a_booking(): void
    {
        $service = BeautyService::factory()->create(['price' => 200000]);
        $code = DiscountCode::factory()->create([
            'type' => 'percentage',
            'amount' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->postJson('/bookings/check-discount', [
            'code' => $code->code,
            'service_id' => $service->id,
        ]);

        $response->assertOk();
        $response->assertJson(['valid' => true]);
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_check_falls_back_to_a_default_base_amount_with_no_service_or_booking(): void
    {
        $code = DiscountCode::factory()->create(['type' => 'fixed', 'amount' => 20000, 'is_active' => true]);

        $response = $this->actingAs($this->user)->postJson('/bookings/check-discount', [
            'code' => $code->code,
        ]);

        $response->assertOk();
        $response->assertJson(['valid' => true]);
    }

    public function test_check_reports_a_422_for_an_unknown_code(): void
    {
        $response = $this->actingAs($this->user)->postJson('/bookings/check-discount', [
            'code' => 'DOESNOTEXIST',
        ]);

        $response->assertStatus(422);
    }

    public function test_check_requires_authentication_on_the_sanctum_guarded_api_route(): void
    {
        // ⭐ Updated (test-writing session 9): the misleading "public" registration
        // (/api/check-discount, no middleware, but silently 403'd by
        // CheckDiscountRequest::authorize() anyway) was removed per an explicit project
        // decision that this preview genuinely requires login. The only remaining API
        // route is /api/bookings/check-discount, properly guarded by auth:sanctum.
        $code = DiscountCode::factory()->create(['is_active' => true]);

        $response = $this->postJson('/api/bookings/check-discount', ['code' => $code->code]);

        $response->assertUnauthorized();
    }

    public function test_check_works_via_the_sanctum_guarded_api_route_once_authenticated(): void
    {
        $code = DiscountCode::factory()->create(['is_active' => true]);

        Sanctum::actingAs($this->user);
        $response = $this->postJson('/api/bookings/check-discount', ['code' => $code->code]);

        $response->assertOk();
        $response->assertJson(['valid' => true]);
    }

    public function test_apply_attaches_the_discount_to_the_users_own_unpaid_booking(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'payment_status' => 'unpaid',
            'prepayment_amount' => 100000,
        ]);
        $code = DiscountCode::factory()->create([
            'type' => 'percentage',
            'amount' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->post("/bookings/{$booking->id}/apply-discount", [
            'code' => $code->code,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame($code->code, $booking->fresh()->discount_code);
        $this->assertGreaterThan(0, (float) $booking->fresh()->discount_amount);
    }

    public function test_apply_is_forbidden_for_another_users_booking(): void
    {
        $other = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $other->id, 'payment_status' => 'unpaid']);
        $code = DiscountCode::factory()->create(['is_active' => true]);

        $this->actingAs($this->user)
            ->post("/bookings/{$booking->id}/apply-discount", ['code' => $code->code])
            ->assertForbidden();
    }

    public function test_apply_refuses_a_code_already_belonging_to_a_different_user(): void
    {
        $owner = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $this->user->id, 'payment_status' => 'unpaid']);
        $code = DiscountCode::factory()->create(['is_active' => true, 'user_id' => $owner->id]);

        $response = $this->actingAs($this->user)
            ->post("/bookings/{$booking->id}/apply-discount", ['code' => $code->code]);

        $response->assertSessionHas('error');
        $this->assertNull($booking->fresh()->discount_code);
    }

    public function test_apply_is_forbidden_on_an_already_paid_booking(): void
    {
        // BookingPolicy::applyDiscount requires payment_status === 'unpaid', so a paid
        // booking never even reaches BookingService::applyDiscountCode()'s own
        // "already paid" message — it's blocked one layer earlier, at authorization.
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'payment_status' => 'paid',
        ]);
        $code = DiscountCode::factory()->create(['is_active' => true]);

        $this->actingAs($this->user)
            ->post("/bookings/{$booking->id}/apply-discount", ['code' => $code->code])
            ->assertForbidden();
    }

    public function test_apply_refuses_reapplying_a_second_code_to_the_same_booking(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'payment_status' => 'unpaid',
            'discount_code' => 'ALREADY-APPLIED',
        ]);
        $code = DiscountCode::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->user)
            ->post("/bookings/{$booking->id}/apply-discount", ['code' => $code->code]);

        $response->assertSessionHas('error');
        $this->assertSame('ALREADY-APPLIED', $booking->fresh()->discount_code);
    }

    public function test_apply_api_returns_json_success(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'payment_status' => 'unpaid',
            'prepayment_amount' => 100000,
        ]);
        $code = DiscountCode::factory()->create(['type' => 'fixed', 'amount' => 15000, 'is_active' => true]);

        Sanctum::actingAs($this->user);
        $response = $this->postJson("/api/bookings/{$booking->id}/apply-discount", [
            'code' => $code->code,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_apply_api_returns_json_error_for_an_invalid_code(): void
    {
        $booking = Booking::factory()->create([
            'user_id' => $this->user->id,
            'payment_status' => 'unpaid',
        ]);

        Sanctum::actingAs($this->user);
        $response = $this->postJson("/api/bookings/{$booking->id}/apply-discount", [
            'code' => 'BOGUS-CODE',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_apply_requires_authentication(): void
    {
        $booking = Booking::factory()->create(['payment_status' => 'unpaid']);
        $code = DiscountCode::factory()->create(['is_active' => true]);

        $this->post("/bookings/{$booking->id}/apply-discount", ['code' => $code->code])
            ->assertRedirect(route('login'));
    }
}
