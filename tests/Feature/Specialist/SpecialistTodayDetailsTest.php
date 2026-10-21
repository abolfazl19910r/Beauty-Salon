<?php

namespace Tests\Feature\Specialist;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * «برنامه‌ی امروز» متخصص، ۳-۱ گزینه‌ی ج (۲۰۲۶-۱۰-۲۲) — وب و API اپ همکار:
 * - وضعیت پیش‌پرداخت و «دریافت در سالن» درست: نوبت دستی پرداخت‌نشده (prepayment_amount پیش‌فرض ۵۰٬۰۰۰ دارد) کل قیمت منهای تخفیف،
 *   پرداخت‌شده همان باقی‌مانده (قبلاً برای پرداخت‌نشده هم «قیمت − ۵۰٬۰۰۰» نشان داده می‌شد)؛
 * - «نیامد» در فهرست امروز می‌ماند (با برچسب) ولی در شمار نوبت‌ها نه؛
 * - لغوشده‌های امروز در بخش جمع‌شده با لغوکننده و دلیل.
 */
class SpecialistTodayDetailsTest extends TestCase
{
    use RefreshDatabase;

    private Specialist $specialist;

    private BeautyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setTime(7, 0));
        $this->specialist = Specialist::factory()->create();
        $this->service = BeautyService::factory()->create(['price' => 400000]);
    }

    private function booking(string $status, string $payment, int $hour, array $extra = []): Booking
    {
        return Booking::factory()->create(array_merge([
            'specialist_id' => $this->specialist->id,
            'service_id' => $this->service->id,
            'status' => $status,
            'payment_status' => $payment,
            'prepayment_amount' => 50000,
            'discount_amount' => 0,
            'customer_course_id' => null,
            'booking_time' => now()->setTime($hour, 0),
        ], $extra));
    }

    public function test_due_at_salon_counts_an_unpaid_prepayment(): void
    {
        $manual = $this->booking('confirmed', 'unpaid', 9, ['discount_amount' => 20000]);
        $paid = $this->booking('confirmed', 'paid', 10, ['discount_amount' => 20000]);

        $this->assertSame(380000.0, $manual->due_at_salon);
        $this->assertSame(330000.0, $paid->due_at_salon);
        $this->assertSame(330000.0, $manual->remaining_amount); // نمای مشتری دست نخورد
    }

    public function test_web_today_shows_payment_due_no_show_and_collapsed_cancellations(): void
    {
        $manual = $this->booking('confirmed', 'unpaid', 9);
        $noShow = $this->booking('no_show', 'paid', 10);
        $cancelled = $this->booking('cancelled', 'paid', 11, ['cancelled_by' => 'customer', 'cancellation_reason' => 'سفر پیش آمد']);
        $this->booking('pending_payment', 'unpaid', 12);

        $response = $this->actingAs(User::find($this->specialist->user_id))->get(route('specialist.my-dashboard'))->assertOk();

        $this->assertSame([$manual->id, $noShow->id], $response->viewData('todaySchedule')->pluck('id')->all());
        $this->assertSame(1, $response->viewData('todayBookingsCount'));
        $this->assertSame([$cancelled->id], $response->viewData('todayCancelled')->pluck('id')->all());

        $response->assertSee('data-payment="unpaid"', false)
            ->assertSee('پیش‌پرداخت نشده')
            ->assertSee('دریافت در سالن: '.number_format(400000), false)
            ->assertSee('نیامد')
            ->assertSee('data-today-cancelled', false)
            ->assertSee('data-cancelled-booking="'.$cancelled->id.'"', false)
            ->assertSee('لغو توسط مشتری')
            ->assertSee('سفر پیش آمد');
    }

    public function test_booking_page_tells_to_collect_the_whole_price_when_prepayment_is_unpaid(): void
    {
        $manual = $this->booking('confirmed', 'unpaid', 9);

        $this->actingAs(User::find($this->specialist->user_id))->get(route('specialist.bookings.show', $manual))->assertOk()
            ->assertSee('پیش‌پرداخت انجام نشده')
            ->assertSeeInOrder(['data-due-at-salon', number_format(400000)], false);
    }

    public function test_staff_api_today_has_due_no_show_and_cancelled(): void
    {
        $manual = $this->booking('confirmed', 'unpaid', 9);
        $noShow = $this->booking('no_show', 'paid', 10);
        $cancelled = $this->booking('cancelled', 'paid', 11, ['cancelled_by' => 'admin']);

        $token = User::find($this->specialist->user_id)->createToken('A17', ['staff'], now()->addDays(90))->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/staff/today')->assertOk()
            ->assertJsonPath('data.summary.count', 1)
            ->assertJsonPath('data.summary.cancelled_count', 1)
            ->assertJsonPath('data.bookings.0.id', $manual->id)
            ->assertJsonPath('data.bookings.0.due_at_salon', 400000)
            ->assertJsonPath('data.bookings.1.id', $noShow->id)
            ->assertJsonPath('data.bookings.1.status_label', 'نیامد')
            ->assertJsonPath('data.cancelled.0.id', $cancelled->id)
            ->assertJsonPath('data.cancelled.0.cancelled_by', 'admin');
    }
}
