<?php

namespace Tests\Feature\Policies;

use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingPolicyTest extends TestCase
{
    use RefreshDatabase;

    // ── before() admin/manager bypass ────────────────────────────────────

    public function test_admin_can_do_anything_regardless_of_ownership(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $booking = Booking::factory()->create(['status' => 'confirmed']);

        $this->assertTrue($admin->can('view', $booking));
        $this->assertTrue($admin->can('update', $booking));
        $this->assertTrue($admin->can('cancel', $booking));
        $this->assertTrue($admin->can('delete', $booking));
    }

    // ── view() ────────────────────────────────────────────────────────────

    public function test_owner_can_view_their_own_booking(): void
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('view', $booking));
    }

    public function test_unrelated_user_cannot_view_someone_elses_booking(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($user->can('view', $booking));
    }

    // ── update() ─────────────────────────────────────────────────────────

    public function test_owner_can_update_a_pending_booking(): void
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id, 'status' => 'pending']);

        $this->assertTrue($user->can('update', $booking));
    }

    public function test_owner_cannot_update_a_confirmed_booking(): void
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id, 'status' => 'confirmed']);

        $this->assertFalse($user->can('update', $booking));
    }

    public function test_non_owner_cannot_update_a_pending_booking(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $otherUser->id, 'status' => 'pending']);

        $this->assertFalse($user->can('update', $booking));
    }

    // ── applyDiscount() ──────────────────────────────────────────────────

    public function test_owner_can_apply_discount_to_their_own_unpaid_booking(): void
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending_payment',
            'payment_status' => 'unpaid',
        ]);

        $this->assertTrue($user->can('applyDiscount', $booking));
    }

    public function test_owner_cannot_apply_discount_to_an_already_paid_booking(): void
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id, 'payment_status' => 'paid']);

        $this->assertFalse($user->can('applyDiscount', $booking));
    }

    // ── pay() ────────────────────────────────────────────────────────────

    public function test_owner_can_pay_for_their_own_booking(): void
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('pay', $booking));
    }

    public function test_non_owner_cannot_pay_for_someone_elses_booking(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($user->can('pay', $booking));
    }

    // ── reschedule() ─────────────────────────────────────────────────────

    public function test_owner_can_reschedule_when_more_than_24_hours_away(): void
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id, 'status' => 'confirmed', 'booking_time' => now()->addHours(48),
        ]);

        $this->assertTrue($user->can('reschedule', $booking));
    }

    public function test_owner_cannot_reschedule_within_24_hours(): void
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id, 'status' => 'confirmed', 'booking_time' => now()->addHours(5),
        ]);

        $this->assertFalse($user->can('reschedule', $booking));
    }

    // ── changeStatus() ───────────────────────────────────────────────────

    public function test_assigned_specialist_can_change_status(): void
    {
        $specialistUser = User::factory()->create();
        $specialistRecord = Specialist::factory()->create(['phone' => $specialistUser->phone]);
        $specialistUser->roles()->attach(\App\Models\Role::factory()->create(['name' => 'specialist']));

        $booking = Booking::factory()->create(['specialist_id' => $specialistRecord->id]);

        $this->assertTrue($specialistUser->can('changeStatus', $booking));
    }

    public function test_unrelated_specialist_cannot_change_status_of_someone_elses_booking(): void
    {
        $specialistUser = User::factory()->create();
        Specialist::factory()->create(['phone' => $specialistUser->phone]); // unrelated specialist record
        $specialistUser->roles()->attach(\App\Models\Role::factory()->create(['name' => 'specialist']));

        $otherSpecialist = Specialist::factory()->create();
        $booking = Booking::factory()->create(['specialist_id' => $otherSpecialist->id]);

        $this->assertFalse($specialistUser->can('changeStatus', $booking));
    }

    // ── cancel() ─────────────────────────────────────────────────────────

    public function test_owner_can_cancel_more_than_24_hours_before_booking(): void
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id, 'status' => 'confirmed', 'booking_time' => now()->addHours(48),
        ]);

        $this->assertTrue($user->can('cancel', $booking));
    }

    public function test_owner_cannot_cancel_within_24_hours_of_booking(): void
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id, 'status' => 'confirmed', 'booking_time' => now()->addHours(10),
        ]);

        $this->assertFalse($user->can('cancel', $booking));
    }

    public function test_owner_cannot_cancel_an_already_cancelled_booking(): void
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id, 'status' => 'cancelled', 'booking_time' => now()->addHours(48),
        ]);

        $this->assertFalse($user->can('cancel', $booking));
    }

    // ── delete() ─────────────────────────────────────────────────────────

    public function test_regular_user_can_never_delete_a_booking(): void
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id]);

        $this->assertFalse($user->can('delete', $booking));
    }
}
