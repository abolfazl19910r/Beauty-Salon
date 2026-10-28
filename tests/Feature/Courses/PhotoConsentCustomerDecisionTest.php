<?php

namespace Tests\Feature\Courses;

use App\Models\CoursePhotoConsent;
use App\Models\User;
use App\Services\Courses\PhotoConsentService;
use App\Support\Courses\PhotoConsent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * تصمیم خود مشتری درباره‌ی رضایت عکس، در اپ و سایت سالن (۲۰۲۶-۱۰-۲۸). پیش‌تر مشتری فقط می‌توانست رضایتی را که کادر ثبت کرده پس
 * بگیرد؛ موافقت یا مخالفتش هیچ‌جا ثبت نمی‌شد.
 */
class PhotoConsentCustomerDecisionTest extends TestCase
{
    use CreatesCourses;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->setUpCourseWorld();
    }

    private function requested(): \App\Models\CustomerCourse
    {
        $course = $this->paidCourse();
        app(PhotoConsentService::class)->request($course, User::find($this->courseSpecialist->user_id));

        return $course;
    }

    public function test_the_app_shows_the_open_request_and_records_agreement(): void
    {
        $course = $this->requested();
        $token = $this->courseCustomer->createToken('a', ['customer'], now()->addDay())->plainTextToken;

        $this->withToken($token)->getJson("/api/v1/customer/courses/{$course->id}")->assertOk()
            ->assertJsonPath('data.photo_consent_state.status', 'pending')
            ->assertJsonPath('data.photo_consent_state.text', PhotoConsent::TEXT)
            ->assertJsonPath('data.photo_consent_state.text_version', PhotoConsent::VERSION);

        $this->withToken($token)->postJson("/api/v1/customer/courses/{$course->id}/photo-consent", ['decision' => 'maybe'])->assertStatus(422);
        $this->withToken($token)->withServerVariables(['REMOTE_ADDR' => '5.6.7.40'])
            ->postJson("/api/v1/customer/courses/{$course->id}/photo-consent", ['decision' => 'agree'])->assertOk()
            ->assertJsonPath('data.photo_consent', true)
            ->assertJsonPath('data.photo_consent_state.history.0.label', 'موافقت — اپ مشتری');

        $row = CoursePhotoConsent::sole();
        $this->assertSame(['granted', 'app', '5.6.7.40'], [$row->status, $row->source, $row->ip_address]);
    }

    public function test_the_app_declines_and_without_a_request_decline_is_refused(): void
    {
        $course = $this->requested();
        $token = $this->courseCustomer->createToken('a', ['customer'], now()->addDay())->plainTextToken;

        $this->withToken($token)->postJson("/api/v1/customer/courses/{$course->id}/photo-consent", ['decision' => 'decline'])->assertOk()
            ->assertJsonPath('data.photo_consent', false)->assertJsonPath('data.photo_consent_state.status', 'declined');
        $this->withToken($token)->postJson("/api/v1/customer/courses/{$course->id}/photo-consent", ['decision' => 'decline'])
            ->assertStatus(422)->assertJsonPath('error.code', 'course');
    }

    public function test_the_web_page_lets_the_customer_answer_the_request(): void
    {
        $course = $this->requested();
        $this->actingAs($this->courseCustomer);

        $this->get(route('my-courses.show', $course))->assertOk()->assertSee('data-photo-consent-pending', false)
            ->assertSee('data-photo-consent-form', false)->assertSee('موافق نیستم');

        $this->post(route('my-courses.photo-consent.decide', $course), ['decision' => 'agree'])->assertSessionHasErrors('read_text');
        $this->post(route('my-courses.photo-consent.decide', $course), ['decision' => 'agree', 'read_text' => '1'])->assertSessionHas('success');

        $this->assertSame('web', CoursePhotoConsent::sole()->source);
        $this->assertTrue($course->fresh()->hasPhotoConsent());
        $this->get(route('my-courses.show', $course))->assertSee('پس گرفتن رضایت')->assertDontSee('data-photo-consent-form', false);
    }

    public function test_the_customer_can_agree_on_the_web_without_any_request(): void
    {
        $course = $this->paidCourse();
        $this->actingAs($this->courseCustomer);

        $this->get(route('my-courses.show', $course))->assertOk()->assertSee('data-photo-consent-form', false)->assertDontSee('موافق نیستم');
        $this->post(route('my-courses.photo-consent.decide', $course), ['decision' => 'agree', 'read_text' => '1'])->assertSessionHas('success');
        $this->assertTrue($course->fresh()->hasPhotoConsent());
    }

    public function test_another_customer_cannot_decide(): void
    {
        $course = $this->requested();
        $other = User::factory()->create(['user_type' => 'customer', 'salon_id' => $course->salon_id]);

        $this->actingAs($other)->post(route('my-courses.photo-consent.decide', $course), ['decision' => 'agree', 'read_text' => '1'])->assertNotFound();
        $this->assertFalse($course->fresh()->hasPhotoConsent());
    }
}
