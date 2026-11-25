<?php

namespace Tests\Feature\User;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * داشبورد مشتری (بسته‌ی ۴ ظاهری، ۲۰۲۶-۱۱-۲۵؛ تصمیم‌های «الف»): نوبت امروز/بعدی با مسیریابی (فقط با مکان سالن)، تقویم و تغییر
 * زمان، کیف پول، باشگاه، نوبت‌های بعدی، «نظرتان چه بود؟»، «دوباره رزرو کنید»؛ حذف بخش‌های تکراری قدیم.
 */
class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Specialist $specialist;

    private BeautyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-25 14:00:00')); // چهارشنبه ۴ آذر
        $this->user = User::factory()->create(['user_type' => 'customer', 'salon_id' => app(CurrentSalon::class)->id()]);
        $this->specialist = Specialist::factory()->create(['name' => 'نگار']);
        $this->service = BeautyService::factory()->create(['name' => 'کوتاهی مو', 'duration' => 60, 'price' => 300000]);
    }

    private function booking(string $status, Carbon $time, array $extra = []): Booking
    {
        return Booking::factory()->create(array_merge([
            'user_id' => $this->user->id, 'specialist_id' => $this->specialist->id, 'service_id' => $this->service->id,
            'status' => $status, 'booking_time' => $time, 'customer_course_id' => null, 'rating' => null,
        ], $extra));
    }

    private function dashboard()
    {
        return $this->actingAs($this->user)->get(route('dashboard'))->assertOk()->assertViewIs('dashboard');
    }

    public function test_today_card_with_calendar_and_deadline_and_the_rest_in_one_list(): void
    {
        $today = $this->booking('confirmed', now()->setTime(18, 0));
        $later = $this->booking('pending', now()->addDays(5)->setTime(11, 0));
        $this->booking('cancelled', now()->addDays(2)->setTime(12, 0));
        $this->booking('confirmed', now()->setTime(10, 0)); // تمام شده

        $response = $this->dashboard();
        $this->assertSame($today->id, $response->viewData('nextBooking')->id);
        $this->assertTrue($response->viewData('nextIsToday'));
        $this->assertSame([$later->id], $response->viewData('upcoming')->pluck('id')->all());

        $response->assertSee('نوبت امروز')->assertSee('امروز یک نوبت دارید.')
            ->assertSee('href="'.route('bookings.calendar', $today).'"', false)
            ->assertDontSee('data-reschedule', false) // کمتر از ۲۴ ساعت
            ->assertSee('کمتر از ۲۴ ساعت به نوبت مانده')
            ->assertDontSee('data-directions', false)  // سالن مکان روی نقشه ندارد
            ->assertDontSee('نوبت‌های اخیر')
            ->assertDontSee('دسترسی سریع');
    }

    public function test_next_booking_on_another_day_can_be_changed_and_shows_directions_with_a_map_location(): void
    {
        $next = $this->booking('confirmed', now()->addDays(3)->setTime(17, 0));
        Salon::where('id', app(CurrentSalon::class)->id())->update(['map_lat' => 35.76, 'map_lng' => 51.406]);
        app(CurrentSalon::class)->set(Salon::find(app(CurrentSalon::class)->id()));

        $this->dashboard()
            ->assertSee('نوبت بعدی، '.fa_date($next->booking_time))
            ->assertSee('href="'.route('bookings.reschedule', $next).'"', false)
            ->assertSee('تغییر زمان یا لغو تا ۲۴ ساعت پیش از نوبت ممکن است.')
            ->assertSee('href="https://nshn.ir/?lat=35.76&amp;lng=51.406"', false);
    }

    public function test_rate_prompt_and_rebook_with_the_same_expert(): void
    {
        $done = $this->booking('completed', now()->subDays(10)->setTime(10, 0), ['rating' => null, 'review' => null]);
        $popular = BeautyService::factory()->create(['category_id' => $this->service->category_id, 'name' => 'رنگ مو']);
        $someoneElse = User::factory()->create(['user_type' => 'customer', 'salon_id' => app(CurrentSalon::class)->id()]);
        foreach ([20, 21] as $daysAgo) {
            Booking::factory()->create(['user_id' => $someoneElse->id, 'service_id' => $popular->id, 'status' => 'completed', 'booking_time' => now()->subDays($daysAgo)]);
        }

        $response = $this->dashboard();
        $this->assertSame($done->id, $response->viewData('toRate')->id);
        $this->assertSame($popular->id, $response->viewData('suggestion')->id);
        $response->assertSee('نظرتان چه بود؟')
            ->assertSee('action="'.route('bookings.rate', $done).'"', false)
            ->assertSee('name="rating" value="5"', false)
            ->assertSee('href="'.e(route('bookings.create', ['service' => $this->service->id, 'specialist' => $this->specialist->id])).'"', false)
            ->assertSee('data-rebook-suggestion="'.$popular->id.'"', false);

        $this->post(route('bookings.rate', $done), ['rating' => 4])->assertRedirect();
        $this->assertSame(4, (int) $done->fresh()->rating);
        $this->dashboard()->assertDontSee('نظرتان چه بود؟');
    }

    public function test_empty_state_and_wallet_card(): void
    {
        $this->dashboard()->assertSee('نوبت پیش رویی ندارید')->assertSee('موجودی کیف پول')
            ->assertSee('href="'.route('wallet.charge').'"', false);
    }

    public function test_dashboard_only_shows_the_authenticated_users_own_bookings(): void
    {
        $this->booking('confirmed', now()->addDays(2)->setTime(10, 0));
        $other = User::factory()->create();
        Booking::factory()->create(['user_id' => $other->id, 'status' => 'confirmed', 'booking_time' => now()->addDay()]);

        $this->assertSame($this->user->id, $this->dashboard()->viewData('nextBooking')->user_id);
    }

    public function test_calendar_file_for_own_booking_only(): void
    {
        $booking = $this->booking('confirmed', now()->addDays(3)->setTime(17, 0));

        $response = $this->actingAs($this->user)->get(route('bookings.calendar', $booking))->assertOk();
        $this->assertStringStartsWith('text/calendar', $response->headers->get('Content-Type'));
        $ics = $response->getContent();
        $this->assertStringContainsString('BEGIN:VEVENT', $ics);
        $this->assertStringContainsString('DTSTART:'.$booking->booking_time->copy()->utc()->format('Ymd\THis\Z'), $ics);
        $this->assertStringContainsString('DTEND:'.$booking->booking_time->copy()->addMinutes(60)->utc()->format('Ymd\THis\Z'), $ics);
        $this->assertStringContainsString('SUMMARY:کوتاهی مو', $ics);

        $other = User::factory()->create(['user_type' => 'customer', 'salon_id' => app(CurrentSalon::class)->id()]);
        $this->actingAs($other)->get(route('bookings.calendar', $booking))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('salon.login'));
    }
}
