<?php

namespace Tests\Feature\Courses;

use App\Models\User;
use App\Services\Courses\CourseService;
use App\Services\Courses\PhotoConsentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * سابقه‌ی رضایت عکس در پنل مدیر، پنل متخصص و «دوره‌های من» (۲۰۲۶-۱۰-۲۸): ثبت کادر با برچسب «ثبت حضوری» و نام کارمند (فقط پنل‌های
 * کادر)، تصمیم مشتری با منبعش.
 */
class PhotoConsentHistoryViewTest extends TestCase
{
    use CreatesCourses;
    use RefreshDatabase;

    public function test_history_is_labelled_in_all_three_panels(): void
    {
        Notification::fake();
        $this->setUpCourseWorld();
        $course = $this->paidCourse();
        $staff = User::find($this->courseSpecialist->user_id);
        $consents = app(PhotoConsentService::class);

        $consents->request($course, $staff);
        $consents->decline($course, $this->courseCustomer, 'app');
        app(CourseService::class)->recordConsent($course, $staff);
        $booking = app(CourseService::class)->bookSession($course->fresh(), $this->courseSpecialist->id, $this->sessionAt(1));
        $staff->refresh();

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get(route('admin.courses.show', $course))->assertOk()
            ->assertSee('data-photo-consent-history', false)
            ->assertSee('موافقت — ثبت حضوری ('.$staff->name.')')->assertSee('مخالفت — اپ مشتری');

        $this->actingAs($staff)->get(route('specialist.bookings.show', $booking))->assertOk()
            ->assertSee('موافقت — ثبت حضوری')->assertSee('مخالفت — اپ مشتری');

        $this->actingAs($this->courseCustomer)->get(route('my-courses.show', $course))->assertOk()
            ->assertSee('موافقت — ثبت حضوری')->assertDontSee('('.$staff->name.')');
    }
}
