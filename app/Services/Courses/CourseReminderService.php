<?php

namespace App\Services\Courses;

use App\Models\CourseReminder;
use App\Models\CustomerCourse;
use App\Models\Salon;
use App\Services\Links\ShortLinkService;
use App\Services\Notification\NotificationSettingService;
use App\Services\Sms\SmsQuotaService;
use App\Services\SMSService;
use App\Support\CurrentSalon;
use App\Support\Features\Feature;
use App\Support\Notifications\NotificationEvents;
use App\Support\Sms\SmsText;
use App\Support\SmsParts;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

/**
 * یادآوری‌های دوره‌ی درمانی (بسته‌ی ب، تصمیم ۱۱ و ۱۲) — روزانه از courses:remind.
 *
 * - next_session: وقتی موعد جلسه‌ی بعد (next_due_at) رسید و مشتری هنوز نوبتی نگرفته؛ برای هر شماره‌ی جلسه فقط یک بار
 *   (جلسه‌ی اول: دست‌کم ۳ روز بعد از خرید).
 * - next_session_followup: اگر ۱۴ روز بعد از آن هنوز رزرو نکرده، فقط یک پیگیری دیگر (برای همان شماره‌ی جلسه).
 * - expiring: ۳۰ روز مانده به پایان مهلت دوره، اگر جلسه‌ی رزرونشده مانده؛ فقط یک بار برای هر دوره.
 *
 * دوره‌ای که نوبت فعال آینده دارد یادآوری نمی‌گیرد؛ بین دو یادآوری یک دوره دست‌کم ۱۴ روز. یادآوری‌ها بعد از تنزل پلن هم ادامه دارند (تصمیم ۱۲) — گیت فقط دسته‌ی
 * «پیامک به مشتری» (SmsCustomer) و سوییچ رویداد COURSE_REMINDER_CUSTOMER در تنظیمات اعلان است. مثل پیگیری مشتری غایب، فقط تا
 * وقتی ۲۰٪ سهمیه‌ی ماه برای پیامک‌های نوبت بماند؛ اولین پیامکی که جا نداشت کار آن سالن را تا فردا متوقف می‌کند.
 * ردیف course_reminders یکتا (دوره + نوع + شماره‌ی جلسه) است: اجرای هم‌زمان یا تکراری دو پیامک نمی‌فرستد.
 */
class CourseReminderService
{
    public const FOLLOWUP_AFTER_DAYS = 14;

    public const EXPIRING_BEFORE_DAYS = 30;

    /** جلسه‌ی اول: تا چند روز بعد از خرید یادآوری نمی‌رود (مشتری تازه خریده) */
    public const FIRST_SESSION_AFTER_DAYS = 3;

    public const RESERVE_RATIO = 0.2;

    public function __construct(
        private readonly SMSService $sms,
        private readonly ShortLinkService $links,
        private readonly NotificationSettingService $settings,
        private readonly SmsQuotaService $quota,
    ) {}

    /** @return array<int, int> پیامک‌های فرستاده‌شده برای هر سالن */
    public function runAll(): array
    {
        $salonIds = CustomerCourse::withoutGlobalScopes()->where('status', 'active')->distinct()->pluck('salon_id');
        $sent = [];

        foreach ($salonIds as $salonId) {
            $salon = Salon::withoutGlobalScopes()->find($salonId);
            if (! $salon || ! $salon->hasActiveSubscription()) {
                continue;
            }
            if (! $this->settings->isEnabled(NotificationEvents::COURSE_REMINDER_CUSTOMER, 'sms', $salon->id)
                && ! $this->settings->isEnabled(NotificationEvents::COURSE_REMINDER_CUSTOMER, 'push', $salon->id)) {
                continue;
            }

            $sent[$salon->id] = app(CurrentSalon::class)->withSalon($salon, fn () => $this->runForSalon($salon));
        }

        return $sent;
    }

    public function runForSalon(Salon $salon): int
    {
        $sent = 0;

        foreach ($this->due($salon) as [$course, $kind, $number, $remaining]) {
            $result = $this->send($salon, $course, $kind, $number, $remaining);
            if ($result === null) {
                break;
            }
            $sent += $result ? 1 : 0;
        }

        return $sent;
    }

    /**
     * یادآوری‌هایی که امروز باید بروند.
     *
     * @return list<array{0: CustomerCourse, 1: string, 2: int, 3: int}>
     */
    public function due(Salon $salon): array
    {
        $courses = CustomerCourse::withoutGlobalScopes()
            ->with('user')
            ->where('salon_id', $salon->id)
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->whereColumn('sessions_used', '<', 'sessions_total')
            ->whereNotExists(fn ($q) => $q->from('bookings')
                ->whereColumn('bookings.customer_course_id', 'customer_courses.id')
                ->whereIn('bookings.status', CustomerCourse::ACTIVE_BOOKING_STATUSES)
                ->where('bookings.booking_time', '>=', now()))
            ->orderBy('id')
            ->get();

        $sent = CourseReminder::query()->whereIn('customer_course_id', $courses->modelKeys())->get()
            ->groupBy('customer_course_id');

        $due = [];
        foreach ($courses as $course) {
            if (! $course->user?->phone) {
                continue;
            }
            $remaining = $course->remainingSessions();
            if ($remaining <= 0) {
                continue;
            }
            $number = $course->sessions_used + 1;
            $mine = $sent->get($course->id, collect());
            $has = fn (string $kind, int $n) => $mine->first(fn ($r) => $r->kind === $kind && (int) $r->session_number === $n);

            // هر یادآوری دوره تا ۱۴ روز جای یادآوری دیگرِ همان دوره را می‌گیرد (پیامک پشت سر هم نه)
            $last = $mine->max('sent_at');
            if ($last && Carbon::parse($last)->gt(now()->subDays(self::FOLLOWUP_AFTER_DAYS))) {
                continue;
            }

            if ($course->expires_at && $course->expires_at->lte(now()->addDays(self::EXPIRING_BEFORE_DAYS)) && ! $has('expiring', 0)) {
                $due[] = [$course, 'expiring', 0, $remaining];

                continue;
            }

            if (! $course->next_due_at || $course->next_due_at->isFuture()) {
                continue;
            }
            if ($number === 1 && $course->purchased_at && $course->purchased_at->gt(now()->subDays(self::FIRST_SESSION_AFTER_DAYS))) {
                continue;
            }

            $first = $has('next_session', $number);
            if (! $first) {
                $due[] = [$course, 'next_session', $number, $remaining];
            } elseif ($first->sent_at->lte(now()->subDays(self::FOLLOWUP_AFTER_DAYS)) && ! $has('next_session_followup', $number)) {
                $due[] = [$course, 'next_session_followup', $number, $remaining];
            }
        }

        return $due;
    }

    /** true فرستاده شد؛ false این یکی رد شد (برو بعدی)؛ null سهمیه جا ندارد یا ارسال نشد — تا فردا ادامه نده */
    private function send(Salon $salon, CustomerCourse $course, string $kind, int $number, int $remaining): ?bool
    {
        $link = fn () => $this->links->shorten(
            route('my-courses.book', ['salon_slug' => $salon->slug, 'course' => $course->id]),
            now()->addDays(self::FOLLOWUP_AFTER_DAYS + 7)
        );
        $text = $kind === 'expiring'
            ? SmsText::courseExpiring($course, $remaining, $link)
            : SmsText::courseNextSession($course, $remaining, $link);
        $parts = SmsParts::count($text);

        if (! $this->hasRoomFor($salon, $parts)) {
            return null;
        }

        // اول ردیف (یکتا) — اجرای هم‌زمان دوم همین‌جا رد می‌شود و پیامک تکراری نمی‌رود
        try {
            $row = CourseReminder::create([
                'customer_course_id' => $course->id, 'kind' => $kind, 'session_number' => $number, 'sent_at' => now(), 'sms_parts' => $parts,
            ]);
        } catch (QueryException) {
            return false;
        }

        // پوش اپ یا پیامک (بسته‌ی ۴)؛ یادآوری دوره «عادی» است: پیامک فقط اگر اپ در ۱۰ دقیقه رسید نفرستد
        $delivered = app(\App\Services\Push\AppMessenger::class)->deliver(new \App\Services\Push\Outgoing(
            recipient: $course->user,
            phone: $course->user->phone,
            text: $text,
            salonId: $salon->id,
            smsFeature: Feature::SmsCustomer,
            pushKey: NotificationEvents::COURSE_REMINDER_CUSTOMER,
            settingsEvent: NotificationEvents::COURSE_REMINDER_CUSTOMER,
            data: ['course_id' => $course->id],
            inbox: true,
        ));

        if (! $delivered) {
            $row->delete();

            return null;
        }

        return true;
    }

    private function hasRoomFor(Salon $salon, int $parts): bool
    {
        $available = $this->quota->remaining($salon) + $this->quota->credit($salon);

        return $available - $parts >= (int) ceil($this->quota->quotaFor($salon) * self::RESERVE_RATIO);
    }
}
