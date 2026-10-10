<?php

namespace Tests\Feature\Api\V1\Staff;

use App\Events\Booking\BookingCancelled;
use App\Models\Booking;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * نوبت‌های متخصص در API اپ «ماهرو همکار» (بسته‌ی ۲).
 */
class StaffBookingApiTest extends TestCase
{
    use RefreshDatabase;

    protected Specialist $specialist;

    protected string $token;

    protected int $hour = 8;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setTime(7, 0));
        $this->specialist = Specialist::factory()->create();
        $this->token = User::find($this->specialist->user_id)->createToken('A17', ['staff'], now()->addDays(90))->plainTextToken;
    }

    protected function booking(string $status, string $payment = 'paid', int $day = 0, ?Specialist $specialist = null): Booking
    {
        return Booking::factory()->create([
            'specialist_id' => ($specialist ?? $this->specialist)->id,
            'status' => $status,
            'payment_status' => $payment,
            'booking_time' => now()->addDays($day)->setTime($this->hour++, 0),
        ]);
    }

    protected function api(): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($this->token);
    }

    public function test_today_lists_active_bookings_with_summary(): void
    {
        $manual = $this->booking('confirmed', 'unpaid');
        $pending = $this->booking('pending', 'paid');
        $this->booking('cancelled', 'paid');
        $this->booking('pending_payment', 'unpaid');
        $this->booking('confirmed', 'paid', 1);

        $response = $this->api()->getJson('/api/v1/staff/today');

        $response->assertOk()
            ->assertJsonPath('data.summary.count', 2)
            ->assertJsonPath('data.summary.pending_count', 1)
            ->assertJsonPath('data.bookings.0.id', $manual->id)
            ->assertJsonPath('data.bookings.0.actions', ['confirm' => false, 'cancel' => true, 'complete' => true])
            ->assertJsonPath('data.bookings.1.id', $pending->id)
            ->assertJsonPath('data.bookings.1.status_label', 'در انتظار تأیید')
            ->assertJsonStructure(['data' => ['date', 'date_jalali', 'bookings' => [['booking_time', 'booking_time_jalali', 'customer' => ['name', 'phone'], 'service']]]]);
    }

    public function test_index_filters_by_range_and_status_and_never_shows_other_specialists(): void
    {
        $other = Specialist::factory()->create();
        $inRange = $this->booking('confirmed', 'paid', 2);
        $cancelled = $this->booking('cancelled', 'paid', 3);
        $this->booking('confirmed', 'paid', 40);
        $this->booking('pending_payment', 'unpaid', 2);
        $foreign = $this->booking('confirmed', 'paid', 2, $other);

        $all = $this->api()->getJson('/api/v1/staff/bookings?from='.now()->toDateString().'&to='.now()->addDays(10)->toDateString());
        $all->assertOk()->assertJsonPath('meta.total', 2);
        $ids = collect($all->json('data'))->pluck('id');
        $this->assertEqualsCanonicalizing([$inRange->id, $cancelled->id], $ids->all());
        $this->assertFalse($ids->contains($foreign->id));

        $this->api()->getJson('/api/v1/staff/bookings?status=cancelled&from='.now()->toDateString())
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $cancelled->id);

        $this->api()->getJson('/api/v1/staff/bookings?from=2026-01-01&to=2026-12-31')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'range_too_large');
    }

    public function test_another_specialists_booking_is_404_for_read_and_write(): void
    {
        $sameSalon = $this->booking('pending', 'paid', 1, Specialist::factory()->create());
        $otherSalon = Salon::factory()->create();
        $foreign = app(CurrentSalon::class)->withSalon($otherSalon, fn () => Booking::factory()->create([
            'specialist_id' => Specialist::factory()->create()->id,
            'status' => 'pending',
            'booking_time' => now()->addDay()->setTime(15, 0),
        ]));

        foreach ([$sameSalon, $foreign] as $booking) {
            $this->api()->getJson('/api/v1/staff/bookings/'.$booking->id)->assertNotFound()->assertJsonPath('error.code', 'not_found');
            $this->api()->postJson('/api/v1/staff/bookings/'.$booking->id.'/confirm')->assertNotFound();
            $this->assertSame('pending', $booking->fresh()->status);
        }
    }

    public function test_confirm_is_idempotent_and_refuses_non_pending(): void
    {
        $pending = $this->booking('pending', 'paid', 1);
        $cancelled = $this->booking('cancelled', 'paid', 1);

        $this->api()->postJson('/api/v1/staff/bookings/'.$pending->id.'/confirm')
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('meta.changed', true);
        $this->api()->postJson('/api/v1/staff/bookings/'.$pending->id.'/confirm')
            ->assertOk()
            ->assertJsonPath('meta.changed', false);

        $this->api()->postJson('/api/v1/staff/bookings/'.$cancelled->id.'/confirm')
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'invalid_booking_state');
        $this->assertSame('cancelled', $cancelled->fresh()->status);
    }

    public function test_cancel_with_reason_and_complete(): void
    {
        Event::fake([BookingCancelled::class]);
        $toCancel = $this->booking('confirmed', 'paid', 1);
        $toComplete = $this->booking('confirmed', 'paid');
        $completed = $this->booking('completed', 'paid');

        $this->api()->postJson('/api/v1/staff/bookings/'.$toCancel->id.'/cancel', ['reason' => 'بیماری'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.cancellation_reason', 'بیماری')
            ->assertJsonPath('data.cancelled_by', 'specialist');
        Event::assertDispatched(BookingCancelled::class);

        $this->api()->postJson('/api/v1/staff/bookings/'.$toComplete->id.'/complete')
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->api()->postJson('/api/v1/staff/bookings/'.$completed->id.'/cancel')
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'invalid_booking_state');
    }

    public function test_customer_token_cannot_reach_staff_routes(): void
    {
        $customer = User::factory()->create(['user_type' => 'customer', 'salon_id' => app(CurrentSalon::class)->id()]);
        $this->token = $customer->createToken('x', ['customer'], now()->addDays(90))->plainTextToken;

        $this->api()->getJson('/api/v1/staff/today')->assertForbidden()->assertJsonPath('error.code', 'wrong_app');
    }
}
