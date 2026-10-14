<?php

namespace Tests\Feature\Courses;

use App\Models\Booking;
use App\Models\CourseSetting;
use App\Models\CustomerCourse;
use App\Models\DiscountCode;
use App\Models\Salon;
use App\Models\SpecialistWallet;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\WalletSetting;
use App\Services\Courses\CourseException;
use App\Services\Courses\CourseService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/** شمارش جلسه، پیش‌پرداخت، فاصله و مهلت، انجام/لغو، درآمد متخصص و لغو دوره (بسته‌ی ب، تصمیم‌های ۲۰۲۶-۱۰-۱۴) */
class CourseServiceTest extends TestCase
{
    use CreatesCourses;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCourseWorld();
    }

    private function service(): CourseService
    {
        return app(CourseService::class);
    }

    private function book(CustomerCourse $course, int $days, bool $byStaff = false, string $time = '10:00'): Booking
    {
        return $this->service()->bookSession($course, $this->courseSpecialist->id, $this->sessionAt($days, $time), $byStaff);
    }

    public function test_purchase_snapshots_the_package_and_waits_for_payment(): void
    {
        $package = $this->coursePackage();
        $course = $this->service()->purchase($this->courseCustomer, $package);

        $package->update(['price' => 9999000, 'sessions_count' => 10]);

        $course->refresh();
        $this->assertSame('pending_payment', $course->status);
        $this->assertSame(6, $course->sessions_total);
        $this->assertSame(4800000.0, $course->price_total);
        $this->assertSame(800000.0, $course->per_session_value);
        $this->assertSame('پرداخت دوره هنوز انجام نشده است.', $course->bookingBlocker());
    }

    public function test_full_payment_activates_the_course_and_starts_the_validity_window(): void
    {
        $course = $this->paidCourse();

        $this->assertSame('active', $course->status);
        $this->assertTrue($course->isFullyPaid());
        $this->assertSame(6, $course->paidSessions());
        $this->assertEqualsWithDelta(now()->addDays(365)->endOfDay()->timestamp, $course->expires_at->timestamp, 2);
        $this->assertNull($course->bookingBlocker());
    }

    public function test_prepayment_books_only_as_many_sessions_as_were_paid(): void
    {
        $package = $this->coursePackage(['min_prepayment_percent' => 30]);
        $course = $this->service()->purchase($this->courseCustomer, $package);
        $this->assertSame(1440000.0, $course->min_prepayment);

        $this->service()->recordPayment($course, 1000000, 'in_person');
        $this->assertSame('pending_payment', $course->fresh()->status, 'below the minimum prepayment');

        $this->service()->recordPayment($course, 600000, 'in_person'); // 1.6M = 2 جلسه (۸۰۰ هزاری)
        $course->refresh();
        $this->assertSame('active', $course->status);
        $this->assertSame(2, $course->paidSessions());

        $this->book($course, 1);
        $this->book($course, 30);
        $this->assertSame('برای رزرو جلسه‌ی بعد، باقی مبلغ دوره را بپردازید.', $course->fresh()->bookingBlocker());

        $this->service()->recordPayment($course, 10_000_000, 'in_person'); // بیش از مانده پذیرفته نمی‌شود
        $this->assertSame(4800000.0, $course->fresh()->amount_paid);
        $this->assertNull($course->fresh()->bookingBlocker());
    }

    public function test_sessions_respect_the_minimum_interval_for_customers_but_staff_may_override(): void
    {
        $course = $this->paidCourse();
        $first = $this->book($course, 1);
        $this->assertSame(1, $first->course_session_number);
        $this->assertSame('paid', $first->payment_status);
        $this->assertSame(0.0, $first->remaining_amount);

        try {
            $this->book($course, 10);
            $this->fail('a customer booked inside the minimum interval');
        } catch (CourseException $e) {
            $this->assertStringContainsString('زودتر از', $e->getMessage());
        }

        $second = $this->book($course, 10, byStaff: true);
        $this->assertSame(2, $second->course_session_number);
        $this->assertSame(4, $course->fresh()->remainingSessions());
    }

    public function test_sessions_cannot_go_past_the_expiry_or_to_another_specialist_when_one_is_fixed(): void
    {
        $course = $this->paidCourse(['validity_days' => 20]);
        $this->expectException(CourseException::class);
        $this->book($course, 25, byStaff: true);
    }

    public function test_completing_a_session_counts_it_sets_the_next_due_date_and_credits_the_specialist_once(): void
    {
        $course = $this->paidCourse([], 'gateway');
        $booking = $this->book($course, 1);

        $booking->update(['status' => 'completed']);
        $booking->update(['status' => 'completed']); // بی‌اثر

        $course->refresh();
        $this->assertSame(1, $course->sessions_used);
        $this->assertEqualsWithDelta($booking->booking_time->copy()->addDays(35)->timestamp, $course->next_due_at->timestamp, 1);

        $wallet = SpecialistWallet::where('specialist_id', $this->courseSpecialist->id)->first();
        $rate = $this->courseSpecialist->getEffectiveCommissionRate();
        $this->assertEqualsWithDelta(800000 * (1 - $rate / 100), (float) $wallet->total_earned, 1);
        $this->assertNotNull($booking->fresh()->course_income_at);
    }

    public function test_in_person_courses_give_no_wallet_income_like_in_person_bookings(): void
    {
        $course = $this->paidCourse();
        $this->book($course, 1)->update(['status' => 'completed']);

        $this->assertSame(0.0, (float) (SpecialistWallet::where('specialist_id', $this->courseSpecialist->id)->value('total_earned') ?? 0));
        $this->assertSame(1, $course->fresh()->sessions_used);
    }

    public function test_the_last_session_completes_the_course(): void
    {
        $course = $this->paidCourse(['sessions_count' => 2, 'price' => 1000000, 'min_interval_days' => 1, 'interval_days' => 2]);
        $this->book($course, 1)->update(['status' => 'completed']);
        $this->book($course, 3)->update(['status' => 'completed']);

        $course->refresh();
        $this->assertSame('completed', $course->status);
        $this->assertNull($course->next_due_at);
        $this->assertSame('همه‌ی جلسه‌های این دوره انجام شده است.', $course->bookingBlocker());
    }

    public function test_an_on_time_cancellation_returns_the_session_and_a_late_one_burns_it_only_when_the_salon_wants(): void
    {
        $this->travelTo(now()->setTime(8, 0)); // نوبت‌های «چند ساعت بعد» همان روز باید در ساعت کاری (۸ تا ۲۰) بیفتند
        WalletSetting::get()->update(['cancellation_before_hours' => 24]);
        $course = $this->paidCourse();

        $early = $this->book($course, 5);
        $early->update(['status' => 'cancelled', 'cancelled_by' => 'customer', 'cancelled_at' => now()]);
        $this->assertSame(0, $course->fresh()->sessions_used);
        $this->assertSame(6, $course->fresh()->remainingSessions());

        $late = $this->book($course, 0, false, now()->addHours(3)->format('H').':00');
        $late->update(['status' => 'cancelled', 'cancelled_by' => 'customer', 'cancelled_at' => now()]);
        $this->assertSame(0, $course->fresh()->sessions_used, 'setting off by default');

        CourseSetting::create(['salon_id' => $course->salon_id, 'burn_late_cancellation' => true]);
        $late2 = $this->book($course, 0, true, now()->addHours(4)->format('H').':00');
        $late2->update(['status' => 'cancelled', 'cancelled_by' => 'customer', 'cancelled_at' => now()]);
        $course->refresh();
        $this->assertSame(1, $course->sessions_used);
        $this->assertSame(1, $course->sessions_burned);
        $this->assertTrue($late2->fresh()->course_session_burned);

        // لغو دیرهنگام توسط سالن هرگز نمی‌سوزد
        $late3 = $this->book($course, 0, true, now()->addHours(5)->format('H').':00');
        $late3->update(['status' => 'cancelled', 'cancelled_by' => 'admin', 'cancelled_at' => now()]);
        $this->assertSame(1, $course->fresh()->sessions_used);
    }

    public function test_cancelling_a_course_refunds_the_unused_value_to_the_wallet_and_cancels_future_sessions(): void
    {
        $course = $this->paidCourse([], 'gateway');
        $this->book($course, 1)->update(['status' => 'completed']);
        $future = $this->book($course, 40, true);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->assertSame(4000000.0, $this->service()->refundableAmount($course->fresh()));

        try {
            $this->service()->cancel($course->fresh(), 4500000, 'درخواست مشتری', $admin);
            $this->fail('refund above the suggestion was accepted');
        } catch (CourseException) {
        }

        $this->service()->cancel($course->fresh(), 3500000, 'درخواست مشتری', $admin);

        $course->refresh();
        $this->assertSame('cancelled', $course->status);
        $this->assertSame(3500000.0, $course->refunded_amount);
        $this->assertSame('cancelled', $future->fresh()->status);
        $this->assertSame(3500000.0, (float) UserWallet::where('user_id', $this->courseCustomer->id)->value('balance'));
        $this->assertSame(0.0, $this->service()->refundableAmount($course));
    }

    public function test_expiry_keeps_sessions_booked_before_it(): void
    {
        $course = $this->paidCourse(['validity_days' => 10]);
        $booking = $this->book($course, 9);

        $this->travel(11)->days();
        $this->assertSame(1, $this->service()->expireDue());
        $this->assertSame('expired', $course->fresh()->status);

        $booking->update(['status' => 'completed']);
        $this->assertSame(1, $course->fresh()->sessions_used);

        $admin = User::factory()->create(['is_admin' => true]);
        $this->service()->extend($course->fresh(), now()->addDays(30), $admin);
        $this->assertSame('active', $course->fresh()->status);
    }

    public function test_discount_codes_apply_only_when_marked_valid_for_courses_and_are_released_on_abandon(): void
    {
        $code = DiscountCode::factory()->create(['code' => 'LASER20', 'type' => 'percentage', 'amount' => 20, 'max_amount' => null, 'max_uses' => 5, 'used_count' => 0, 'is_active' => true, 'expires_at' => now()->addMonth(), 'user_id' => null]);

        try {
            $this->service()->purchase($this->courseCustomer, $this->coursePackage(), 'LASER20');
            $this->fail('a booking-only code was accepted on a course');
        } catch (CourseException) {
        }

        $code->update(['valid_for_courses' => true]);
        $course = $this->service()->purchase($this->courseCustomer, $this->coursePackage(), 'LASER20');
        $this->assertSame(3840000.0, $course->price_total);
        $this->assertSame(640000.0, $course->per_session_value);
        $this->assertSame(1, $code->fresh()->used_count);

        $this->travel(31)->minutes();
        $this->assertSame(1, $this->service()->abandonUnpaid());
        $this->assertSame('cancelled', $course->fresh()->status);
        $this->assertSame(0, $code->fresh()->used_count);
    }

    public function test_a_customer_of_another_salon_cannot_buy_this_salons_package(): void
    {
        $other = Salon::factory()->create();
        $stranger = User::factory()->create(['user_type' => 'customer', 'salon_id' => $other->id]);

        $this->expectException(CourseException::class);
        $this->service()->purchase($stranger, $this->coursePackage());
    }

    public function test_courses_of_another_salon_are_invisible(): void
    {
        $course = $this->paidCourse();
        $other = Salon::factory()->create();

        app(CurrentSalon::class)->withSalon($other, function () use ($course) {
            $this->assertNull(CustomerCourse::find($course->id));
        });
    }
}
