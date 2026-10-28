<?php

namespace Tests\Feature\Courses;

use App\Models\CoursePhotoConsent;
use App\Models\CustomerCourse;
use App\Models\Salon;
use App\Support\Courses\PhotoConsent;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * جدول سابقه‌ی رضایت عکس (۲۰۲۶-۱۰-۲۸): مختص سالن، «درخواست باز» فقط تا مهلت، برچسب منبع («ثبت حضوری») در سابقه.
 */
class CoursePhotoConsentModelTest extends TestCase
{
    use CreatesCourses;
    use RefreshDatabase;

    private function row(CustomerCourse $course, array $attributes): CoursePhotoConsent
    {
        return CoursePhotoConsent::create($attributes + [
            'customer_course_id' => $course->id, 'user_id' => $course->user_id, 'text_version' => PhotoConsent::VERSION,
        ]);
    }

    public function test_open_requests_labels_and_salon_scope(): void
    {
        $this->setUpCourseWorld();
        $course = $this->paidCourse();

        $open = $this->row($course, ['status' => 'pending', 'requested_at' => now(), 'expires_at' => now()->addDays(7)]);
        $expired = $this->row($course, ['status' => 'pending', 'requested_at' => now()->subDays(8), 'expires_at' => now()->subDay()]);
        $inPerson = $this->row($course, ['status' => 'granted', 'source' => 'in_person', 'decided_at' => now()]);

        $this->assertSame([$open->id], CoursePhotoConsent::open()->pluck('id')->all());
        $this->assertSame('در انتظار پاسخ مشتری', $open->label());
        $this->assertSame('درخواست بی پاسخ ماند (منقضی)', $expired->label());
        $this->assertSame('موافقت — ثبت حضوری', $inPerson->label());
        $this->assertSame($inPerson->id, $course->photoConsents()->first()->id, 'تازه‌ترین اول');
        $this->assertSame($course->salon_id, $open->salon_id);

        app(CurrentSalon::class)->set(Salon::factory()->create(['slug' => 'consent-other']));
        $this->assertSame(0, CoursePhotoConsent::count(), 'سالن دیگر نمی‌بیند');
    }
}
