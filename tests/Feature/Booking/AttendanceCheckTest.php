<?php

namespace Tests\Feature\Booking;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\BookingPolicy;
use App\Models\Specialist;
use App\Models\User;
use App\Services\SMSService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * تأیید حضور با لینک (بسته‌ی 5ه، تصمیم ۱۶-۳): کلید مدیر (خاموش / بدقول‌ها / همه)؛ ۲۴ ساعت پیش پیامک با لینک، بی تأیید تا ۳ ساعت
 * مانده لغو خودکار.
 */
class AttendanceCheckTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{0: string, 1: string}> */
    private array $sms = [];

    private bool $smsWorks = true;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->mock(SMSService::class, function ($mock) {
            $mock->shouldReceive('send')->andReturnUsing(function ($phone, $text) {
                $this->sms[] = [$phone, $text];

                return $this->smsWorks;
            });
            $mock->shouldIgnoreMissing(true);
        });
    }

    private function booking(int $hoursAhead, array $extra = []): Booking
    {
        return Booking::factory()->withoutPrepayment()->create(array_merge([
            'user_id' => User::factory()->create(['user_type' => 'customer', 'phone' => '0912'.random_int(1000000, 9999999)])->id,
            'service_id' => BeautyService::factory()->create(['price' => 300000])->id,
            'specialist_id' => Specialist::factory()->create(['phone' => '0935'.random_int(1000000, 9999999)])->id,
            'status' => 'confirmed', 'booking_time' => now()->addHours($hoursAhead)->startOfHour(),
        ], $extra));
    }

    private function runChecks(): void
    {
        $this->artisan('bookings:attendance-checks')->assertSuccessful();
    }

    private function linkSentTo(Booking $booking): ?string
    {
        foreach ($this->sms as [$phone, $text]) {
            if ($phone === $booking->user->phone && str_contains($text, 'تایید کنید')) {
                // لینک کوتاه بی‌پروتکل (ShortLinkService::host): «127.0.0.1:8000/b/xxxxxx»
                preg_match('~\S+/b/[a-z0-9]+~', $text, $m);

                return isset($m[0]) ? 'http://'.$m[0] : null;
            }
        }

        return null;
    }

    public function test_nothing_happens_while_it_is_off(): void
    {
        $booking = $this->booking(20);
        $this->runChecks();

        $this->assertSame([], $this->sms);
        $this->assertNull($booking->fresh()->attendance_requested_at);
    }

    public function test_the_link_goes_out_within_24_hours_and_confirming_keeps_the_booking(): void
    {
        BookingPolicy::current()->update(['attendance_check' => BookingPolicy::ATTENDANCE_ALL]);
        $soon = $this->booking(20);
        $later = $this->booking(30);
        $prepaid = $this->booking(21, ['prepayment_required' => true, 'payment_status' => 'paid', 'prepayment_amount' => 90000]);

        $this->runChecks();

        $this->assertNotNull($soon->fresh()->attendance_requested_at);
        $this->assertNull($later->fresh()->attendance_requested_at, 'هنوز ۲۴ ساعت نمانده');
        $this->assertNull($prepaid->fresh()->attendance_requested_at, 'نوبت با پیش‌پرداخت تعهد دارد');
        $this->assertNotNull($link = $this->linkSentTo($soon));

        // لینک کوتاه ← صفحه‌ی تأیید (GET فقط نشان می‌دهد؛ تأیید با دکمه)
        $target = $this->get($link)->assertRedirect()->headers->get('Location');
        $this->get($target)->assertOk()->assertSee('حضورم را تأیید می‌کنم');
        $this->assertNull($soon->fresh()->attendance_confirmed_at);
        $this->post($target)->assertRedirect()->assertSessionHas('success');
        $this->assertNotNull($soon->fresh()->attendance_confirmed_at);

        $this->travel(18)->hours();
        $this->runChecks();
        $this->assertSame('confirmed', $soon->fresh()->status);
    }

    public function test_an_unconfirmed_booking_is_cancelled_three_hours_before_and_both_sides_are_told(): void
    {
        BookingPolicy::current()->update(['attendance_check' => BookingPolicy::ATTENDANCE_ALL]);
        $booking = $this->booking(20);
        $this->runChecks();
        $this->sms = [];

        $this->travel(16)->hours();
        $this->runChecks();
        $this->assertSame('confirmed', $booking->fresh()->status, '۴ ساعت مانده: هنوز مهلت دارد');

        $this->travel(2)->hours();
        $this->runChecks();

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status);
        $this->assertSame('system', $booking->cancelled_by);
        $this->assertSame('حضور تأیید نشد', $booking->cancellation_reason);
        $phones = array_column($this->sms, 0);
        $this->assertContains($booking->user->phone, $phones, 'خبر لغو به مشتری');
        $this->assertContains($booking->specialist->phone, $phones, 'خبر لغو به متخصص');
    }

    public function test_a_booking_whose_message_could_not_be_sent_is_never_cancelled(): void
    {
        BookingPolicy::current()->update(['attendance_check' => BookingPolicy::ATTENDANCE_ALL]);
        $this->smsWorks = false;
        $booking = $this->booking(20);

        $this->runChecks();
        $this->travel(18)->hours();
        $this->runChecks();

        $this->assertNull($booking->fresh()->attendance_requested_at);
        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_flagged_only_mode_asks_only_unreliable_customers(): void
    {
        BookingPolicy::current()->update(['attendance_check' => BookingPolicy::ATTENDANCE_FLAGGED]);
        app(CurrentSalon::class)->get()->paymentGateways()->update(['is_active' => false]);
        $flagged = $this->booking(20);
        foreach ([10, 20] as $days) {
            Booking::factory()->create(['user_id' => $flagged->user_id, 'status' => 'no_show', 'no_show_at' => now()->subDays($days), 'booking_time' => now()->subDays($days)]);
        }
        $normal = $this->booking(21);

        $this->runChecks();

        $this->assertNotNull($flagged->fresh()->attendance_requested_at);
        $this->assertNull($normal->fresh()->attendance_requested_at);
    }

    public function test_the_owner_turns_it_on_from_the_booking_mode_page(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $this->actingAs($owner)->get(route('admin.booking-policy.edit'))->assertOk()->assertSee('تأیید حضور با لینک');
        $this->actingAs($owner)->put(route('admin.booking-policy.attendance'), ['attendance_check' => 'all'])->assertRedirect(route('admin.booking-policy.edit'));

        $this->assertSame(BookingPolicy::ATTENDANCE_ALL, BookingPolicy::current()->attendance_check);
    }
}
