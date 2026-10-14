<?php

namespace Tests\Feature\Courses;

use App\Models\Booking;
use App\Models\CoursePackage;
use App\Models\CourseSessionNote;
use App\Models\CourseSessionPhoto;
use App\Models\CourseSetting;
use App\Models\CustomerCourse;
use App\Models\Specialist;
use App\Models\User;
use App\Services\Courses\CourseService;
use App\Support\CurrentSalon;
use App\Support\Features\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/** دوره‌های درمانی در وب مشتری، پنل مدیر و پنل متخصص (بسته‌ی ب) */
class CoursePanelsTest extends TestCase
{
    use CreatesCourses;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake(CourseSessionPhoto::DISK);
        $this->setUpCourseWorld();
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function specialistUser(?Specialist $specialist = null): User
    {
        return User::find(($specialist ?? $this->courseSpecialist)->user_id);
    }

    private function bookedSession(?CustomerCourse $course = null): Booking
    {
        return app(CourseService::class)->bookSession($course ?? $this->paidCourse(), $this->courseSpecialist->id, $this->sessionAt(1));
    }

    // ─── وب مشتری ─────────────────────────────────────────────────

    public function test_customer_pages_buy_book_and_show_the_course(): void
    {
        $package = $this->coursePackage();
        $this->get(route('courses.index'))->assertOk()->assertSee($package->name);
        $this->get(route('courses.show', $package))->assertOk()->assertSee('۶');

        $this->actingAs($this->courseCustomer)->post(route('courses.purchase', $package))->assertRedirect();
        $course = CustomerCourse::sole();
        $this->get(route('my-courses.pay', $course))->assertOk();
        app(CourseService::class)->recordPayment($course, $course->price_total, 'in_person');

        $this->get(route('my-courses.index'))->assertOk()->assertSee($course->name);
        $this->get(route('my-courses.book', ['course' => $course, 'specialist' => $this->courseSpecialist->id, 'date' => now()->addDay()->format('Y-m-d')]))
            ->assertOk()->assertSee('10:00');
        $this->post(route('my-courses.book.store', $course), [
            'specialist_id' => $this->courseSpecialist->id, 'date' => now()->addDay()->format('Y-m-d'), 'time' => '10:00',
        ])->assertRedirect(route('my-courses.show', $course))->assertSessionHas('success');

        $this->get(route('my-courses.show', $course))->assertOk()->assertSee('جلسه‌ی ۱ از ۶');
        $this->get(route('bookings.index'))->assertOk()->assertSee('جلسه‌ی ۱ از ۶');

        $other = User::factory()->create(['user_type' => 'customer', 'salon_id' => $course->salon_id]);
        $this->actingAs($other)->get(route('my-courses.show', $course))->assertNotFound();
    }

    public function test_public_course_pages_and_purchase_hide_without_the_plan_feature(): void
    {
        $course = $this->paidCourse();
        $this->courseSalonWithout(Feature::TreatmentCourses);

        $this->get(route('courses.index'))->assertNotFound();
        $this->actingAs($this->courseCustomer)->post(route('courses.purchase', $course->course_package_id))->assertNotFound();
        $this->get(route('my-courses.show', $course))->assertOk();
    }

    // ─── پنل مدیر ─────────────────────────────────────────────────

    public function test_admin_defines_a_package_sells_it_in_person_and_books_a_session(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.courses.packages.create'))->assertOk();
        $this->post(route('admin.courses.packages.store'), [
            'name' => 'لیزر ۶ جلسه', 'service_id' => $this->courseService->id, 'sessions_count' => '۶', 'price' => '4,800,000',
            'interval_days' => 35, 'min_interval_days' => 28, 'validity_days' => 365, 'min_prepayment_percent' => 30, 'is_active' => 1, 'is_public' => 1,
        ])->assertRedirect(route('admin.courses.packages.index'));
        $package = CoursePackage::sole();
        $this->assertSame(4800000.0, $package->price);

        $this->get(route('admin.courses.sell'))->assertOk();
        $this->post(route('admin.courses.sell.store'), [
            'package_id' => $package->id, 'customer_phone' => '09351112233', 'customer_name' => 'سارا', 'amount' => '1,440,000',
        ])->assertRedirect();
        $course = CustomerCourse::sole();
        $this->assertSame('active', $course->status);
        $this->assertSame(2, $course->paidSessions());
        $this->assertSame($admin->id, $course->sold_by);

        $this->get(route('admin.courses.index'))->assertOk()->assertSee('سارا');
        $this->get(route('admin.courses.show', $course))->assertOk();
        $this->post(route('admin.courses.sessions.store', $course), [
            'specialist_id' => $this->courseSpecialist->id, 'date' => verta(now()->addDay())->format('Y/m/d'), 'time' => '12:00',
        ])->assertSessionHas('success');
        $this->assertSame(1, $course->reservedCount());

        $this->put(route('admin.courses.settings.update'), ['burn_late_cancellation' => 1])->assertSessionHas('success');
        $this->assertTrue((bool) CourseSetting::forSalon(app(CurrentSalon::class)->id())->burn_late_cancellation);
    }

    public function test_after_a_downgrade_admin_cannot_sell_but_still_manages_sold_courses(): void
    {
        $course = $this->paidCourse();
        $this->courseSalonWithout(Feature::TreatmentCourses);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.courses.sell'))->assertRedirect(route('admin.billing.index'));
        $this->get(route('admin.courses.packages.index'))->assertRedirect(route('admin.billing.index'));
        $this->get(route('admin.courses.show', $course))->assertOk();
        $this->post(route('admin.courses.sessions.store', $course), [
            'specialist_id' => $this->courseSpecialist->id, 'date' => verta(now()->addDay())->format('Y/m/d'), 'time' => '12:00',
        ])->assertSessionHas('success');
    }

    public function test_admin_photos_need_consent_and_stay_on_the_private_disk(): void
    {
        $booking = $this->bookedSession();
        $course = $booking->customerCourse;
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.courses.sessions.photos.store', [$course, $booking]), ['photo' => UploadedFile::fake()->image('p.jpg'), 'kind' => 'before'])
            ->assertSessionHas('error');
        $this->post(route('admin.courses.consent', $course))->assertSessionHas('success');
        $this->post(route('admin.courses.sessions.photos.store', [$course, $booking]), ['photo' => UploadedFile::fake()->image('p.jpg'), 'kind' => 'before'])
            ->assertSessionHas('success');

        $photo = CourseSessionPhoto::sole();
        Storage::disk(CourseSessionPhoto::DISK)->assertExists($photo->path);
        $this->get(route('admin.courses.photos.show', [$course, $photo]))->assertOk();
        $this->assertFalse(Storage::disk('public')->exists($photo->path));
    }

    public function test_the_admin_customer_page_lists_the_customers_courses(): void
    {
        $course = $this->paidCourse();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.customers.show', $this->courseCustomer))->assertOk()
            ->assertSee('دوره‌های درمانی')
            ->assertSee($course->name)
            ->assertSee(route('admin.courses.show', $course), false)
            ->assertSee(route('admin.courses.sell', ['phone' => $this->courseCustomer->phone]), false);
        $this->get(route('admin.courses.sell', ['phone' => $this->courseCustomer->phone]))->assertOk()->assertSee($this->courseCustomer->phone);

        // تنزل پلن: دوره‌ها دیده می‌شوند، دکمه‌ی فروش نه
        $this->courseSalonWithout(Feature::TreatmentCourses);
        $this->get(route('admin.customers.show', $this->courseCustomer))->assertOk()->assertSee($course->name)
            ->assertDontSee('فروش دوره به این مشتری');
    }

    // ─── پنل متخصص ────────────────────────────────────────────────

    public function test_specialist_sees_the_course_on_the_booking_and_writes_the_session_note(): void
    {
        $booking = $this->bookedSession();

        $this->actingAs($this->specialistUser())->get(route('specialist.bookings.show', $booking))->assertOk()->assertSee('جلسه‌ی ۱ از ۶');
        $this->put(route('specialist.bookings.course-note', $booking), ['note' => 'شدت ۱۸ ژول', 'visible_to_customer' => 1])->assertSessionHas('success');
        $this->assertSame('شدت ۱۸ ژول', CourseSessionNote::sole()->note);

        $other = Specialist::factory()->create();
        $this->actingAs($this->specialistUser($other))->put(route('specialist.bookings.course-note', $booking), ['note' => 'x'])->assertNotFound();
    }
}
