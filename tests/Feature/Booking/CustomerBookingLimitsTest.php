<?php

namespace Tests\Feature\Booking;

use App\Exceptions\BookingLimitException;
use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\BookingPolicy;
use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Models\User;
use App\Services\Booking\BookingRescheduleService;
use App\Services\Booking\BookingService;
use App\Services\Courses\CourseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * سقف نوبت آنلاین مشتری (بسته‌ی 5ه، تصمیم‌های ۱۲ تا ۱۵): روزانه (پیش‌فرض ۱) و کل نوبت‌های آینده‌ی فعال (پیش‌فرض ۳).
 */
class CustomerBookingLimitsTest extends TestCase
{
    use CreatesCourses;
    use RefreshDatabase;

    private User $customer;

    private BeautyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->customer = User::factory()->create(['user_type' => 'customer']);
        $this->service = BeautyService::factory()->create(['price' => 200000, 'duration' => 30]);
    }

    private function specialist(): Specialist
    {
        $specialist = Specialist::factory()->create(['auto_confirm_bookings' => true]);
        $specialist->services()->attach($this->service->id);
        foreach (range(0, 6) as $day) {
            SpecialistSchedule::factory()->create(['specialist_id' => $specialist->id, 'day_of_week' => $day, 'start_time' => '00:00', 'end_time' => '23:59', 'is_active' => true]);
        }

        return $specialist;
    }

    private function at(int $days, string $time): string
    {
        return now()->addDays($days)->format('Y-m-d').' '.$time.':00';
    }

    private function book(Specialist $specialist, string $time, ?User $user = null): Booking
    {
        return app(BookingService::class)->createBooking(($user ?? $this->customer)->id, $this->service->id, $specialist->id, $time);
    }

    public function test_one_online_booking_per_day_by_default_even_with_another_specialist(): void
    {
        $this->book($this->specialist(), $this->at(2, '10:00'));

        try {
            $this->book($this->specialist(), $this->at(2, '15:00'));
            $this->fail('second booking on the same day was accepted');
        } catch (BookingLimitException $e) {
            $this->assertSame('برای هر روز حداکثر یک نوبت آنلاین ممکن است؛ برای خدمت دوم با سالن تماس بگیرید.', $e->getUserMessage());
        }

        $this->book($this->specialist(), $this->at(3, '10:00'));
        $this->assertSame(2, Booking::where('user_id', $this->customer->id)->count());
        $this->book($this->specialist(), $this->at(2, '15:00'), User::factory()->create(['user_type' => 'customer']));
    }

    public function test_the_day_is_the_booking_day_not_the_day_it_was_made(): void
    {
        $specialist = $this->specialist();
        $this->book($specialist, $this->at(2, '23:00'));
        $this->book($specialist, $this->at(3, '00:30'));

        $this->assertSame(2, Booking::where('user_id', $this->customer->id)->count());
    }

    public function test_only_active_bookings_count(): void
    {
        $specialist = $this->specialist();
        foreach (['cancelled', 'completed', 'no_show'] as $i => $status) {
            Booking::factory()->create(['user_id' => $this->customer->id, 'specialist_id' => $specialist->id, 'service_id' => $this->service->id,
                'status' => $status, 'booking_time' => $this->at(2, sprintf('%02d:00', 8 + $i))]);
        }

        $this->book($specialist, $this->at(2, '15:00'));
        $this->assertSame(4, Booking::where('user_id', $this->customer->id)->count());
    }

    public function test_the_admin_can_raise_or_remove_the_daily_limit(): void
    {
        BookingPolicy::current()->update(['daily_limit' => 2]);
        $specialist = $this->specialist();
        $this->book($specialist, $this->at(2, '10:00'));
        $this->book($specialist, $this->at(2, '12:00'));
        $this->expectException(BookingLimitException::class);
        $this->expectExceptionMessage('limit 2');
        $this->book($specialist, $this->at(2, '14:00'));
    }

    public function test_an_empty_limit_means_no_limit(): void
    {
        BookingPolicy::current()->update(['daily_limit' => null, 'active_limit' => 0]);
        $specialist = $this->specialist();
        foreach (['09:00', '11:00', '13:00', '15:00', '17:00'] as $time) {
            $this->book($specialist, $this->at(2, $time));
        }

        $this->assertSame(5, Booking::where('user_id', $this->customer->id)->count());
    }

    public function test_at_most_three_active_future_bookings_by_default(): void
    {
        $specialist = $this->specialist();
        foreach ([2, 3, 4] as $day) {
            $this->book($specialist, $this->at($day, '10:00'));
        }

        try {
            $this->book($specialist, $this->at(5, '10:00'));
            $this->fail('fourth active booking was accepted');
        } catch (BookingLimitException $e) {
            $this->assertStringContainsString('حداکثر ۳ نوبت آینده‌ی فعال', $e->getUserMessage());
        }

        Booking::where('user_id', $this->customer->id)->orderBy('id')->first()->update(['status' => 'cancelled']);
        $this->book($specialist, $this->at(5, '10:00'));
    }

    /**
     * تصمیم ابوالفضل ۲۰۲۶-۱۰-۲۹: نوبت با پیش‌پرداخت (تعهد مالی) از سقف کل نوبت‌های آینده معاف است و در آن شمرده نمی‌شود؛ سقف روزانه
     * همچنان برقرار است.
     */
    public function test_prepaid_bookings_are_exempt_from_the_active_limit_but_not_the_daily_one(): void
    {
        BookingPolicy::current()->update(['payment_mode' => BookingPolicy::MODE_PREPAY]);
        $specialist = $this->specialist();
        foreach ([2, 3, 4, 5] as $day) {
            $booking = $this->book($specialist, $this->at($day, '10:00'));
            $this->assertTrue((bool) $booking->prepayment_required);
        }

        try {
            $this->book($specialist, $this->at(5, '15:00'));
            $this->fail('second prepaid booking on the same day was accepted');
        } catch (BookingLimitException $e) {
            $this->assertStringContainsString('برای هر روز', $e->getUserMessage());
        }

        // برگشت به «بدون پیش‌پرداخت»: نوبت‌های پیش‌پرداختی قبلی جای سقف را نمی‌گیرند
        BookingPolicy::current()->update(['payment_mode' => BookingPolicy::MODE_NONE]);
        Booking::where('user_id', $this->customer->id)->update(['status' => 'confirmed', 'payment_status' => 'paid']);
        foreach ([6, 7, 8] as $day) {
            $this->assertFalse((bool) $this->book($specialist, $this->at($day, '10:00'))->prepayment_required);
        }
        $this->expectException(BookingLimitException::class);
        $this->book($specialist, $this->at(9, '10:00'));
    }

    public function test_moving_a_booking_to_a_day_with_another_booking_is_refused_but_within_the_same_day_is_fine(): void
    {
        $specialist = $this->specialist();
        $first = $this->book($specialist, $this->at(3, '10:00'));
        $this->book($specialist, $this->at(4, '10:00'));

        app(BookingRescheduleService::class)->reschedule($first->fresh(), $this->at(3, '16:00'));
        $this->assertSame($this->at(3, '16:00'), $first->fresh()->booking_time->format('Y-m-d H:i:s'));

        $this->expectException(BookingLimitException::class);
        app(BookingRescheduleService::class)->reschedule($first->fresh(), $this->at(4, '16:00'));
    }

    public function test_course_sessions_count_and_staff_bookings_are_exempt_with_a_warning(): void
    {
        $this->setUpCourseWorld();
        $course = $this->paidCourse();
        $specialist = $this->specialist();
        $this->book($specialist, $this->sessionAt(5, '12:00'), $this->courseCustomer);

        try {
            app(CourseService::class)->bookSession($course, $this->courseSpecialist->id, $this->sessionAt(5, '16:00'));
            $this->fail('course session on a day with another online booking was accepted');
        } catch (BookingLimitException) {
        }

        // مدیر: معاف
        $session = app(CourseService::class)->bookSession($course, $this->courseSpecialist->id, $this->sessionAt(5, '16:00'), byStaff: true);
        $this->assertNotNull($session->id);

        $owner = User::factory()->create(['is_admin' => true]);
        $this->actingAs($owner)->post(route('admin.bookings.store'), [
            'user_id' => $this->courseCustomer->id, 'service_id' => $this->service->id, 'specialist_id' => $specialist->id,
            'booking_time' => $this->sessionAt(5, '18:00'), 'status' => 'confirmed', 'payment_status' => 'unpaid', 'source' => 'phone',
        ])->assertSessionHas('warning', fn ($m) => str_contains($m, 'این مشتری در همین روز ۲ نوبت فعال دیگر'));
    }

    /** آزمون دستی ۵ (۲۰۲۶-۱۰-۲۹، «نوار زرد دیده نشد»): هشدار روی صفحه‌ی نوبت تازه هم دیده می‌شود، نه فقط در نشست */
    public function test_the_staff_warning_is_shown_on_the_new_booking_page(): void
    {
        $specialist = $this->specialist();
        $this->book($specialist, $this->at(3, '10:00'));
        $owner = User::factory()->create(['is_admin' => true]);

        $this->actingAs($owner)->followingRedirects()->post(route('admin.bookings.store'), [
            'user_id' => $this->customer->id, 'service_id' => $this->service->id, 'specialist_id' => $this->specialist()->id,
            'booking_time' => $this->at(3, '15:00'), 'status' => 'pending', 'payment_status' => 'unpaid', 'source' => 'phone',
        ])->assertOk()->assertSee('هشدار: این مشتری در همین روز ۱ نوبت فعال دیگر هم دارد.');
    }

    public function test_the_api_and_the_web_say_why(): void
    {
        $specialist = $this->specialist();
        $this->book($specialist, $this->at(2, '10:00'));

        $this->actingAs($this->customer)->post(route('bookings.store'), [
            'service_id' => $this->service->id, 'specialist_id' => $specialist->id, 'booking_time' => $this->at(2, '15:00'),
        ])->assertSessionHas('error', fn ($m) => str_contains($m, 'برای خدمت دوم با سالن تماس بگیرید'));

        $this->actingAs($this->customer)->post(route('bookings.confirm'), [
            'service_id' => $this->service->id, 'specialist_id' => $specialist->id, 'booking_time' => $this->at(2, '15:00'),
        ])->assertSessionHas('error', fn ($m) => str_contains($m, 'حداکثر یک نوبت آنلاین'));

        $token = $this->customer->forceFill(['phone_verified_at' => now()])->createToken('RN8', ['customer'], now()->addDays(90))->plainTextToken;
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->postJson('/api/v1/customer/bookings', [
            'service_id' => $this->service->id, 'specialist_id' => $specialist->id,
            'booking_time' => now()->addDays(2)->setTime(15, 0)->toIso8601String(),
        ])->assertStatus(422)->assertJsonPath('error.code', 'booking_limit')
            ->assertJsonPath('error.message', 'برای هر روز حداکثر یک نوبت آنلاین ممکن است؛ برای خدمت دوم با سالن تماس بگیرید.');
    }

    public function test_the_owner_sets_the_limits_on_the_booking_mode_page(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);
        $this->actingAs($owner)->get(route('admin.booking-policy.edit'))->assertOk()->assertSee('سقف نوبت هر مشتری');

        $this->actingAs($owner)->put(route('admin.booking-policy.limits'), ['daily_limit' => 2, 'active_limit' => ''])
            ->assertRedirect(route('admin.booking-policy.edit'));

        $policy = BookingPolicy::current();
        $this->assertSame(2, $policy->daily_limit);
        $this->assertNull($policy->active_limit);
    }
}
