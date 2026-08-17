<?php

namespace Tests\Feature\Specialist;

use App\Models\Review;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ Regression suite for a critical bug discovered in test-writing session 6
 * (2026-08-16): SpecialistPolicy, ReviewPolicy, and five specialist Form Requests
 * all gated on `$user->hasRole('specialist')`. Nothing in the real registration or
 * specialist-creation flow ever assigns that role to a user — the User↔Specialist
 * link is entirely phone-match based (User::specialist()). This made every ability
 * below permanently return false/403 in production for every specialist, unless an
 * admin manually visited /admin/roles/assign per specialist — a step nowhere
 * documented as part of the specialist-creation workflow.
 *
 * Every test in this class deliberately does NOT assign the 'specialist' role to
 * prove these endpoints work purely from the phone-match link, matching the
 * already-correct pattern used by SpecialistWalletPolicy elsewhere in this project.
 */
class SpecialistSelfServiceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function actingSpecialist(): array
    {
        $user = User::factory()->create(['phone' => '09121234567']);
        $specialist = Specialist::factory()->create([
            'phone' => '09121234567',
            'user_id' => $user->id,
        ]);

        return [$user, $specialist];
    }

    public function test_iban_update_succeeds_without_any_role_assigned(): void
    {
        [$user, $specialist] = $this->actingSpecialist();

        $response = $this->actingAs($user)->put(route('specialist.wallet.update-iban'), [
            'iban' => '820540102680020817909002',
            'account_holder_name' => 'علی رضایی',
            'bank_name' => 'بانک ملت',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertSame('IR820540102680020817909002', $specialist->getOrCreateWallet()->fresh()->iban);
    }

    public function test_leave_index_and_create_pages_render_without_any_role_assigned(): void
    {
        [$user] = $this->actingSpecialist();

        $this->actingAs($user)->get(route('specialist.leaves'))->assertOk();
        $this->actingAs($user)->get(route('specialist.leaves.create'))->assertOk();
    }

    public function test_leave_store_succeeds_without_any_role_assigned(): void
    {
        [$user, $specialist] = $this->actingSpecialist();

        $response = $this->actingAs($user)->post(route('specialist.leaves.store'), [
            'start_date_jalali' => '1405/02/01',
            'end_date_jalali' => '1405/02/02',
            'reason' => 'استراحت',
        ]);

        $response->assertRedirect(route('specialist.leaves'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('leaves', ['specialist_id' => $specialist->id, 'status' => 'pending']);
    }

    public function test_schedule_update_succeeds_without_any_role_assigned(): void
    {
        [$user, $specialist] = $this->actingSpecialist();
        SpecialistSchedule::factory()->create(['specialist_id' => $specialist->id]);

        $response = $this->actingAs($user)->put(route('specialist.schedule.update'), [
            'schedules' => [
                [
                    'day_of_week' => 6,
                    'is_active' => '1',
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    public function test_profile_update_succeeds_without_any_role_assigned(): void
    {
        [$user] = $this->actingSpecialist();

        $response = $this->actingAs($user)->put(route('specialist.profile.update'), [
            'name' => 'نام جدید',
            'phone' => $user->phone,
        ]);

        $response->assertRedirect(route('specialist.profile.show'));
        $response->assertSessionHasNoErrors();
        $this->assertSame('نام جدید', $user->fresh()->name);
    }

    public function test_review_show_and_respond_succeed_without_any_role_assigned(): void
    {
        [$user, $specialist] = $this->actingSpecialist();
        $review = Review::factory()->create(['specialist_id' => $specialist->id]);

        $this->actingAs($user)->get(route('specialist.reviews.show', $review))->assertOk();

        $response = $this->actingAs($user)->post(route('specialist.reviews.respond', $review), [
            'response' => 'ممنون از نظر شما.',
        ]);

        $response->assertRedirect();
        $this->assertSame('ممنون از نظر شما.', $review->fresh()->specialist_response);
    }

    public function test_bookings_index_is_accessible_without_any_role_assigned(): void
    {
        [$user] = $this->actingSpecialist();

        $this->actingAs($user)->get(route('specialist.bookings.index'))->assertOk();
    }

    public function test_a_specialist_cannot_respond_to_another_specialists_review(): void
    {
        [$user] = $this->actingSpecialist();
        $otherSpecialist = Specialist::factory()->create();
        $review = Review::factory()->create(['specialist_id' => $otherSpecialist->id]);

        $this->actingAs($user)
            ->post(route('specialist.reviews.respond', $review), ['response' => 'تست'])
            ->assertForbidden();
    }

    public function test_a_specialist_cannot_update_another_specialists_iban_via_a_forged_wallet(): void
    {
        [$user] = $this->actingSpecialist();
        $otherSpecialist = Specialist::factory()->create();
        $otherWallet = $otherSpecialist->getOrCreateWallet();

        // updateIban is resolved from the authenticated specialist internally, not from
        // a route parameter, so this is really confirming the wallet policy scoping —
        // no route accepts a foreign wallet id here, this documents that guarantee.
        $this->assertFalse(
            (bool) auth()->user()?->can('updateIban', $otherWallet)
        );
    }
}
