<?php

namespace Tests\Feature\Courses;

use App\Models\CourseSessionPhoto;
use App\Models\User;
use App\Services\Courses\CourseService;
use App\Services\Courses\CourseSessionRecords;
use App\Support\Courses\PhotoConsent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * رضایت عکس قبل/بعد دوره (بسته‌ی مجوز 5د؛ مواد ۵۸ و ۵۹ قانون تجارت الکترونیکی). 🐞 کفایت: پیش‌تر کادر فقط «مشتری رضایت داد» می‌زد؛
 * به مشتری هیچ متنی (هدف، چه کسی می‌بیند، پس گرفتن) گفته یا نشان داده نمی‌شد و راهی برای پس گرفتن رضایت نبود.
 */
class PhotoConsentTextTest extends TestCase
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

    public function test_staff_see_the_text_to_read_and_the_recorded_consent_carries_its_version(): void
    {
        $course = $this->paidCourse();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.courses.show', $course))->assertOk()
            ->assertSee('data-photo-consent-text', false)->assertSee('فقط شما و کادر همین سالن');

        $this->actingAs($admin)->post(route('admin.courses.consent', $course))->assertSessionHas('success');

        $entry = \Spatie\Activitylog\Models\Activity::query()->where('description', 'course_photo_consent')->latest('id')->first();
        $this->assertSame(PhotoConsent::VERSION, $entry->properties['version']);
    }

    public function test_the_customer_sees_the_text_and_can_withdraw_on_the_web_and_new_photos_stop(): void
    {
        $course = $this->paidCourse();
        app(CourseService::class)->recordConsent($course, User::factory()->create(['is_admin' => true]));

        $this->actingAs($this->courseCustomer)->get(route('my-courses.show', $course))->assertOk()
            ->assertSee('data-photo-consent', false)->assertSee(PhotoConsent::TEXT)->assertSee('پس گرفتن رضایت');

        $this->actingAs($this->courseCustomer)->post(route('my-courses.consent.withdraw', $course))->assertSessionHas('success');
        $this->assertFalse($course->fresh()->hasPhotoConsent());

        $booking = app(CourseService::class)->bookSession($course->fresh(), $this->courseSpecialist->id, $this->sessionAt(1));
        $this->expectException(\App\Services\Courses\CourseException::class);
        app(CourseSessionRecords::class)->addPhoto($booking, UploadedFile::fake()->image('a.jpg'), 'before', User::find($this->courseSpecialist->user_id));
    }

    public function test_the_app_shows_the_text_and_can_withdraw(): void
    {
        $course = $this->paidCourse();
        app(CourseService::class)->recordConsent($course, User::factory()->create(['is_admin' => true]));
        $token = $this->courseCustomer->createToken('a', ['customer'], now()->addDay())->plainTextToken;

        $this->withToken($token)->getJson("/api/v1/customer/courses/{$course->id}")->assertJsonPath('data.photo_consent_text', PhotoConsent::TEXT);
        $this->withToken($token)->postJson("/api/v1/customer/courses/{$course->id}/photo-consent/withdraw")->assertOk()->assertJsonPath('data.photo_consent', false);
        $this->assertFalse($course->fresh()->hasPhotoConsent());
    }

    public function test_another_customer_cannot_withdraw(): void
    {
        $course = $this->paidCourse();
        app(CourseService::class)->recordConsent($course, User::factory()->create(['is_admin' => true]));

        $this->actingAs(User::factory()->create(['user_type' => 'customer', 'salon_id' => $course->salon_id]))
            ->post(route('my-courses.consent.withdraw', $course))->assertNotFound();
        $this->assertTrue($course->fresh()->hasPhotoConsent());
    }
}
