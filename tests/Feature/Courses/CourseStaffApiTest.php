<?php

namespace Tests\Feature\Courses;

use App\Models\Booking;
use App\Models\CourseSessionPhoto;
use App\Models\CustomerCourse;
use App\Models\Specialist;
use App\Models\User;
use App\Services\Courses\CourseService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/** پرونده‌ی جلسه‌ی دوره در API اپ «ماهرو همکار» (بسته‌ی ب): یادداشت، رضایت، عکس، جداسازی متخصص‌ها، انجام جلسه */
class CourseStaffApiTest extends TestCase
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

    private function specialistUser(?Specialist $specialist = null): User
    {
        return User::find(($specialist ?? $this->courseSpecialist)->user_id);
    }

    private function staffApi(?Specialist $specialist = null): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($this->specialistUser($specialist)->createToken('A17', ['staff'], now()->addDay())->plainTextToken);
    }

    private function bookedSession(?CustomerCourse $course = null): Booking
    {
        return app(CourseService::class)->bookSession($course ?? $this->paidCourse(), $this->courseSpecialist->id, $this->sessionAt(1));
    }

    // ─── API همکار ────────────────────────────────────────────────

    public function test_staff_api_course_note_consent_photos_and_isolation(): void
    {
        $booking = $this->bookedSession();
        $base = "/api/v1/staff/bookings/{$booking->id}";

        $this->staffApi()->getJson($base)->assertOk()->assertJsonPath('data.course.label', 'جلسه‌ی ۱ از ۶')->assertJsonPath('data.course.photo_consent', false);
        $this->staffApi()->postJson("{$base}/session-note", ['note' => 'پوست حساس', 'visible_to_customer' => false])->assertOk()->assertJsonPath('data.note', 'پوست حساس');
        $this->staffApi()->postJson("{$base}/photos", ['photo' => UploadedFile::fake()->image('a.jpg'), 'kind' => 'after'])
            ->assertStatus(422)->assertJsonPath('error.code', 'course');
        $this->staffApi()->postJson("{$base}/course-consent")->assertOk();
        $photoUrl = $this->staffApi()->postJson("{$base}/photos", ['photo' => UploadedFile::fake()->image('a.jpg'), 'kind' => 'after'])
            ->assertCreated()->json('data.url');
        $this->staffApi()->get($photoUrl)->assertOk();
        $this->staffApi()->getJson("{$base}/course")->assertOk()
            ->assertJsonPath('data.sessions.0.note', 'پوست حساس')
            ->assertJsonPath('data.sessions.0.photos.0.kind', 'after');

        // یادداشت پنهان از مشتری در API مشتری دیده نمی‌شود
        $this->app['auth']->forgetGuards();
        $this->withToken($this->courseCustomer->createToken('c', ['customer'], now()->addDay())->plainTextToken)
            ->getJson("/api/v1/customer/courses/{$booking->customer_course_id}")->assertOk()->assertJsonPath('data.sessions.0.note', null);

        $other = Specialist::factory()->create();
        $this->staffApi($other)->getJson("{$base}/course")->assertNotFound();
        $this->staffApi($other)->get($photoUrl)->assertNotFound();

        $plain = Booking::factory()->create(['specialist_id' => $this->courseSpecialist->id, 'status' => 'confirmed', 'booking_time' => now()->addDays(4)]);
        $this->staffApi()->postJson("/api/v1/staff/bookings/{$plain->id}/session-note", ['note' => 'x'])->assertStatus(422)->assertJsonPath('error.code', 'not_course_session');
        $this->staffApi()->getJson("/api/v1/staff/bookings/{$plain->id}")->assertOk()->assertJsonPath('data.course', null);
    }

    public function test_completing_a_session_from_the_staff_app_counts_it(): void
    {
        $booking = $this->bookedSession();
        $this->travel(1)->days();
        $this->travel(1)->hours();

        $this->staffApi()->postJson("/api/v1/staff/bookings/{$booking->id}/complete")->assertOk();

        $course = $booking->customerCourse()->first();
        $this->assertSame(1, $course->sessions_used);
        $this->assertNotNull($course->next_due_at);
        $this->assertSame(app(CurrentSalon::class)->id(), $course->salon_id);
    }
}
