<?php

namespace Tests\Feature\Api\V1\Staff;

use App\Models\Booking;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * تقویم، برنامه‌ی هفتگی و مرخصی در API اپ همکار (بسته‌ی ۲).
 */
class StaffCalendarApiTest extends TestCase
{
    use RefreshDatabase;

    protected Specialist $specialist;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // یکشنبه ۲۰۲۶-۱۰-۱۱
        $this->travelTo(Carbon::parse('2026-10-11 07:00:00'));
        $this->specialist = Specialist::factory()->create(['auto_confirm_bookings' => true]);
        $this->token = User::find($this->specialist->user_id)->createToken('A17', ['staff'], now()->addDays(90))->plainTextToken;
    }

    protected function api(): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($this->token);
    }

    public function test_calendar_combines_hours_holidays_leaves_and_bookings(): void
    {
        SpecialistSchedule::create(['specialist_id' => $this->specialist->id, 'day_of_week' => 0, 'start_time' => '09:00', 'end_time' => '17:00', 'break_start' => '13:00', 'break_end' => '14:00', 'is_active' => true]);
        Holiday::create(['specialist_id' => $this->specialist->id, 'date' => '2026-10-12', 'description' => 'تعطیل']);
        Leave::create(['specialist_id' => $this->specialist->id, 'start_date' => '2026-10-13', 'end_date' => '2026-10-14', 'status' => 'approved']);
        Leave::create(['specialist_id' => $this->specialist->id, 'start_date' => '2026-10-15', 'end_date' => '2026-10-15', 'status' => 'rejected']);
        $booking = Booking::factory()->create(['specialist_id' => $this->specialist->id, 'status' => 'confirmed', 'booking_time' => '2026-10-11 10:00:00']);
        Booking::factory()->create(['specialist_id' => $this->specialist->id, 'status' => 'cancelled', 'booking_time' => '2026-10-11 11:00:00']);

        $days = $this->api()->getJson('/api/v1/staff/calendar?from=2026-10-11&to=2026-10-15')->assertOk()->json('data');

        $this->assertCount(5, $days);
        $this->assertSame(['start_time' => '09:00', 'end_time' => '17:00', 'break_start' => '13:00', 'break_end' => '14:00'], $days[0]['working_hours']);
        $this->assertSame('یکشنبه', $days[0]['weekday_label']);
        $this->assertSame([$booking->id], array_column($days[0]['bookings'], 'id'));
        $this->assertSame('تعطیل', $days[1]['holiday']['description']);
        $this->assertNull($days[1]['working_hours']);
        $this->assertSame('approved', $days[2]['leave']['status']);
        $this->assertSame('approved', $days[3]['leave']['status']);
        $this->assertNull($days[4]['leave'], 'rejected leave is not shown');

        $this->api()->getJson('/api/v1/staff/calendar?from=2026-10-01&to=2026-12-01')
            ->assertStatus(422)->assertJsonPath('error.code', 'range_too_large');
    }

    public function test_schedule_round_trip_and_auto_confirm_untouched_when_absent(): void
    {
        $this->api()->putJson('/api/v1/staff/schedule', ['schedules' => [
            ['day_of_week' => 6, 'is_active' => true, 'start_time' => '10:00', 'end_time' => '18:00', 'break_start' => '14:00', 'break_end' => '15:00'],
            ['day_of_week' => 5, 'is_active' => false],
        ]])->assertOk()
            ->assertJsonPath('data.auto_confirm_bookings', true)
            ->assertJsonPath('data.schedules.6.is_active', true)
            ->assertJsonPath('data.schedules.6.start_time', '10:00')
            ->assertJsonPath('data.schedules.5.is_active', false);

        $this->assertSame(1, SpecialistSchedule::where('specialist_id', $this->specialist->id)->count());
        $this->assertTrue((bool) $this->specialist->fresh()->auto_confirm_bookings);

        $this->api()->putJson('/api/v1/staff/schedule', ['schedules' => [], 'auto_confirm_bookings' => false])
            ->assertOk()->assertJsonPath('data.auto_confirm_bookings', false);
        $this->assertSame(0, SpecialistSchedule::where('specialist_id', $this->specialist->id)->count());
    }

    public function test_schedule_validation_mirrors_the_web_form(): void
    {
        $bad = fn (array $rows) => $this->api()->putJson('/api/v1/staff/schedule', ['schedules' => $rows])->assertStatus(422)->json('error.fields');

        $this->assertArrayHasKey('schedules.0.end_time', $bad([['day_of_week' => 1, 'is_active' => true, 'start_time' => '18:00', 'end_time' => '09:00']]));
        $this->assertArrayHasKey('schedules.0.start_time', $bad([['day_of_week' => 1, 'is_active' => true, 'end_time' => '17:00']]));
        $this->assertArrayHasKey('schedules.1.day_of_week', $bad([
            ['day_of_week' => 1, 'is_active' => true, 'start_time' => '09:00', 'end_time' => '17:00'],
            ['day_of_week' => 1, 'is_active' => true, 'start_time' => '09:00', 'end_time' => '17:00'],
        ]));
    }

    public function test_leave_request_list_conflict_and_delete_rules(): void
    {
        $created = $this->api()->postJson('/api/v1/staff/leaves', ['start_date' => '2026-10-20', 'end_date' => '2026-10-21', 'reason' => 'سفر'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.start_date_jalali', '1405/07/28');

        $this->api()->postJson('/api/v1/staff/leaves', ['start_date' => '2026-10-01', 'end_date' => '2026-10-02'])
            ->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');

        Booking::factory()->create(['specialist_id' => $this->specialist->id, 'status' => 'confirmed', 'booking_time' => '2026-10-25 10:00:00']);
        $this->api()->postJson('/api/v1/staff/leaves', ['start_date' => '2026-10-25', 'end_date' => '2026-10-25'])
            ->assertStatus(422)->assertJsonPath('error.code', 'leave_conflict');

        $this->api()->getJson('/api/v1/staff/leaves')->assertOk()->assertJsonPath('meta.total', 1);

        $approved = Leave::create(['specialist_id' => $this->specialist->id, 'start_date' => '2026-11-01', 'end_date' => '2026-11-01', 'status' => 'approved']);
        $this->api()->deleteJson('/api/v1/staff/leaves/'.$approved->id)->assertStatus(409)->assertJsonPath('error.code', 'leave_not_pending');

        $foreign = Leave::create(['specialist_id' => Specialist::factory()->create()->id, 'start_date' => '2026-11-02', 'end_date' => '2026-11-02', 'status' => 'pending']);
        $this->api()->deleteJson('/api/v1/staff/leaves/'.$foreign->id)->assertNotFound();
        $this->assertDatabaseHas('leaves', ['id' => $foreign->id]);

        $this->api()->deleteJson('/api/v1/staff/leaves/'.$created->json('data.id'))->assertOk();
        $this->assertDatabaseMissing('leaves', ['id' => $created->json('data.id')]);
    }

    public function test_web_schedule_form_still_turns_auto_confirm_off_when_unchecked(): void
    {
        $this->actingAs(User::find($this->specialist->user_id))->put(route('specialist.schedule.update'), [
            'schedules' => [['day_of_week' => 1, 'is_active' => 1, 'start_time' => '09:00', 'end_time' => '17:00']],
        ])->assertRedirect(route('specialist.my-dashboard'));

        $this->assertFalse((bool) $this->specialist->fresh()->auto_confirm_bookings);
        $this->assertSame(1, SpecialistSchedule::where('specialist_id', $this->specialist->id)->count());
    }
}
