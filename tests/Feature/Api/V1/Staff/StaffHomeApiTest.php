<?php

namespace Tests\Feature\Api\V1\Staff;

use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بسته‌ی ۵د: خانه‌ی اپ همکار (GET staff/home)، تب‌های نوبت‌ها (tab + q با شمار) و کار اصلی هر نوبت — همان قاعده‌های پنل وب کارشناس.
 */
class StaffHomeApiTest extends TestCase
{
    use RefreshDatabase;

    private Specialist $specialist;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setTime(10, 0));
        $this->specialist = Specialist::factory()->create();
        $this->token = User::find($this->specialist->user_id)->createToken('A17', ['staff'], now()->addDays(90))->plainTextToken;
    }

    private function booking(string $status, \DateTimeInterface $at, array $extra = []): Booking
    {
        return Booking::factory()->create(['specialist_id' => $this->specialist->id, 'status' => $status, 'payment_status' => 'unpaid', 'booking_time' => $at] + $extra);
    }

    private function api(): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($this->token);
    }

    public function test_home_has_the_next_booking_numbers_and_the_coming_week(): void
    {
        $next = $this->booking('pending', now()->addDay()->setTime(14, 30));
        $later = $this->booking('confirmed', now()->addDays(3)->setTime(9, 0));
        $this->booking('cancelled', now()->addDays(2)->setTime(9, 0));

        $this->api()->getJson('/api/v1/staff/home')->assertOk()
            ->assertJsonPath('data.next.id', $next->id)
            ->assertJsonPath('data.next.primary_action', 'confirm')
            ->assertJsonPath('data.next.day_label', 'فردا '.\App\Support\Fa::date($next->booking_time))
            ->assertJsonPath('data.next.time_label', '۱۴:۳۰')
            ->assertJsonPath('data.waiting_count', 1)
            ->assertJsonPath('data.withdrawable', 0)
            ->assertJsonPath('data.upcoming.0.id', $later->id)
            ->assertJsonCount(1, 'data.upcoming')
            ->assertJsonStructure(['data' => ['date_label', 'month_label', 'month_income', 'rating', 'today_count']]);
    }

    public function test_home_without_bookings(): void
    {
        $this->api()->getJson('/api/v1/staff/home')->assertOk()
            ->assertJsonPath('data.next', null)
            ->assertJsonPath('data.upcoming', []);
    }

    public function test_primary_action_follows_the_web_rules(): void
    {
        $future = $this->booking('confirmed', now()->addDays(5)->setTime(17, 30));
        $started = $this->booking('confirmed', now()->subHour());
        $pastPending = $this->booking('pending', now()->subHours(2));

        $get = fn (Booking $b) => $this->api()->getJson('/api/v1/staff/bookings/'.$b->id)->assertOk();

        $get($future)->assertJsonPath('data.primary_action', 'details')->assertJsonPath('data.actions.complete', false);
        $get($started)->assertJsonPath('data.primary_action', 'complete')->assertJsonPath('data.actions.complete', true);
        $get($pastPending)->assertJsonPath('data.primary_action', 'details')->assertJsonPath('data.actions.confirm', false)
            ->assertJsonPath('data.actions.cancel', true);
    }

    public function test_bookings_by_tab_with_counts_and_search(): void
    {
        $customer = User::factory()->create(['name' => 'ظریفه شریعتمداری', 'phone' => '09125550000']);
        $other = User::factory()->create(['name' => 'مریم احمدی', 'phone' => '09351110000']);
        $waiting = $this->booking('pending', now()->addDays(2), ['user_id' => $customer->id]);
        $this->booking('confirmed', now()->addDays(1), ['user_id' => $other->id]);
        $this->booking('completed', now()->subDays(2), ['user_id' => $other->id]);
        $this->booking('cancelled', now()->addDays(3), ['user_id' => $other->id]);

        $this->api()->getJson('/api/v1/staff/bookings?tab=upcoming')->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.counts', ['upcoming' => 2, 'pending' => 1, 'past' => 1, 'cancelled' => 1])
            ->assertJsonPath('meta.tab', 'upcoming');

        $this->api()->getJson('/api/v1/staff/bookings?tab=pending&q='.urlencode('ظریفه'))->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $waiting->id)
            ->assertJsonPath('meta.counts.upcoming', 1);

        $this->api()->getJson('/api/v1/staff/bookings?tab=pending&q=۰۹۱۲۵۵۵')->assertOk()->assertJsonCount(1, 'data');
        $this->api()->getJson('/api/v1/staff/bookings?tab=nope')->assertStatus(422);
    }

    public function test_tab_never_shows_another_specialists_bookings(): void
    {
        Booking::factory()->create(['specialist_id' => Specialist::factory()->create()->id, 'status' => 'pending', 'booking_time' => now()->addDay()]);

        $this->api()->getJson('/api/v1/staff/bookings?tab=upcoming')->assertOk()->assertJsonCount(0, 'data');
    }
}
