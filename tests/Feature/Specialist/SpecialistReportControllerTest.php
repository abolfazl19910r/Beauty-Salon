<?php

namespace Tests\Feature\Specialist;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecialistReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingSpecialist(float $commissionRate = 10.0): array
    {
        $user = User::factory()->create(['phone' => '09121234567']);
        $specialist = Specialist::factory()->create([
            'phone' => '09121234567',
            'user_id' => $user->id,
            'commission_rate' => $commissionRate,
        ]);

        return [$user, $specialist];
    }

    public function test_index_shows_profile_not_found_when_no_specialist_matches_the_user(): void
    {
        $user = User::factory()->create(['phone' => '09129999999']);

        $response = $this->actingAs($user)->get(route('specialist.reports.index'));

        $response->assertOk();
        $response->assertViewIs('specialist.profile-not-found');
    }

    public function test_index_defaults_to_the_last_thirty_days_without_a_date_range(): void
    {
        [$user, $specialist] = $this->actingSpecialist();
        $recent = Booking::factory()->create([
            'specialist_id' => $specialist->id,
            'booking_time' => now()->subDays(5),
            'payment_status' => 'paid',
            'prepayment_amount' => 100000,
        ]);
        $old = Booking::factory()->create([
            'specialist_id' => $specialist->id,
            'booking_time' => now()->subDays(60),
            'payment_status' => 'paid',
            'prepayment_amount' => 100000,
        ]);

        $response = $this->actingAs($user)->get(route('specialist.reports.index'));

        $ids = $response->viewData('bookings')->pluck('id');
        $this->assertTrue($ids->contains($recent->id));
        $this->assertFalse($ids->contains($old->id));
    }

    public function test_revenue_is_computed_after_the_specialists_own_commission_rate(): void
    {
        [$user, $specialist] = $this->actingSpecialist(commissionRate: 20.0);
        Booking::factory()->create([
            'specialist_id' => $specialist->id,
            'booking_time' => now()->subDays(2),
            'payment_status' => 'paid',
            'status' => 'completed',
            'prepayment_amount' => 100000,
        ]);

        $response = $this->actingAs($user)->get(route('specialist.reports.index'));

        // 100000 raw, minus 20% commission => 80000 net to the specialist.
        $this->assertSame(80000.0, $response->viewData('totalRevenue'));
    }

    public function test_cancelled_bookings_are_excluded_from_revenue(): void
    {
        [$user, $specialist] = $this->actingSpecialist(commissionRate: 0.0);
        Booking::factory()->create([
            'specialist_id' => $specialist->id,
            'booking_time' => now()->subDay(),
            'payment_status' => 'paid',
            'status' => 'cancelled',
            'prepayment_amount' => 100000,
        ]);

        $response = $this->actingAs($user)->get(route('specialist.reports.index'));

        $this->assertSame(0.0, $response->viewData('totalRevenue'));
    }

    public function test_index_filters_by_status(): void
    {
        [$user, $specialist] = $this->actingSpecialist();
        Booking::factory()->create([
            'specialist_id' => $specialist->id, 'booking_time' => now()->subDay(), 'status' => 'completed',
        ]);
        Booking::factory()->create([
            'specialist_id' => $specialist->id, 'booking_time' => now()->subDay(), 'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get(route('specialist.reports.index', ['status' => 'completed']));

        $this->assertSame(1, $response->viewData('totalBookings'));
    }

    public function test_index_filters_by_service(): void
    {
        [$user, $specialist] = $this->actingSpecialist();
        $serviceA = BeautyService::factory()->create();
        $serviceB = BeautyService::factory()->create();
        $specialist->services()->attach([$serviceA->id, $serviceB->id]);

        Booking::factory()->create([
            'specialist_id' => $specialist->id, 'service_id' => $serviceA->id, 'booking_time' => now()->subDay(),
        ]);
        Booking::factory()->create([
            'specialist_id' => $specialist->id, 'service_id' => $serviceB->id, 'booking_time' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->get(route('specialist.reports.index', ['service_id' => $serviceA->id]));

        $this->assertSame(1, $response->viewData('totalBookings'));
    }

    public function test_index_filters_by_jalali_date_range(): void
    {
        [$user, $specialist] = $this->actingSpecialist();
        $inRange = Booking::factory()->create([
            'specialist_id' => $specialist->id, 'booking_time' => now()->setDate(2026, 4, 15),
        ]);
        Booking::factory()->create([
            'specialist_id' => $specialist->id, 'booking_time' => now()->setDate(2026, 6, 15),
        ]);

        $response = $this->actingAs($user)->get(route('specialist.reports.index', [
            'start_date' => '1405/01/01',
            'end_date' => '1405/02/28',
        ]));

        $ids = $response->viewData('bookings')->pluck('id');
        $this->assertTrue($ids->contains($inRange->id));
        $this->assertSame(1, $ids->count());
    }

    public function test_excel_export_streams_a_downloadable_file(): void
    {
        [$user, $specialist] = $this->actingSpecialist();
        Booking::factory()->create(['specialist_id' => $specialist->id, 'booking_time' => now()->subDay()]);

        $response = $this->actingAs($user)->get(route('specialist.reports.index', ['export' => 'excel']));

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml',
            $response->headers->get('Content-Type')
        );
    }

    public function test_a_specialist_cannot_see_another_specialists_bookings_in_their_report(): void
    {
        [$user, $specialist] = $this->actingSpecialist();
        $otherSpecialist = Specialist::factory()->create();
        Booking::factory()->create(['specialist_id' => $otherSpecialist->id, 'booking_time' => now()->subDay()]);

        $response = $this->actingAs($user)->get(route('specialist.reports.index'));

        $this->assertSame(0, $response->viewData('totalBookings'));
    }
}
