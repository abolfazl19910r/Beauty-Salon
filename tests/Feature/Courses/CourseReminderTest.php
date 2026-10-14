<?php

namespace Tests\Feature\Courses;

use App\Models\CourseReminder;
use App\Models\CustomerCourse;
use App\Models\NotificationSetting;
use App\Services\Courses\CourseReminderService;
use App\Services\SMSService;
use App\Support\CurrentSalon;
use App\Support\Features\Feature;
use App\Support\Notifications\NotificationEvents;
use App\Support\Sms\SmsTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/** یادآوری جلسه‌ی بعد، یک پیگیری بعد از ۱۴ روز، پایان مهلت؛ ادامه بعد از تنزل پلن (تصمیم‌های ۱۱ و ۱۲ بسته‌ی ب) */
class CourseReminderTest extends TestCase
{
    use CreatesCourses;
    use RefreshDatabase;

    /** @var list<array{0: string, 1: string}> */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-14 11:00:00');
        app(CurrentSalon::class)->get()->update(['sms_name' => 'رزگلد', 'sms_quota_per_month' => 1000, 'subscription_ends_at' => now()->addYears(2), 'is_suspended' => false]);
        $this->setUpCourseWorld();

        $sent = &$this->sent;
        app()->instance(SMSService::class, new class($sent) extends SMSService
        {
            public function __construct(private array &$log)
            {
                parent::__construct();
            }

            public function send(string $mobile, string $message, ?int $salonId = null, ?Feature $feature = null): bool
            {
                $ok = parent::send($mobile, $message, $salonId, $feature);
                if ($ok) {
                    $this->log[] = [$mobile, $message];
                }

                return $ok;
            }
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function remind(): int
    {
        return array_sum(app(CourseReminderService::class)->runAll());
    }

    /** دوره‌ی پرداخت‌شده که جلسه‌ی اولش فردا انجام شد؛ موعد جلسه‌ی دوم ۳۵ روز بعد */
    private function courseAfterFirstSession(): CustomerCourse
    {
        $course = $this->paidCourse();
        $booking = app(\App\Services\Courses\CourseService::class)->bookSession($course, $this->courseSpecialist->id, $this->sessionAt(1));
        $this->travel(1)->days();
        $booking->update(['status' => 'completed']);
        $this->sent = []; // پیامک‌های نوبت و باشگاه

        return $course->fresh();
    }

    public function test_next_session_reminder_goes_once_then_one_follow_up_after_fourteen_days(): void
    {
        $course = $this->courseAfterFirstSession();
        $this->assertSame(0, $this->remind(), 'هنوز موعد جلسه‌ی دوم نرسیده');

        $this->travel(35)->days();
        $this->assertSame(1, $this->remind());
        $this->assertSame($this->courseCustomer->phone, $this->sent[0][0]);
        $this->assertStringContainsString('جلسه‌ی ۲', $this->sent[0][1]);
        $this->assertStringContainsString('۵ جلسه مانده', $this->sent[0][1]);
        $this->assertSame(0, $this->remind(), 'برای هر شماره‌ی جلسه یک بار');

        $this->travel(13)->days();
        $this->assertSame(0, $this->remind());
        $this->travel(1)->days();
        $this->assertSame(1, $this->remind(), 'یک پیگیری بعد از ۱۴ روز');

        $this->travel(30)->days();
        $this->assertSame(0, $this->remind(), 'بیشتر از یک پیگیری نه');
        $this->assertEqualsCanonicalizing(['next_session', 'next_session_followup'], CourseReminder::where('customer_course_id', $course->id)->pluck('kind')->all());
    }

    public function test_a_course_with_a_future_booking_gets_no_reminder(): void
    {
        $course = $this->courseAfterFirstSession();
        $this->travel(35)->days();
        app(\App\Services\Courses\CourseService::class)->bookSession($course->fresh(), $this->courseSpecialist->id, $this->sessionAt(2));

        $this->assertSame(0, $this->remind());
    }

    public function test_expiring_reminder_goes_once_thirty_days_before_the_end(): void
    {
        $course = $this->paidCourse(['validity_days' => 60]);
        $this->sent = [];
        $this->assertSame(0, $this->remind());

        $this->travel(31)->days();
        $this->assertSame(1, $this->remind());
        $this->assertStringContainsString('۶ جلسه از', $this->sent[0][1]);
        $this->assertStringContainsString(verta($course->expires_at)->format('Y/m/d'), $this->sent[0][1]);
        $this->travel(5)->days();
        $this->assertSame(0, $this->remind(), 'یادآوری جلسه‌ی اول تا ۱۴ روز بعد از هشدار پایان مهلت نمی‌رود');
    }

    public function test_first_session_reminder_waits_a_few_days_after_purchase(): void
    {
        $this->paidCourse();
        $this->sent = [];
        $this->assertSame(0, $this->remind());

        $this->travel(3)->days();
        $this->assertSame(1, $this->remind());
        $this->assertStringContainsString('جلسه‌ی ۱', $this->sent[0][1]);
    }

    public function test_reminders_continue_after_the_plan_loses_treatment_courses(): void
    {
        $this->courseAfterFirstSession();
        $this->courseSalonWithout(Feature::TreatmentCourses);
        $this->travel(35)->days();

        $this->assertSame(1, $this->remind());
    }

    public function test_no_reminder_without_customer_sms_in_the_plan_or_with_the_event_switched_off(): void
    {
        $this->courseAfterFirstSession();
        $this->travel(35)->days();

        $salon = $this->courseSalonWithout(Feature::SmsCustomer);
        $this->assertSame(0, $this->remind());
        $this->assertSame(0, CourseReminder::count(), 'ارسال‌نشده ثبت نمی‌شود تا فردا دوباره امتحان شود');

        $this->courseSalonWithout();
        NotificationSetting::withoutGlobalScopes()->updateOrCreate(
            ['salon_id' => $salon->id, 'event_key' => NotificationEvents::COURSE_REMINDER_CUSTOMER],
            ['sms_enabled' => false, 'database_enabled' => true, 'telegram_enabled' => false],
        );
        app(\App\Services\Notification\NotificationSettingService::class)->flush($salon->id);
        $this->assertSame(0, $this->remind());
    }

    public function test_reminders_keep_a_fifth_of_the_monthly_quota_for_booking_sms(): void
    {
        $this->courseAfterFirstSession();
        $this->travel(35)->days();
        app(CurrentSalon::class)->get()->update(['sms_quota_per_month' => 1]);

        $this->assertSame(0, $this->remind());
    }

    public function test_salon_text_is_used_and_the_default_texts_are_short(): void
    {
        foreach (['course_next_session', 'course_expiring'] as $key) {
            $this->assertLessThanOrEqual(2, \App\Support\SmsParts::count(SmsTemplates::sample($key)), $key);
        }

        // جای‌خالی اختیاری {جلسه} در متن‌های نوبت؛ متن‌های پیش‌فرض بدون آن (بدون تغییر رفتار)
        $this->assertArrayHasKey('جلسه', SmsTemplates::get('reminder_customer')['placeholders']);
        $this->assertStringNotContainsString('{جلسه}', SmsTemplates::get('reminder_customer')['default']);
    }
}
