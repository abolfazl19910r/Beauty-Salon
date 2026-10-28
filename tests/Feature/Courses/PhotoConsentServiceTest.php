<?php

namespace Tests\Feature\Courses;

use App\Models\CoursePhotoConsent;
use App\Models\User;
use App\Services\Courses\CourseException;
use App\Services\Courses\CourseService;
use App\Services\Courses\PhotoConsentService;
use App\Support\Courses\PhotoConsent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * رضایت عکس از زبان خود مشتری (۲۰۲۶-۱۰-۲۸): درخواست کادر، موافقت/مخالفت مشتری، ثبت حضوری با برچسب و سه قاعده‌ی درخواست.
 * پیش‌تر فقط کادر «مشتری رضایت داد» را می‌زد و از مخالفت، منبع، IP و درخواست هیچ سابقه‌ای نمی‌ماند.
 */
class PhotoConsentServiceTest extends TestCase
{
    use CreatesCourses;
    use RefreshDatabase;

    private PhotoConsentService $consents;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->setUpCourseWorld();
        $this->consents = app(PhotoConsentService::class);
        $this->staff = User::find($this->courseSpecialist->user_id);
    }

    private function fromIp(string $ip): Request
    {
        return Request::create('/', 'POST', server: ['REMOTE_ADDR' => $ip, 'HTTP_USER_AGENT' => 'Mahru/0.3.7']);
    }

    public function test_request_then_customer_grants_with_time_ip_version_and_source(): void
    {
        $course = $this->paidCourse();
        [$row, $token] = $this->consents->request($course, $this->staff);

        $this->assertSame(CoursePhotoConsent::PENDING, $row->status);
        $this->assertSame(hash('sha256', $token), $row->token_hash, 'فقط هش توکن ذخیره می‌شود');
        $this->assertTrue($row->expires_at->between(now()->addDays(7)->subMinute(), now()->addDays(7)->addMinute()));
        $this->assertFalse($course->fresh()->hasPhotoConsent(), 'درخواست رضایت نیست');

        $this->consents->grant($course, $this->courseCustomer, 'app', $this->fromIp('5.6.7.20'));

        $row->refresh();
        $this->assertSame(CoursePhotoConsent::GRANTED, $row->status);
        $this->assertSame('app', $row->source);
        $this->assertSame('5.6.7.20', $row->ip_address);
        $this->assertSame('Mahru/0.3.7', $row->user_agent);
        $this->assertSame(PhotoConsent::VERSION, $row->text_version);
        $this->assertNotNull($row->decided_at);
        $this->assertFalse($row->isOpen(), 'لینک بعد از پاسخ دیگر تصمیم نمی‌گیرد');
        $this->assertTrue($course->fresh()->hasPhotoConsent());
        $this->assertSame($this->courseCustomer->id, $course->fresh()->photo_consent_by);
    }

    public function test_decline_is_recorded_and_photos_stay_closed(): void
    {
        $course = $this->paidCourse();
        $this->consents->request($course, $this->staff);
        $this->consents->decline($course, $this->courseCustomer, 'web', $this->fromIp('5.6.7.21'));

        $row = $course->photoConsents()->first();
        $this->assertSame(CoursePhotoConsent::DECLINED, $row->status);
        $this->assertSame('مخالفت — سایت سالن', $row->label());
        $this->assertFalse($course->fresh()->hasPhotoConsent());

        $this->expectException(CourseException::class);
        $this->consents->decline($course, $this->courseCustomer, 'web');
    }

    public function test_rule_1_one_open_request_and_resend_once_a_day_with_a_new_link(): void
    {
        $course = $this->paidCourse();
        [$row, $first] = $this->consents->request($course, $this->staff);

        try {
            $this->consents->request($course, $this->staff);
            $this->fail('درخواست دوم پذیرفته شد');
        } catch (CourseException $e) {
            $this->assertStringContainsString('در انتظار پاسخ', $e->getMessage());
        }

        try {
            $this->consents->resend($course, $this->staff);
            $this->fail('ارسال دوباره همان روز پذیرفته شد');
        } catch (CourseException $e) {
            $this->assertStringContainsString('ارسال دوباره از', $e->getMessage());
        }

        $this->travel(25)->hours();
        [$same, $second] = $this->consents->resend($course, $this->staff);
        $this->assertSame($row->id, $same->id);
        $this->assertNotSame($first, $second);
        $this->assertNull($this->consents->findByToken($first), 'لینک قبلی باطل شد');
        $this->assertSame($row->id, $this->consents->findByToken($second)->id);
        $this->assertSame(2, $same->send_count);

        $this->travel(7)->days();
        $state = $this->consents->state($course);
        $this->assertNull($state['open_request'], 'بعد از ۷ روز درخواست باز نیست');
        $this->assertTrue($state['can_request']);
    }

    public function test_rule_2_after_a_decline_a_new_request_waits_24_hours_but_in_person_is_always_open(): void
    {
        $course = $this->paidCourse();
        $this->consents->request($course, $this->staff);
        $this->consents->decline($course, $this->courseCustomer, 'app');

        $state = $this->consents->state($course);
        $this->assertFalse($state['can_request']);
        $this->assertStringContainsString('مخالفت کرد', $state['request_blocked_reason']);
        $this->assertNotNull($state['next_request_at']);

        $this->travel(23)->hours();
        $this->assertFalse($this->consents->state($course)['can_request']);
        $this->travel(2)->hours();
        $this->assertTrue($this->consents->state($course)['can_request']);

        app(CourseService::class)->recordConsent($course, $this->staff, $this->fromIp('5.6.7.22'));
        $row = $course->photoConsents()->first();
        $this->assertSame('in_person', $row->source);
        $this->assertSame($this->staff->id, $row->recorded_by);
        $this->assertSame('موافقت — ثبت حضوری', $row->label());
        $this->assertTrue($course->fresh()->hasPhotoConsent());
    }

    public function test_in_person_record_closes_an_open_request_and_its_link(): void
    {
        $course = $this->paidCourse();
        [, $token] = $this->consents->request($course, $this->staff);
        app(CourseService::class)->recordConsent($course, $this->staff);

        $this->assertSame(CoursePhotoConsent::CANCELLED, $this->consents->findByToken($token)->status);
        $this->assertSame(1, CoursePhotoConsent::where('status', CoursePhotoConsent::CANCELLED)->count());
        $this->assertFalse($this->consents->state($course)['can_request'], 'رضایت ثبت شده');
    }

    public function test_rule_3_the_customer_grants_and_withdraws_any_time(): void
    {
        $course = $this->paidCourse();
        $this->consents->grant($course, $this->courseCustomer, 'web', $this->fromIp('5.6.7.23'));
        $this->assertTrue($course->fresh()->hasPhotoConsent());

        app(CourseService::class)->withdrawConsent($course->fresh(), $this->courseCustomer, 'app', $this->fromIp('5.6.7.24'));
        $this->assertFalse($course->fresh()->hasPhotoConsent());
        $row = $course->photoConsents()->first();
        $this->assertSame(CoursePhotoConsent::WITHDRAWN, $row->status);
        $this->assertSame('5.6.7.24', $row->ip_address);

        $this->assertTrue($this->consents->state($course->fresh())['can_request'], 'بعد از پس گرفتن، کادر می‌تواند دوباره بپرسد');
    }

    public function test_sms_link_decision_and_its_limits(): void
    {
        $course = $this->paidCourse();
        [$row, $token] = $this->consents->request($course, $this->staff);

        $found = $this->consents->findByToken($token);
        $this->consents->decideByToken($found, true, $this->fromIp('5.6.7.25'));
        $this->assertSame('sms_link', $row->fresh()->source);
        $this->assertTrue($course->fresh()->hasPhotoConsent());

        $this->assertNull($this->consents->findByToken('short'));
        try {
            $this->consents->decideByToken($this->consents->findByToken($token), false, $this->fromIp('5.6.7.25'));
            $this->fail('پاسخ دوم پذیرفته شد');
        } catch (CourseException $e) {
            $this->assertStringContainsString('قبلاً پاسخ', $e->getMessage());
        }

        $other = $this->paidCourse();
        [$expired, $oldToken] = $this->consents->request($other, $this->staff);
        $this->travel(8)->days();
        $this->expectExceptionMessage('مهلت این لینک گذشته است');
        $this->consents->decideByToken($this->consents->findByToken($oldToken), true, $this->fromIp('5.6.7.26'));
    }

    public function test_another_customer_cannot_decide(): void
    {
        $course = $this->paidCourse();
        $this->consents->request($course, $this->staff);
        $stranger = User::factory()->create(['user_type' => 'customer', 'salon_id' => $course->salon_id]);

        $this->expectException(CourseException::class);
        $this->consents->grant($course, $stranger, 'web');
    }
}
