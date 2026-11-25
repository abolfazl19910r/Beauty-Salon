<?php

namespace Tests\Feature\Themes;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Models\User;
use App\Services\Booking\SalonFreeSlots;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * بسته‌ی ۵الف: ساعت‌های خالی صفحه‌ی اول قالب‌های پولی (SalonFreeSlots) و مسیر JSON فرم رزرو «شاهکار».
 */
class SalonFreeSlotsTest extends TestCase
{
    use RefreshDatabase;

    private BeautyService $service;

    private Specialist $first;

    private Specialist $second;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        // سه‌شنبه ۳ آذر ۱۴۰۵، ۱۸:۱۰ تهران
        Carbon::setTestNow(Carbon::parse('2026-11-24 18:10:00'));
        $this->service = BeautyService::factory()->create(['duration' => 60]);
        $this->first = Specialist::factory()->create(['name' => 'کارشناس اول']);
        $this->second = Specialist::factory()->create(['name' => 'کارشناس دوم']);
        $this->service->specialists()->attach([$this->first->id, $this->second->id]);
        // جمعه (۵) هیچ‌کس کار نمی‌کند؛ اول همه‌ی روزهای دیگر ۹ تا ۲۱، دوم فقط سه‌شنبه‌ها ۱۷ تا ۲۱
        foreach ([6, 0, 1, 2, 3, 4] as $day) {
            SpecialistSchedule::factory()->create(['specialist_id' => $this->first->id, 'day_of_week' => $day, 'start_time' => '09:00', 'end_time' => '21:00']);
        }
        SpecialistSchedule::factory()->create(['specialist_id' => $this->second->id, 'day_of_week' => 2, 'start_time' => '17:00', 'end_time' => '21:00']);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function salon()
    {
        return app(CurrentSalon::class)->get();
    }

    public function test_days_for_a_service_skip_closed_days_and_past_hours(): void
    {
        $days = app(SalonFreeSlots::class)->daysForService($this->salon(), $this->service);

        $this->assertCount(5, $days);
        $this->assertSame(['امروز', 'فردا', 'پنج‌شنبه', 'شنبه', 'یکشنبه'], array_column($days, 'label'), 'جمعه رد می‌شود');
        $this->assertSame('2026-11-24', $days[0]['date']);
        $this->assertSame('۳', $days[0]['day']);
        $today = array_column($days[0]['slots'], 'time');
        $this->assertNotContains('18:00', $today, 'ساعت گذشته‌ی امروز نیست');
        $this->assertSame('18:30', $today[0], 'همان قاعده‌ی getAvailableSlots: گذشته‌ها نیم‌ساعت نیم‌ساعت رد می‌شوند');
        $this->assertSame('۱۸:۳۰', $days[0]['slots'][0]['label']);
    }

    public function test_any_specialist_takes_the_first_one_free_at_that_time(): void
    {
        Booking::factory()->create([
            'specialist_id' => $this->first->id, 'service_id' => $this->service->id,
            'booking_time' => '2026-11-24 18:30:00', 'status' => 'confirmed',
        ]);

        $today = app(SalonFreeSlots::class)->daysForService($this->salon(), $this->service)[0]['slots'];
        $first = collect($today)->firstWhere('time', '18:30');

        $this->assertSame($this->second->id, $first['specialist_id'], 'اول ۱۸:۳۰ را دارد، دوم خالی است');

        $onlyFirst = app(SalonFreeSlots::class)->daysForService($this->salon(), $this->service, $this->first->id)[0]['slots'];
        $this->assertNotContains('18:30', array_column($onlyFirst, 'time'));
    }

    public function test_nearest_and_next_per_specialist(): void
    {
        $nearest = app(SalonFreeSlots::class)->nearest($this->salon(), 6);
        $this->assertCount(6, $nearest);
        $this->assertSame(['امروز', '18:30'], [$nearest[0]['day_label'], $nearest[0]['time']]);
        $this->assertSame(['18:30', '19:00', '19:30', '20:00', '20:30'], array_column(array_slice($nearest, 0, 5), 'time'), 'یکی برای هر ساعت');
        $this->assertSame('فردا', $nearest[5]['day_label']);

        $next = app(SalonFreeSlots::class)->nextForSpecialists($this->salon(), collect([$this->first, $this->second]));
        $this->assertSame('امروز ۱۸:۳۰', $next[$this->first->id]['text']);
        $this->assertSame('امروز ۱۸:۳۰', $next[$this->second->id]['text']);
    }

    public function test_a_paused_salon_shows_no_free_slots(): void
    {
        $this->salon()->forceFill(['paused_at' => now()])->save();

        $this->assertSame([], app(SalonFreeSlots::class)->daysForService($this->salon(), $this->service));
        $this->assertSame([], app(SalonFreeSlots::class)->nearest($this->salon()));
    }

    public function test_the_free_slots_endpoint_is_public_and_salon_scoped(): void
    {
        $this->getJson(route('salon.free-slots', ['service' => $this->service->id]))
            ->assertOk()->assertJsonPath('days.0.label', 'امروز')->assertJsonPath('days.0.slots.0.time', '18:30');

        $this->getJson(route('salon.free-slots', ['service' => $this->service->id, 'specialist' => $this->second->id]))
            ->assertOk()->assertJsonCount(1, 'days');

        $stranger = Specialist::factory()->create();
        $this->getJson(route('salon.free-slots', ['service' => $this->service->id, 'specialist' => $stranger->id]))->assertNotFound();
        $this->getJson(route('salon.free-slots', ['service' => 999999]))->assertNotFound();
        $this->getJson(route('salon.free-slots'))->assertUnprocessable();
    }

    public function test_the_booking_form_reads_date_and_time_from_the_link(): void
    {
        $customer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $this->salon()->id]);

        $this->actingAs($customer)->get(route('bookings.create', ['service' => $this->service->id, 'specialist' => $this->second->id, 'date' => '2026-11-24', 'time' => '19:00']))
            ->assertOk()
            ->assertSee('const prefill = (function () {', false);
    }
}
