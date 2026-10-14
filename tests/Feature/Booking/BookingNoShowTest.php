<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use App\Services\Booking\BookingNoShowService;
use App\Services\Courses\CourseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/** «نیامد» (کارهای باز بسته‌ی ب، ادامه ۷): پیش‌پرداخت مال سالن، جلسه‌ی دوره می‌سوزد، فقط بعد از ساعت شروع */
class BookingNoShowTest extends TestCase
{
    use CreatesCourses;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->setUpCourseWorld();
    }

    private function paidBooking(int $hoursFromNow = 2): Booking
    {
        return Booking::factory()->create([
            'user_id' => $this->courseCustomer->id, 'specialist_id' => $this->courseSpecialist->id, 'service_id' => $this->courseService->id,
            'status' => 'confirmed', 'payment_status' => 'paid', 'prepayment_amount' => 300000, 'booking_time' => now()->addHours($hoursFromNow),
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_only_after_the_start_and_the_prepayment_stays_with_the_salon(): void
    {
        $booking = $this->paidBooking();
        $admin = $this->admin();
        $service = app(BookingNoShowService::class);

        $this->assertFalse($service->mark($booking, $admin), 'پیش از ساعت نوبت نه');

        $this->travel(3)->hours();
        $wallet = (float) ($this->courseCustomer->getOrCreateWallet()->fresh()->balance);
        $this->actingAs($admin)->post(route('admin.bookings.no-show', $booking))->assertSessionHas('success');

        $booking->refresh();
        $this->assertSame('no_show', $booking->status);
        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame($admin->id, (int) $booking->no_show_by);
        $this->assertSame($wallet, (float) $this->courseCustomer->getOrCreateWallet()->fresh()->balance, 'برگشت پول نیست');
        $this->assertSame(0, \App\Models\LoyaltyPoint::query()->where('user_id', $this->courseCustomer->id)->count());
        $this->assertFalse($service->mark($booking->fresh(), $admin), 'دوباره نه');
        $this->get(route('admin.bookings.show', $booking))->assertOk()->assertSee('نیامد');
    }

    public function test_a_course_session_is_burned(): void
    {
        $course = $this->paidCourse();
        $booking = app(CourseService::class)->bookSession($course, $this->courseSpecialist->id, $this->sessionAt(1));
        $this->travel(2)->days();

        $this->assertTrue(app(BookingNoShowService::class)->mark($booking, $this->admin()));

        $course->refresh();
        $this->assertSame(1, $course->sessions_used);
        $this->assertSame(1, $course->sessions_burned);
        $this->assertTrue((bool) $booking->fresh()->course_session_burned);
        $this->assertSame(5, $course->remainingSessions());
    }

    public function test_specialist_web_and_staff_app(): void
    {
        $booking = $this->paidBooking();
        $other = $this->paidBooking(4);
        $this->travel(5)->hours();
        $user = User::find($this->courseSpecialist->user_id);

        $stranger = Specialist::factory()->create();
        $this->actingAs(User::find($stranger->user_id))->put(route('specialist.bookings.no-show', $booking))->assertNotFound();

        $this->actingAs($user)->get(route('specialist.bookings.show', $booking))->assertOk()->assertSee('نیامد');
        $this->put(route('specialist.bookings.no-show', $booking))->assertSessionHas('success');
        $this->assertSame('no_show', $booking->fresh()->status);

        $this->app['auth']->forgetGuards();
        $token = $user->createToken('A17', ['staff'], now()->addDay())->plainTextToken;
        $this->withToken($token)->getJson("/api/v1/staff/bookings/{$other->id}")->assertOk()->assertJsonPath('data.actions.no_show', true);
        $this->withToken($token)->postJson("/api/v1/staff/bookings/{$other->id}/no-show")->assertOk()
            ->assertJsonPath('data.status', 'no_show')->assertJsonPath('data.status_label', 'نیامد')->assertJsonPath('data.actions.no_show', false);
        $this->withToken($token)->postJson("/api/v1/staff/bookings/{$other->id}/no-show")->assertStatus(422);

        $this->app['auth']->forgetGuards();
        $this->withToken($this->courseCustomer->createToken('c', ['customer'], now()->addDay())->plainTextToken)
            ->getJson("/api/v1/customer/bookings/{$other->id}")->assertOk()
            ->assertJsonPath('data.status_label', 'نیامد')->assertJsonPath('data.actions.cancel', false)->assertJsonPath('data.actions.reschedule', false);
    }
}
