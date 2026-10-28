<?php

namespace Tests\Feature\Courses;

use App\Models\User;
use App\Services\Courses\CourseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * 🐞 صفحه‌ی دوره در پنل مدیر برای هر ردیف «تاریخچه» کاربر انجام‌دهنده را جدا می‌خواند (N+1): با دو ردیف یا بیشتر تست‌ها خطا
 * می‌دادند و سیستم توسعه هشدار N+1 می‌نوشت.
 */
class AdminCourseActivityListTest extends TestCase
{
    use CreatesCourses;
    use RefreshDatabase;

    public function test_the_activity_list_with_several_entries_loads_its_causers_at_once(): void
    {
        Notification::fake();
        $this->setUpCourseWorld();
        $course = $this->paidCourse();
        $admin = User::factory()->create(['is_admin' => true]);
        app(CourseService::class)->extend($course, now()->addYear(), $admin);
        app(CourseService::class)->extend($course->fresh(), now()->addYears(2), $admin);

        $this->actingAs($admin)->get(route('admin.courses.show', $course))->assertOk()->assertSee('تمدید مهلت');
    }
}
