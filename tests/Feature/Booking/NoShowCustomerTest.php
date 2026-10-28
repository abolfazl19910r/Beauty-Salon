<?php

namespace Tests\Feature\Booking;

use App\Exceptions\BookingLimitException;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\BookingPolicy;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Models\User;
use App\Models\WalletSetting;
use App\Services\Booking\BookingService;
use App\Services\Booking\NoShowRecord;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * مشتری بدقول (بسته‌ی 5ه، تصمیم ۱۶): پیش‌فرض ۲ «نیامد» در ۹۰ روز. با درگاه ← فقط با پیش‌پرداخت؛ بی درگاه ← تأیید دستی اجباری و سقف
 * کمتر (هر دو قابل تنظیم)؛ مدیر محدودیت را از صفحه‌ی مشتری برمی‌دارد.
 */
class NoShowCustomerTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private BeautyService $service;

    private Specialist $specialist;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        WalletSetting::get()->update(['prepayment_percentage' => 30, 'minimum_prepayment_amount' => 50000]);
        $this->customer = User::factory()->create(['user_type' => 'customer', 'phone' => '09125550000']);
        $this->service = BeautyService::factory()->create(['price' => 200000, 'duration' => 30]);
        $this->specialist = Specialist::factory()->create(['auto_confirm_bookings' => true]);
        $this->specialist->services()->attach($this->service->id);
        foreach (range(0, 6) as $day) {
            SpecialistSchedule::factory()->create(['specialist_id' => $this->specialist->id, 'day_of_week' => $day, 'start_time' => '08:00', 'end_time' => '20:00', 'is_active' => true]);
        }
    }

    private function noShow(int $daysAgo): void
    {
        Booking::factory()->create([
            'user_id' => $this->customer->id, 'specialist_id' => $this->specialist->id, 'service_id' => $this->service->id,
            'status' => 'no_show', 'no_show_at' => now()->subDays($daysAgo), 'booking_time' => now()->subDays($daysAgo)->setTime(9 + $daysAgo % 8, 0),
        ]);
    }

    private function book(int $days = 3): Booking
    {
        return app(BookingService::class)->createBooking($this->customer->id, $this->service->id, $this->specialist->id, now()->addDays($days)->setTime(10, 0)->format('Y-m-d H:i:s'));
    }

    private function withoutGateway(): void
    {
        app(CurrentSalon::class)->get()->paymentGateways()->update(['is_active' => false]);
    }

    public function test_two_no_shows_within_ninety_days_flag_the_customer(): void
    {
        $record = app(NoShowRecord::class);
        $this->noShow(120);
        $this->noShow(10);
        $this->assertFalse($record->isFlagged($this->customer), 'یکی بیرون از بازه‌ی ۹۰ روز است');

        $this->noShow(5);
        $this->assertTrue($record->isFlagged($this->customer));
        $this->assertSame('بدقول: ۲ بار نیامد در ۹۰ روز', $record->label($this->customer));
        $this->assertSame([$this->customer->id => 'بدقول: ۲ بار نیامد در ۹۰ روز'], $record->labelsFor([$this->customer->id, 999]));
    }

    public function test_with_a_gateway_a_flagged_customer_must_prepay_even_without_prepayment_mode(): void
    {
        $this->noShow(10);
        $this->noShow(20);
        $this->assertSame(BookingPolicy::MODE_NONE, BookingPolicy::current()->payment_mode);

        $booking = $this->book();

        $this->assertTrue($booking->prepayment_required);
        $this->assertSame('pending_payment', $booking->status);
        $this->assertSame(60000.0, (float) $booking->prepayment_amount);

        $other = User::factory()->create(['user_type' => 'customer']);
        $normal = app(BookingService::class)->createBooking($other->id, $this->service->id, $this->specialist->id, now()->addDays(4)->setTime(10, 0)->format('Y-m-d H:i:s'));
        $this->assertFalse($normal->prepayment_required, 'بقیه‌ی مشتری‌ها همچنان بدون پیش‌پرداخت');
    }

    public function test_without_a_gateway_booking_stays_open_with_forced_confirmation_and_a_lower_limit(): void
    {
        $this->withoutGateway();
        $this->noShow(10);
        $this->noShow(20);

        $first = $this->book(3);
        $this->assertFalse($first->prepayment_required);
        $this->assertSame('pending', $first->status, 'تأیید دستی اجباری حتی با متخصص تأیید خودکار');

        $this->expectException(BookingLimitException::class);
        $this->book(5);
    }

    public function test_rescheduling_keeps_the_forced_confirmation(): void
    {
        $this->withoutGateway();
        $booking = $this->book(3);
        $booking->update(['status' => 'confirmed']);
        $this->noShow(10);
        $this->noShow(20);

        app(\App\Services\Booking\BookingRescheduleService::class)->reschedule($booking->fresh(), now()->addDays(3)->setTime(15, 0)->format('Y-m-d H:i:s'));

        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_the_admin_can_turn_off_or_change_both_protections(): void
    {
        $this->withoutGateway();
        $this->noShow(10);
        $this->noShow(20);
        BookingPolicy::current()->update(['flagged_force_pending' => false, 'flagged_active_limit' => 2]);

        $this->assertSame('confirmed', $this->book(3)->status);
        $this->assertSame('confirmed', $this->book(5)->status);
        $this->expectException(BookingLimitException::class);
        $this->book(7);
    }

    public function test_the_admin_lifts_the_restriction_from_the_customer_page(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $this->noShow(10);
        $this->noShow(20);

        $this->actingAs($owner)->get(route('admin.customers.show', $this->customer->id))->assertOk()
            ->assertSee('بدقول: ۲ بار نیامد در ۹۰ روز')->assertSee('فقط با پیش‌پرداخت آنلاین می‌تواند رزرو کند.')->assertSee('برداشتن محدودیت');

        $this->actingAs($owner)->post(route('admin.customers.no-show.clear', $this->customer->id))
            ->assertRedirect(route('admin.customers.show', $this->customer->id));

        $this->assertFalse(app(NoShowRecord::class)->isFlagged($this->customer->fresh()));
        $this->assertFalse($this->book()->prepayment_required);
        $this->actingAs($owner)->get(route('admin.customers.show', $this->customer->id))->assertSee('محدودیت در');
    }

    public function test_staff_see_the_label_on_the_booking_and_in_the_staff_app(): void
    {
        $this->withoutGateway();
        $this->noShow(10);
        $this->noShow(20);
        $booking = $this->book();
        $owner = User::factory()->create(['is_admin' => true]);

        $this->actingAs($owner)->get(route('admin.bookings.show', $booking))->assertOk()->assertSee('بدقول: ۲ بار نیامد در ۹۰ روز');
        $this->actingAs($owner)->get(route('admin.bookings.index'))->assertOk()->assertSee('بدقول: ۲ بار نیامد در ۹۰ روز');

        $presented = \App\Services\Api\StaffBookingPresenter::present($booking->load(['user', 'service']));
        $this->assertSame('بدقول: ۲ بار نیامد در ۹۰ روز', $presented['customer']['no_show_label']);
    }

    public function test_the_customer_is_told_why_in_the_app_quote(): void
    {
        $this->noShow(10);
        $this->noShow(20);
        $token = $this->customer->forceFill(['phone_verified_at' => now()])->createToken('RN8', ['customer'], now()->addDays(90))->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/customer/bookings/quote', ['service_id' => $this->service->id])->assertOk()
            ->assertJsonPath('data.prepayment_required', true)
            ->assertJsonPath('data.prepayment_reason', 'no_show')
            ->assertJsonPath('data.notice', 'به‌خاطر نیامدن در نوبت‌های قبلی، رزرو آنلاین شما در این سالن فقط با پیش‌پرداخت ممکن است.');
    }

    public function test_the_owner_sets_the_rule_on_the_booking_mode_page(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $this->actingAs($owner)->get(route('admin.booking-policy.edit'))->assertOk()->assertSee('مشتری بدقول');

        $this->actingAs($owner)->put(route('admin.booking-policy.no-show'), [
            'no_show_threshold' => 3, 'no_show_window_days' => 60, 'flagged_force_pending' => '0', 'flagged_active_limit' => '',
        ])->assertRedirect(route('admin.booking-policy.edit'));

        $policy = BookingPolicy::current();
        $this->assertSame([3, 60, false, null], [$policy->no_show_threshold, $policy->no_show_window_days, $policy->flagged_force_pending, $policy->flagged_active_limit]);
    }
}
