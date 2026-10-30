<?php

namespace Tests\Feature\Courses;

use App\Models\Booking;
use App\Models\CourseSessionPhoto;
use App\Models\CustomerCourse;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Courses\CourseService;
use App\Support\CurrentSalon;
use App\Support\Features\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/** دوره‌های درمانی در API اپ «ماهرو» (بسته‌ی ب): بسته‌ها، خرید، پرداخت (کیف پول و درگاه با مرورگر بیرونی)، رزرو جلسه، عکس‌ها */
class CourseCustomerApiTest extends TestCase
{
    use CreatesCourses;
    use RefreshDatabase;
    use \Tests\Concerns\EnablesAllGateways;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Sleep::fake();
        Notification::fake();
        $this->setUpCourseWorld();
        $this->courseCustomer->update(['phone_verified_at' => now()]);
        $this->token = $this->courseCustomer->createToken('RN8', ['customer'], now()->addDays(90))->plainTextToken;
    }

    private function api(?string $token = null): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token ?? $this->token);
    }

    private function gateway(): void
    {
        $salon = app(CurrentSalon::class)->get();
        $salon->paymentGateways()->delete();
        $salon->paymentGateways()->create(['driver' => 'zibal', 'credentials' => ['merchant' => 'zibal'], 'priority' => 1]);
    }

    public function test_packages_purchase_and_wallet_payment_activate_a_course(): void
    {
        $package = $this->coursePackage(['min_prepayment_percent' => 25]);
        $this->api()->getJson('/api/v1/customer/home')->assertOk()->assertJsonFragment(['features' => app(\App\Services\Plans\FeatureResolver::class)->enabledKeys(app(CurrentSalon::class)->get())]);

        $this->api()->getJson('/api/v1/customer/courses/packages')->assertOk()
            ->assertJsonPath('data.0.id', $package->id)
            ->assertJsonPath('data.0.sessions_count', 6)
            ->assertJsonPath('data.0.min_prepayment', 1200000);

        $courseId = $this->api()->postJson("/api/v1/customer/courses/packages/{$package->id}/purchase")
            ->assertCreated()->assertJsonPath('data.status', 'pending_payment')->assertJsonPath('data.actions.book', false)->json('data.id');

        $this->api()->getJson("/api/v1/customer/courses/{$courseId}/pay")->assertOk()->assertJsonPath('data.min_amount', 1200000);
        $this->api()->postJson("/api/v1/customer/courses/{$courseId}/pay", ['amount' => 1000, 'use_wallet' => true])
            ->assertStatus(422)->assertJsonPath('error.code', 'payment_unavailable');

        $this->courseCustomer->getOrCreateWallet()->addRefund(1200000, null, 'شارژ تست');
        $this->api()->postJson("/api/v1/customer/courses/{$courseId}/pay", ['amount' => 1200000, 'use_wallet' => true])
            ->assertOk()->assertJsonPath('data.state', 'paid')
            ->assertJsonPath('data.course.status', 'active')
            ->assertJsonPath('data.course.paid_sessions', 2)
            ->assertJsonPath('data.course.balance_due', 3600000);
    }

    public function test_booking_the_next_session_and_seeing_it_on_the_booking(): void
    {
        $course = $this->paidCourse();

        $this->api()->getJson("/api/v1/customer/courses/{$course->id}/specialists")->assertOk()->assertJsonPath('data.0.id', $this->courseSpecialist->id);
        $date = $this->api()->getJson("/api/v1/customer/courses/{$course->id}/dates?specialist_id={$this->courseSpecialist->id}")
            ->assertOk()->json('data.dates.1');
        $slots = $this->api()->getJson("/api/v1/customer/courses/{$course->id}/slots?specialist_id={$this->courseSpecialist->id}&date={$date}")
            ->assertOk()->json('data.slots');
        $this->assertContains('10:00', $slots);

        $bookingId = $this->api()->postJson("/api/v1/customer/courses/{$course->id}/bookings", [
            'specialist_id' => $this->courseSpecialist->id, 'date' => $date, 'time' => '10:00',
        ])->assertCreated()
            ->assertJsonPath('data.booking.course.label', 'جلسه‌ی ۱ از ۶')
            ->assertJsonPath('data.booking.payment_status', 'paid')
            ->assertJsonPath('data.booking.remaining_amount', 0)
            ->assertJsonPath('data.course.sessions_reserved', 1)
            ->json('data.booking.id');

        $this->api()->getJson("/api/v1/customer/bookings/{$bookingId}")->assertOk()->assertJsonPath('data.course.id', $course->id);
        $this->api()->getJson("/api/v1/customer/courses/{$course->id}")->assertOk()
            ->assertJsonPath('data.sessions.0.booking_id', $bookingId)
            ->assertJsonPath('data.sessions.0.session_number', 1);

        // فاصله‌ی حداقل: جلسه‌ی دوم فردای اولی نه
        $this->api()->postJson("/api/v1/customer/courses/{$course->id}/bookings", [
            'specialist_id' => $this->courseSpecialist->id, 'date' => now()->addDays(2)->format('Y-m-d'), 'time' => '11:00',
        ])->assertStatus(422)->assertJsonPath('error.code', 'course');
    }

    public function test_gateway_payment_through_the_external_browser_records_once(): void
    {
        $this->gateway();
        Http::fake([
            'gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 51]),
            'gateway.zibal.ir/v1/verify' => Http::response(['result' => 100, 'amount' => 48000000, 'refNumber' => 778]),
        ]);
        $course = app(CourseService::class)->purchase($this->courseCustomer, $this->coursePackage());

        $pay = $this->api()->postJson("/api/v1/customer/courses/{$course->id}/pay", ['amount' => 4800000])
            ->assertOk()->assertJsonPath('data.state', 'redirect');
        $tx = PaymentTransaction::sole();
        $this->assertSame('course', $tx->purpose);
        $this->assertSame($tx->public_id, $pay->json('data.transaction'));
        $this->assertStringContainsString('/app/course/callback', $tx->callback_url);
        $this->api()->getJson("/api/v1/customer/payments/{$tx->public_id}")->assertOk()->assertJsonPath('data.state', 'pending');

        $this->app['auth']->forgetGuards();
        for ($i = 0; $i < 2; $i++) {
            $location = $this->withoutToken()->get("/payments/return/{$tx->public_id}?trackId=51&success=1&status=2")->assertStatus(303)->headers->get('Location');
            $this->get($location)->assertOk()->assertSee('purpose=course', false);
        }

        $this->assertSame(1, $course->payments()->count(), 'برگشت تکراری از بانک دو بار ثبت نمی‌شود');
        $this->assertSame('active', $course->fresh()->status);
        $this->api()->getJson("/api/v1/customer/payments/{$tx->public_id}")->assertOk()
            ->assertJsonPath('data.state', 'paid')
            ->assertJsonPath('data.purpose', 'course')
            ->assertJsonPath('data.course.status', 'active');
    }

    public function test_after_the_plan_drops_courses_packages_hide_but_bought_courses_keep_working(): void
    {
        $course = $this->paidCourse();
        $package = $course->package;
        $this->courseSalonWithout(Feature::TreatmentCourses);

        $this->api()->getJson('/api/v1/customer/courses/packages')->assertNotFound();
        $this->api()->postJson("/api/v1/customer/courses/packages/{$package->id}/purchase")->assertNotFound();
        $this->api()->getJson('/api/v1/customer/courses')->assertOk()->assertJsonPath('data.0.id', $course->id);
        $this->api()->postJson("/api/v1/customer/courses/{$course->id}/bookings", [
            'specialist_id' => $this->courseSpecialist->id, 'date' => now()->addDay()->format('Y-m-d'), 'time' => '09:00',
        ])->assertCreated();
    }

    public function test_photos_belong_only_to_the_courses_customer_who_may_delete_them(): void
    {
        Storage::fake(CourseSessionPhoto::DISK);
        $course = $this->paidCourse();
        $booking = app(CourseService::class)->bookSession($course, $this->courseSpecialist->id, $this->sessionAt(1));
        app(CourseService::class)->recordConsent($course, $this->courseCustomer);
        $photo = app(\App\Services\Courses\CourseSessionRecords::class)->addPhoto($booking, UploadedFile::fake()->image('a.jpg'), 'before', $this->courseCustomer);

        $this->api()->getJson("/api/v1/customer/courses/{$course->id}")->assertOk()
            ->assertJsonPath('data.sessions.0.photos.0.url', route('api.v1.customer.courses.photos.show', ['photoId' => $photo->id]));
        $this->api()->get("/api/v1/customer/course-photos/{$photo->id}")->assertOk()->assertHeader('Cache-Control', 'no-store, private');

        $other = User::factory()->create(['user_type' => 'customer', 'salon_id' => $course->salon_id, 'phone' => '09120000999']);
        $otherToken = $other->createToken('x', ['customer'], now()->addDay())->plainTextToken;
        $this->api($otherToken)->get("/api/v1/customer/course-photos/{$photo->id}")->assertNotFound();
        $this->api($otherToken)->getJson("/api/v1/customer/courses/{$course->id}")->assertNotFound();
        $this->api($otherToken)->deleteJson("/api/v1/customer/course-photos/{$photo->id}")->assertNotFound();

        $this->api()->deleteJson("/api/v1/customer/course-photos/{$photo->id}")->assertOk();
        $this->assertNull(CourseSessionPhoto::find($photo->id));
        Storage::disk(CourseSessionPhoto::DISK)->assertMissing($photo->path);
    }

    public function test_a_course_of_another_salon_is_invisible(): void
    {
        $other = \App\Models\Salon::factory()->create(['slug' => 'other-'.uniqid()]);
        $foreign = app(CurrentSalon::class)->withSalon($other, function () use ($other) {
            $service = \App\Models\BeautyService::factory()->create(['duration' => 30, 'price' => 100000]);
            $user = User::factory()->create(['user_type' => 'customer', 'salon_id' => $other->id, 'phone' => '09129998877']);
            $package = \App\Models\CoursePackage::create(['service_id' => $service->id, 'name' => 'x', 'sessions_count' => 3, 'price' => 300000, 'interval_days' => 30, 'min_interval_days' => 20, 'validity_days' => 200, 'is_active' => true, 'is_public' => true]);

            return app(CourseService::class)->purchase($user, $package);
        });

        $this->api()->getJson("/api/v1/customer/courses/{$foreign->id}")->assertNotFound();
        $this->api()->getJson('/api/v1/customer/courses/packages')->assertOk()->assertJsonCount(0, 'data');
        $this->assertSame(0, CustomerCourse::query()->count());
        $this->assertSame(0, Booking::query()->whereNotNull('customer_course_id')->count());
    }
}
