<?php

namespace Tests\Feature\Courses;

use App\Models\Booking;
use App\Models\CoursePhotoConsent;
use App\Models\PushMessage;
use App\Models\ShortLink;
use App\Models\User;
use App\Services\Courses\CourseService;
use App\Services\Courses\PhotoConsentService;
use App\Services\Push\PushMode;
use App\Support\Features\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesCourses;
use Tests\Concerns\FakesPush;
use Tests\TestCase;

/**
 * «درخواست رضایت» کادر (۲۰۲۶-۱۰-۲۸): از اپ همکار و پنل وب متخصص؛ پیام از مسیر استاندارد (پوش و پیامک لینک‌دار طبق حالت پلتفرم،
 * پیامک از سهمیه‌ی سالن)؛ لینک پیامک بی ورود، متن کامل و دو دکمه. ثبت حضوری راه دوم می‌ماند.
 */
class PhotoConsentRequestTest extends TestCase
{
    use CreatesCourses;
    use FakesPush;
    use RefreshDatabase;

    private Booking $booking;

    private string $staffToken;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->recordSms();
        $this->setUpCourseWorld();
        $course = $this->paidCourse();
        $this->booking = app(CourseService::class)->bookSession($course, $this->courseSpecialist->id, $this->sessionAt(1));
        $this->staffToken = User::find($this->courseSpecialist->user_id)->createToken('A17', ['staff'], now()->addDay())->plainTextToken;
    }

    private function staffApi(string $path)
    {
        return $this->withToken($this->staffToken)->postJson("/api/v1/staff/bookings/{$this->booking->id}/course-consent{$path}");
    }

    /** @return list<array> فقط پیامک‌های درخواست رضایت (رزرو جلسه پیامک خودش را دارد) */
    private function consentSms(): array
    {
        return array_values(array_filter($this->smsSent, fn ($s) => str_contains($s['message'], 'رضایت شما برای عکس')));
    }

    private function linkFromSms(): string
    {
        $all = $this->consentSms();
        preg_match('~/b/([a-z0-9]+)~', end($all)['message'], $m);

        return ShortLink::where('code', $m[1])->value('target_url');
    }

    public function test_staff_app_request_sends_an_sms_link_from_the_salon_quota_when_the_customer_has_no_app(): void
    {
        $this->staffApi('/request')->assertOk()
            ->assertJsonPath('data.photo_consent_state.status', 'pending')
            ->assertJsonPath('data.photo_consent_state.can_request', false)
            ->assertJsonPath('data.photo_consent_state.can_resend', false);

        $this->assertCount(1, $this->consentSms());
        $sms = $this->consentSms()[0];
        $this->assertSame($this->courseCustomer->phone, $sms['mobile']);
        $this->assertSame($this->booking->salon_id, $sms['salon_id'], 'از سهمیه‌ی همان سالن');
        $this->assertSame(Feature::SmsCustomer, $sms['feature']);
        $this->assertStringContainsString('رضایت شما برای عکس قبل/بعد', $sms['message']);
        $this->assertStringContainsString('/photo-consent/', $this->linkFromSms());

        $this->staffApi('/request')->assertStatus(422)->assertJsonPath('error.code', 'course');
        $this->staffApi('/resend')->assertStatus(422);
        $this->assertCount(1, $this->consentSms(), 'بی پیامک تکراری');

        $this->travel(25)->hours();
        $this->staffApi('/resend')->assertOk()->assertJsonPath('data.photo_consent_state.open_request.send_count', 2);
        $this->assertCount(2, $this->consentSms());
    }

    public function test_with_the_app_the_standard_path_sends_push_and_sms_link_in_shadow_mode(): void
    {
        $this->fakeFcm();
        PushMode::set(PushMode::SHADOW);
        $this->deviceFor($this->courseCustomer);

        $this->staffApi('/request')->assertOk();

        $message = PushMessage::query()->latest('id')->first();
        $this->assertSame('course.photo_consent.customer', $message->event_key);
        $this->assertSame('normal', $message->urgency);
        $this->assertSame($this->booking->customer_course_id, (int) $message->data['course_id']);
        $this->assertCount(1, $this->consentSms(), 'حالت «آزمایشی»: پیامک لینک‌دار هم');
        $this->assertCount(1, $this->fcmSends());
    }

    public function test_nothing_is_recorded_when_the_message_cannot_be_delivered(): void
    {
        // سهمیه‌ی سالن تمام (یا پلن بی پیامک مشتری): SMSService::send ‏false
        $this->mock(\App\Services\SMSService::class, fn ($m) => $m->shouldReceive('send')->andReturn(false));

        $this->staffApi('/request')->assertStatus(422)->assertJsonPath('error.message', PhotoConsentService::NOT_SENT);
        $this->assertSame(0, CoursePhotoConsent::count());
    }

    public function test_the_sms_link_page_shows_the_full_text_and_records_the_customer_decision(): void
    {
        $this->staffApi('/request')->assertOk();
        $url = $this->linkFromSms();
        auth()->forgetGuards();

        $this->get($url)->assertOk()->assertSee(\App\Support\Courses\PhotoConsent::TEXT)
            ->assertSee('data-photo-consent-form', false)->assertSee('موافق نیستم');

        $this->post($url, ['decision' => 'agree'])->assertSessionHasErrors('read_text');
        $this->withServerVariables(['REMOTE_ADDR' => '5.6.7.30'])->from($url)
            ->post($url, ['decision' => 'agree', 'read_text' => '1'])->assertRedirect($url)->assertSessionHas('success');

        $row = CoursePhotoConsent::sole();
        $this->assertSame('granted', $row->status);
        $this->assertSame('sms_link', $row->source);
        $this->assertSame('5.6.7.30', $row->ip_address);
        $this->assertTrue($this->booking->customerCourse->fresh()->hasPhotoConsent());

        $this->get($url)->assertOk()->assertSee('data-photo-consent-result', false)->assertSee('موافقت — لینک پیامک')
            ->assertDontSee('data-photo-consent-form', false);
        $this->post($url, ['decision' => 'decline'])->assertSessionHas('error');
        $this->get('/s/rasta/photo-consent/'.str_repeat('a', 40))->assertNotFound();
    }

    public function test_the_link_only_works_in_its_own_salon(): void
    {
        $this->staffApi('/request')->assertOk();
        $path = parse_url($this->linkFromSms(), PHP_URL_PATH);
        $other = \App\Models\Salon::factory()->create(['slug' => 'consent-elsewhere']);
        \App\Models\Salon::query()->whereKey($other->id)->update(['verification_status' => 'approved']);

        $this->get('/s/consent-elsewhere')->assertOk();
        $this->get(str_replace('/s/rasta/', '/s/consent-elsewhere/', $path))->assertNotFound();
    }

    public function test_after_a_decline_the_staff_app_waits_24_hours_and_in_person_stays_open(): void
    {
        $this->staffApi('/request')->assertOk();
        app(PhotoConsentService::class)->decline($this->booking->customerCourse, $this->courseCustomer, 'app');

        $this->withToken($this->staffToken)->getJson("/api/v1/staff/bookings/{$this->booking->id}/course")->assertOk()
            ->assertJsonPath('data.photo_consent_state.status', 'declined')
            ->assertJsonPath('data.photo_consent_state.can_request', false)
            ->assertJsonPath('data.photo_consent_state.history.0.label', 'مخالفت — اپ مشتری');
        $this->staffApi('/request')->assertStatus(422);

        $this->staffApi('')->assertOk()->assertJsonPath('data.photo_consent_state.status', 'granted')
            ->assertJsonPath('data.photo_consent_state.history.0.label', 'موافقت — ثبت حضوری');
    }

    public function test_specialist_web_panel_has_the_request_and_the_in_person_buttons(): void
    {
        $user = User::find($this->courseSpecialist->user_id);
        $this->actingAs($user)->get(route('specialist.bookings.show', $this->booking))->assertOk()
            ->assertSee('درخواست رضایت از مشتری')->assertSee('ثبت حضوری رضایت مشتری');

        $this->actingAs($user)->post(route('specialist.bookings.course-consent.request', $this->booking))->assertSessionHas('success');
        $this->assertSame(1, CoursePhotoConsent::where('status', 'pending')->count());
        $this->actingAs($user)->get(route('specialist.bookings.show', $this->booking))->assertSee('در انتظار پاسخ مشتری');
        $this->actingAs($user)->post(route('specialist.bookings.course-consent.request', $this->booking))->assertSessionHas('error');
    }
}
