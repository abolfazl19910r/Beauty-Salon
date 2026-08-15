<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Leave;
use App\Models\Specialist;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers AdminSpecialistLeaveController — the per-specialist leave page — plus LeaveService's
 * conflict-detection logic (approved-leave overlap, booking overlap) via the full HTTP path.
 * This is the controller that had the documented Gregorian/Jalali date-key mismatch bug
 * (Leave-Migration phase): store() now expects start_date/end_date, matching what the Blade
 * modal actually sends.
 */
class AdminSpecialistLeaveTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Specialist $specialist;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->specialist = Specialist::factory()->create();
    }

    public function test_index_lists_the_specialists_own_leaves(): void
    {
        Leave::factory()->count(2)->create(['specialist_id' => $this->specialist->id]);
        Leave::factory()->create(); // a different specialist

        $response = $this->actingAs($this->admin)->get("/admin/specialists/{$this->specialist->id}/leaves");

        $response->assertOk();
        $this->assertCount(2, $response->viewData('leaves'));
    }

    public function test_store_creates_a_pending_leave_using_gregorian_date_keys(): void
    {
        // Regression guard for the documented Leave-Migration bug: the modal sends
        // start_date/end_date (Gregorian), not start_date_jalali/end_date_jalali.
        $response = $this->actingAs($this->admin)->post("/admin/specialists/{$this->specialist->id}/leaves", [
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(7)->format('Y-m-d'),
            'reason' => 'سفر خانوادگی',
        ]);

        $response->assertRedirect(route('admin.specialists.leaves.index', $this->specialist));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('leaves', [
            'specialist_id' => $this->specialist->id,
            'status' => 'pending',
            'reason' => 'سفر خانوادگی',
        ]);
    }

    public function test_store_rejects_a_start_date_in_the_past(): void
    {
        $response = $this->actingAs($this->admin)->from(route('admin.specialists.leaves.index', $this->specialist))
            ->post("/admin/specialists/{$this->specialist->id}/leaves", [
                'start_date' => now()->subDays(2)->format('Y-m-d'),
                'end_date' => now()->addDays(2)->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('start_date');
    }

    public function test_store_refuses_a_range_overlapping_an_already_approved_leave(): void
    {
        Leave::factory()->create([
            'specialist_id' => $this->specialist->id,
            'status' => 'approved',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(10),
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/specialists/{$this->specialist->id}/leaves", [
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(8)->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('admin.specialists.leaves.index', $this->specialist));
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('leaves', ['specialist_id' => $this->specialist->id, 'status' => 'pending']);
    }

    public function test_store_refuses_a_range_that_has_an_active_booking(): void
    {
        Booking::factory()->create([
            'specialist_id' => $this->specialist->id,
            'booking_time' => now()->addDays(6),
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/specialists/{$this->specialist->id}/leaves", [
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(8)->format('Y-m-d'),
        ]);

        $response->assertSessionHas('error');
    }

    public function test_approving_a_leave_notifies_the_specialists_matching_user_not_the_specialist_model(): void
    {
        $matchedUser = User::where('phone', $this->specialist->phone)->first();
        $leave = Leave::factory()->create(['specialist_id' => $this->specialist->id, 'status' => 'pending']);

        $response = $this->actingAs($this->admin)->put("/admin/specialists/{$this->specialist->id}/leaves/{$leave->id}", [
            'status' => 'approved',
        ]);

        $response->assertRedirect(route('admin.specialists.leaves.index', $this->specialist));
        $this->assertSame('approved', $leave->fresh()->status);
        $this->assertNotNull($leave->fresh()->approved_at);

        // Regression guard: the notification must land on notifiable_type=User (matched by
        // phone), not notifiable_type=Specialist — otherwise it never shows up on the
        // specialist's own notification page (SpecialistNotificationController reads
        // auth()->user()->notifications() which resolves to the User side).
        $this->assertDatabaseHas('user_notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $matchedUser->id,
        ]);
    }

    public function test_approving_a_leave_that_now_conflicts_is_rejected_by_the_service(): void
    {
        $leave = Leave::factory()->create([
            'specialist_id' => $this->specialist->id,
            'status' => 'pending',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(7),
        ]);
        // A conflicting booking was added after the pending leave was requested.
        Booking::factory()->create([
            'specialist_id' => $this->specialist->id,
            'booking_time' => now()->addDays(6),
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/specialists/{$this->specialist->id}/leaves/{$leave->id}", [
            'status' => 'approved',
        ]);

        $response->assertSessionHas('error');
        $this->assertSame('pending', $leave->fresh()->status);
    }

    public function test_rejecting_a_leave_requires_a_reason(): void
    {
        $leave = Leave::factory()->create(['specialist_id' => $this->specialist->id, 'status' => 'pending']);

        $response = $this->actingAs($this->admin)->from(route('admin.specialists.leaves.index', $this->specialist))
            ->put("/admin/specialists/{$this->specialist->id}/leaves/{$leave->id}", [
                'status' => 'rejected',
            ]);

        $response->assertSessionHasErrors('reject_reason');
    }

    public function test_rejecting_with_a_reason_updates_the_leave(): void
    {
        $leave = Leave::factory()->create(['specialist_id' => $this->specialist->id, 'status' => 'pending']);

        $response = $this->actingAs($this->admin)->put("/admin/specialists/{$this->specialist->id}/leaves/{$leave->id}", [
            'status' => 'rejected',
            'reject_reason' => 'کمبود پرسنل در این بازه',
        ]);

        $response->assertRedirect();
        $leave->refresh();
        $this->assertSame('rejected', $leave->status);
        $this->assertSame('کمبود پرسنل در این بازه', $leave->reject_reason);
    }

    public function test_update_refuses_a_leave_belonging_to_a_different_specialist(): void
    {
        $otherSpecialist = Specialist::factory()->create();
        $leave = Leave::factory()->create(['specialist_id' => $otherSpecialist->id, 'status' => 'pending']);

        $response = $this->actingAs($this->admin)->put("/admin/specialists/{$this->specialist->id}/leaves/{$leave->id}", [
            'status' => 'approved',
        ]);

        $response->assertStatus(403);
        $this->assertSame('pending', $leave->fresh()->status);
    }

    public function test_approve_and_reject_persist_their_timestamp_fields(): void
    {
        // Regression guard: Leave::$fillable previously omitted approved_at/rejected_at,
        // so Leave::approve()/reject() (both plain mass-assignment updates) silently dropped
        // those columns — status flipped correctly but the timestamp columns stayed null
        // forever. Same "form/update gets data, silently discards it" pattern documented
        // repeatedly elsewhere in this project (admin_commission_percentage, BlogCategory
        // description/order, etc.), this time on the Leave model.
        $approvedLeave = Leave::factory()->create(['specialist_id' => $this->specialist->id, 'status' => 'pending']);
        $this->actingAs($this->admin)->put("/admin/specialists/{$this->specialist->id}/leaves/{$approvedLeave->id}", [
            'status' => 'approved',
        ]);
        $this->assertNotNull($approvedLeave->fresh()->approved_at);

        $rejectedLeave = Leave::factory()->create(['specialist_id' => $this->specialist->id, 'status' => 'pending']);
        $this->actingAs($this->admin)->put("/admin/specialists/{$this->specialist->id}/leaves/{$rejectedLeave->id}", [
            'status' => 'rejected',
            'reject_reason' => 'دلیل رد',
        ]);
        $this->assertNotNull($rejectedLeave->fresh()->rejected_at);
    }

    public function test_non_admin_cannot_access_specialist_leave_management(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get("/admin/specialists/{$this->specialist->id}/leaves")->assertStatus(403);
    }
}
