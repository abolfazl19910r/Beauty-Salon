<?php

namespace Tests\Feature\Specialist;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Specialist\SpecialistDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * داشبورد کارشناس (بسته‌ی ۳ ظاهری، ۲۰۲۶-۱۱-۲۵؛ تصمیم‌های «الف»): نوبت بعدی از همین لحظه با کار مستقیم، درآمد ماه شمسی از
 * کیف پول، قابل برداشت، امتیاز بی نظر، ۷ روز آینده از فردا، میله‌های ۷ روزه، و حذف «آمار کل»، دکمه‌ی «مدیریت نوبت‌ها» و ApexCharts.
 */
class SpecialistDashboardRefreshTest extends TestCase
{
    use RefreshDatabase;

    private Specialist $specialist;

    private BeautyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // چهارشنبه ۴ آذر ۱۴۰۵، ساعت ۱۴:۰۰
        $this->travelTo(Carbon::parse('2026-11-25 14:00:00'));
        $this->specialist = Specialist::factory()->create();
        $this->service = BeautyService::factory()->create(['price' => 400000, 'duration' => 60]);
    }

    private function booking(string $status, Carbon $time, array $extra = []): Booking
    {
        return Booking::factory()->create(array_merge([
            'specialist_id' => $this->specialist->id,
            'service_id' => $this->service->id,
            'status' => $status,
            'payment_status' => 'unpaid',
            'booking_time' => $time,
            'customer_course_id' => null,
            'discount_amount' => 0,
        ], $extra));
    }

    private function dashboard()
    {
        return $this->actingAs(User::find($this->specialist->user_id))->get(route('specialist.my-dashboard'))->assertOk();
    }

    public function test_next_booking_skips_finished_ones_and_offers_confirm(): void
    {
        $this->booking('confirmed', now()->setTime(11, 0));            // تمام شده
        $running = $this->booking('confirmed', now()->setTime(13, 30)); // هنوز در جریان
        $later = $this->booking('pending', now()->setTime(16, 0));

        $response = $this->dashboard();
        $this->assertSame($running->id, $response->viewData('nextBooking')->id);
        $this->assertSame('complete', $response->viewData('nextAction')['kind']);

        $running->update(['status' => 'completed']);
        $response = $this->dashboard();
        $this->assertSame($later->id, $response->viewData('nextBooking')->id);
        $response->assertSee('منتظر تأیید شما')
            ->assertSee('تأیید نوبت')
            ->assertSee('action="'.route('specialist.bookings.complete', $later).'"', false)
            ->assertSee('data-ui-confirm="نوبت', false)
            ->assertSee('data-call-customer', false)
            ->assertSee('۱۶:۰۰');
    }

    public function test_future_confirmed_booking_gets_details_not_done(): void
    {
        $b = $this->booking('confirmed', now()->addDay()->setTime(10, 0));

        $this->assertSame('details', SpecialistDashboardService::primaryAction($b)['kind']);
        $this->assertSame('complete', SpecialistDashboardService::primaryAction($b, now()->addDay()->setTime(10, 5))['kind']);
        $this->assertSame('confirm', SpecialistDashboardService::primaryAction($this->booking('pending', now()->addDays(2)))['kind']);

        $this->dashboard()->assertSee('data-spec-action="details"', false)->assertDontSee('data-spec-action="complete"', false);
    }

    public function test_month_income_is_the_wallet_net_income_of_the_jalali_month(): void
    {
        $wallet = $this->specialist->getOrCreateWallet();
        $wallet->update(['balance' => 1939500]);
        $tx = fn (string $type, int $amount, string $at, ?int $bookingId = null) => WalletTransaction::query()->forceCreate([
            'wallet_id' => $wallet->id, 'type' => $type, 'amount' => $amount, 'balance_after' => 0,
            'booking_id' => $bookingId, 'description' => 'test',
            'created_at' => $at, 'updated_at' => $at,
        ]);

        $tx('income', 300000, '2026-11-22 10:00:00'); // ۱ آذر: همین ماه
        $tx('income', 500000, '2026-11-25 09:00:00'); // امروز
        $tx('income', 700000, '2026-11-20 10:00:00'); // ۲۹ آبان: ماه قبل (در ماه میلادی نوامبر بود)
        $tx('withdrawal', -200000, '2026-11-24 10:00:00'); // برداشت درآمد نیست

        $response = $this->dashboard();
        $this->assertSame(800000.0, $response->viewData('monthIncome'));
        $this->assertSame('آذر', $response->viewData('monthLabel'));
        $this->assertSame(1939500.0, $response->viewData('withdrawable'));
        $response->assertSee('درآمد آذر')->assertSee(fa_money(800000))->assertSee(fa_money(1939500));

        $week = $response->viewData('weeklyIncome');
        $this->assertCount(7, $week);
        $this->assertSame(500000.0, $week[6]['total']);
        $this->assertTrue($week[6]['today']);
        $this->assertSame(700000.0, $week[1]['total']); // ۲۰ نوامبر
        $this->assertSame('۲۹', $week[1]['day']);
    }

    public function test_rating_without_reviews_says_so_and_old_blocks_are_gone(): void
    {
        $response = $this->dashboard();

        $this->assertNull($response->viewData('averageRating'));
        $response->assertSee('هنوز نظری ثبت نشده')
            ->assertDontSee('آمار کل</h2>', false)->assertDontSee('کل نوبت‌ها</p>', false)
            ->assertDontSee('مدیریت نوبت‌ها')
            ->assertDontSee('apexcharts', false)
            ->assertDontSee('۰٫۰')
            ->assertSee('نوبت پیش رویی ندارید');
    }

    public function test_upcoming_list_starts_tomorrow_and_uses_persian_dates(): void
    {
        $this->booking('confirmed', now()->setTime(18, 0));
        $tomorrow = $this->booking('pending', now()->addDay()->setTime(16, 0));
        $this->booking('cancelled', now()->addDays(2)->setTime(12, 0));

        $response = $this->dashboard();
        $this->assertSame([$tomorrow->id], $response->viewData('upcomingDays')->pluck('id')->all());
        $response->assertSee('data-upcoming-booking="'.$tomorrow->id.'"', false)->assertSee(fa_date($tomorrow->booking_time));
    }

    public function test_app_strip_is_at_the_bottom(): void
    {
        $html = $this->dashboard()->getContent();

        $this->assertLessThan(strpos($html, 'data-staff-app-promo hidden'), strpos($html, 'data-spec-upcoming'));
    }
}
