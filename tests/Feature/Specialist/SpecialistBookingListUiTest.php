<?php

namespace Tests\Feature\Specialist;

use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * فهرست نوبت‌های کارشناس (بسته‌ی ۳ ظاهری، ۲۰۲۶-۱۱-۲۵؛ تصمیم‌های «الف»): تب‌ها با شمار، جستجو، یک کار اصلی هم‌نام در هر ردیف،
 * منوی «⋯» با «نیامد» برای تأییدشده‌ی گذشته و «لغو نوبت…»، و «منتظر پرداخت» بی کار اصلی.
 */
class SpecialistBookingListUiTest extends TestCase
{
    use RefreshDatabase;

    private Specialist $specialist;

    /** @var array<string, Booking> */
    private array $b = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-25 14:00:00'));
        $this->specialist = Specialist::factory()->create();

        $customer = fn (string $name, string $phone) => User::factory()->create(['name' => $name, 'phone' => $phone, 'user_type' => 'customer']);
        $other = $customer('نیلوفر احمدی', '09129990000');
        $make = fn (string $key, string $status, Carbon $time, ?User $user = null) => $this->b[$key] = Booking::factory()->create([
            'specialist_id' => $this->specialist->id,
            'status' => $status,
            'booking_time' => $time,
            'user_id' => ($user ?? $other)->id,
        ]);

        $make('todayDone', 'confirmed', now()->setTime(11, 0), $customer('سارا رضایی', '09121110001'));
        $make('pendingSoon', 'pending', now()->setTime(16, 0), $customer('مینا کریمی', '09121110002'));
        $make('confirmedLater', 'confirmed', now()->addDays(2)->setTime(10, 0));
        $make('atGateway', 'pending_payment', now()->addDay()->setTime(12, 0));
        $make('pendingPast', 'pending', now()->subDays(3)->setTime(10, 0));
        $make('completed', 'completed', now()->subDay()->setTime(10, 0));
        $make('noShow', 'no_show', now()->subDays(2)->setTime(10, 0));
        $make('cancelled', 'cancelled', now()->addDay()->setTime(15, 0));
    }

    private function list(array $query = [])
    {
        return $this->actingAs(User::find($this->specialist->user_id))->get(route('specialist.bookings.index', $query))->assertOk();
    }

    private function ids($response): array
    {
        return $response->viewData('bookings')->pluck('id')->all();
    }

    public function test_tabs_split_bookings_with_counts(): void
    {
        $response = $this->list();

        $this->assertSame('upcoming', $response->viewData('tab'));
        $this->assertSame([$this->b['todayDone']->id, $this->b['pendingSoon']->id, $this->b['atGateway']->id, $this->b['confirmedLater']->id], $this->ids($response));
        $this->assertSame(['upcoming' => 4, 'pending' => 1, 'past' => 3, 'cancelled' => 1], $response->viewData('tabCounts'));

        $this->assertSame([$this->b['pendingSoon']->id], $this->ids($this->list(['tab' => 'pending'])));
        $this->assertSame([$this->b['completed']->id, $this->b['noShow']->id, $this->b['pendingPast']->id], $this->ids($this->list(['tab' => 'past'])));
        $this->assertSame([$this->b['cancelled']->id], $this->ids($this->list(['tab' => 'cancelled'])));

        $response->assertSee('پیش رو')->assertSee('منتظر تأیید')->assertSee('۱ نوبت منتظر تأیید شماست.');
        $this->get(route('specialist.bookings.index', ['tab' => 'nope']))->assertSessionHasErrors('tab');
    }

    public function test_menu_badge_and_waiting_tab_count_are_the_same_number(): void
    {
        $html = $this->list()->getContent();

        $this->assertMatchesRegularExpression('/data-spec-nav-item="bookings"[^>]*>.*?data-spec-badge[^>]*>۱</s', $html);
        $this->assertMatchesRegularExpression('/data-spec-tab="pending"[^>]*>\s*منتظر تأیید <span[^>]*>۱</s', $html);
    }

    public function test_search_by_name_or_persian_digit_phone_keeps_the_tab(): void
    {
        $this->assertSame([$this->b['pendingSoon']->id], $this->ids($this->list(['q' => 'مینا'])));
        $this->assertSame([$this->b['todayDone']->id], $this->ids($this->list(['q' => '۱۱۱۰۰۰۱'])));
        $this->assertSame(1, $this->list(['q' => 'مینا'])->viewData('tabCounts')['upcoming']);
        $this->list(['q' => 'مینا', 'tab' => 'past'])->assertSee('نوبتی با «مینا» در این بخش پیدا نشد.')
            ->assertSee('href="'.route('specialist.bookings.index', ['tab' => 'past']).'"', false);
    }

    public function test_one_primary_action_per_row_with_the_same_names(): void
    {
        $html = $this->list()->getContent();

        $row = fn (string $key) => preg_match('/data-booking-row="'.$this->b[$key]->id.'".*?<\/li>/s', $html, $m) ? $m[0] : '';

        $this->assertStringContainsString('data-spec-action="complete"', $row('todayDone'));
        $this->assertStringContainsString('>انجام شد</button>', $row('todayDone'));
        $this->assertStringContainsString('data-spec-no-show', $row('todayDone'));
        $this->assertStringContainsString('action="'.route('specialist.bookings.no-show', $this->b['todayDone']).'"', $row('todayDone'));

        $this->assertStringContainsString('>تأیید</button>', $row('pendingSoon'));
        $this->assertStringContainsString('action="'.route('specialist.bookings.complete', $this->b['pendingSoon']).'"', $row('pendingSoon'));
        $this->assertStringNotContainsString('data-spec-no-show', $row('pendingSoon'));

        $this->assertStringContainsString('data-spec-action="details"', $row('confirmedLater'));
        $this->assertStringNotContainsString('data-spec-no-show', $row('confirmedLater'));

        // منتظر پرداخت: برچسب خودش، بی کار اصلی و بی لغو
        $this->assertStringContainsString('data-booking-status="pending_payment"', $row('atGateway'));
        $this->assertStringNotContainsString('data-spec-action', $row('atGateway'));
        $this->assertStringNotContainsString('data-spec-cancel', $row('atGateway'));
        $this->assertStringContainsString('جزئیات نوبت', $row('atGateway'));

        // منتظر تأییدی که ساعتش گذشته: «جزئیات»، نه «تأیید»؛ لغو در منو می‌ماند (آزمون ویندوز ۱۱-۲۵)
        $past = $this->list(['tab' => 'past'])->getContent();
        $this->assertSame(1, preg_match('/data-booking-row="'.$this->b['pendingPast']->id.'".*?<\/li>/s', $past, $pm));
        $this->assertStringContainsString('data-spec-action="details"', $pm[0]);
        $this->assertStringNotContainsString('data-spec-action="confirm"', $pm[0]);
        $this->assertStringContainsString('data-spec-cancel', $pm[0]);

        foreach (['todayDone', 'pendingSoon', 'confirmedLater'] as $key) {
            $this->assertStringContainsString('data-spec-cancel', $row($key));
        }
        foreach (['todayDone', 'pendingSoon'] as $key) {
            $this->assertStringContainsString('data-ui-confirm="نوبت', $row($key)); // تأیید و انجام شد هر دو با پرسش
        }

        $this->assertStringNotContainsString('تایید', $html);
        $this->assertStringNotContainsString('filterForm', $html);
        $this->assertStringContainsString('امروز، ۴ آذر', $html);
    }

    public function test_row_actions_still_work_end_to_end(): void
    {
        $user = User::find($this->specialist->user_id);
        $this->actingAs($user);

        $this->put(route('specialist.bookings.complete', $this->b['pendingSoon']))->assertRedirect();
        $this->assertSame('confirmed', $this->b['pendingSoon']->fresh()->status);

        $this->put(route('specialist.bookings.no-show', $this->b['todayDone']))->assertRedirect();
        $this->assertSame('no_show', $this->b['todayDone']->fresh()->status);

        $this->put(route('specialist.bookings.cancel', $this->b['confirmedLater']), ['cancel_reason' => 'بیماری'])->assertRedirect();
        $this->assertSame('cancelled', $this->b['confirmedLater']->fresh()->status);
    }
}
