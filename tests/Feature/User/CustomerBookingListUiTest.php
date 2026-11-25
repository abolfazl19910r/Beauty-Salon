<?php

namespace Tests\Feature\User;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Salon;
use App\Models\User;
use App\Models\WalletSetting;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * «نوبت‌های من» مشتری (بسته‌ی ۴ ظاهری، ۲۰۲۶-۱۱-۲۵): تب‌ها با شمار، یک کار اصلی با نام در هر ردیف، منوی «⋯» با پیامد مالی لغو،
 * و رفع ستون وضعیت خالی نوبت انجام‌شده.
 */
class CustomerBookingListUiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /** @var array<string, Booking> */
    private array $b = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-25 14:00:00'));
        $this->user = User::factory()->create(['user_type' => 'customer', 'salon_id' => app(CurrentSalon::class)->id()]);
        $service = BeautyService::factory()->create(['duration' => 60, 'price' => 400000]);
        $make = fn (string $key, string $status, Carbon $time, array $extra = []) => $this->b[$key] = Booking::factory()->create(array_merge([
            'user_id' => $this->user->id, 'service_id' => $service->id, 'status' => $status, 'booking_time' => $time,
            'customer_course_id' => null, 'rating' => null, 'payment_status' => 'unpaid', 'prepayment_required' => false, 'prepayment_amount' => 0,
        ], $extra));

        $make('today', 'confirmed', now()->setTime(18, 0));
        $make('future', 'confirmed', now()->addDays(5)->setTime(11, 0), ['payment_status' => 'paid', 'prepayment_amount' => 100000, 'prepayment_required' => true]);
        $make('payable', 'pending_payment', now()->addDays(6)->setTime(12, 0), ['prepayment_amount' => 100000, 'prepayment_required' => true]);
        $make('done', 'completed', now()->subDays(30)->setTime(18, 0));
        $make('cancelled', 'cancelled', now()->addDays(3)->setTime(15, 0));
    }

    private function list(array $q = [])
    {
        return $this->actingAs($this->user)->get(route('bookings.index', $q))->assertOk();
    }

    private function row(string $html, string $key): string
    {
        return preg_match('/data-cu-row="'.$this->b[$key]->id.'".*?<\/li>/s', $html, $m) ? $m[0] : '';
    }

    public function test_tabs_with_counts(): void
    {
        $response = $this->list();
        $this->assertSame(['upcoming' => 3, 'past' => 1, 'cancelled' => 1], $response->viewData('tabCounts'));
        $this->assertSame([$this->b['today']->id, $this->b['future']->id, $this->b['payable']->id], collect($response->viewData('bookings')->items())->pluck('id')->all());
        $response->assertSee('پیش رو')->assertDontSee('همه وضعیت‌ها')->assertDontSee('jcal-popup', false);
    }

    public function test_one_named_primary_action_per_row(): void
    {
        $html = $this->list()->getContent();

        $this->assertStringContainsString('data-cu-action="details"', $this->row($html, 'today'));    // امروز، سالن بی مکان، کمتر از ۲۴ ساعت
        $this->assertStringContainsString('>امروز</span>', $this->row($html, 'today'));
        $this->assertStringContainsString('data-cu-action="reschedule"', $this->row($html, 'future'));
        $this->assertStringContainsString('data-cu-action="pay"', $this->row($html, 'payable'));
        $this->assertStringNotContainsString('data-cu-cancel', $this->row($html, 'today'));          // کمتر از ۲۴ ساعت
        $this->assertStringContainsString('data-cu-calendar', $this->row($html, 'future'));

        $past = $this->list(['tab' => 'past'])->getContent();
        $this->assertStringContainsString('data-cu-action="review"', $this->row($past, 'done'));
        $this->assertStringContainsString('data-cu-action="rebook"', $this->row($past, 'done'));
        $this->assertStringContainsString('data-booking-status="completed"', $this->row($past, 'done'));
        $this->assertStringContainsString('انجام‌شده', $this->row($past, 'done'));
    }

    public function test_today_row_offers_directions_when_the_salon_has_a_map_location(): void
    {
        Salon::where('id', app(CurrentSalon::class)->id())->update(['map_lat' => 35.76, 'map_lng' => 51.406]);
        app(CurrentSalon::class)->set(Salon::find(app(CurrentSalon::class)->id()));

        $row = $this->row($this->list()->getContent(), 'today');
        $this->assertStringContainsString('data-cu-action="directions"', $row);
        $this->assertStringContainsString('https://nshn.ir/?lat=35.76&amp;lng=51.406', $row);
    }

    public function test_cancel_menu_states_the_money_consequence(): void
    {
        WalletSetting::get()->update(['cancellation_before_hours' => 200, 'customer_cancellation_fee_percentage' => 20]);

        $html = $this->list()->getContent();
        $this->assertStringContainsString('۲۰٬۰۰۰ تومان جریمه‌ی لغو کم و ۸۰٬۰۰۰ تومان به کیف پول برمی‌گردد.', $this->row($html, 'future'));
        $this->assertStringContainsString('پولی پرداخت نشده؛ لغو هزینه‌ای ندارد.', $this->row($html, 'payable'));
        $this->assertStringContainsString('action="'.route('bookings.cancel', $this->b['future']).'"', $this->row($html, 'future'));

        $this->put(route('bookings.cancel', $this->b['future']))->assertRedirect();
        $this->assertSame('cancelled', $this->b['future']->fresh()->status);
    }
}
