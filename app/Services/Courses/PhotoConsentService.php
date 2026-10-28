<?php

namespace App\Services\Courses;

use App\Models\CoursePhotoConsent;
use App\Models\CustomerCourse;
use App\Models\User;
use App\Support\Courses\PhotoConsent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * رضایت عکس قبل/بعد دوره، این بار از زبان خود مشتری (۲۰۲۶-۱۰-۲۸؛ ماده‌ی ۵۸ «رضایت صریح» و ۵۹ قانون تجارت الکترونیکی).
 *
 * - کادر «درخواست رضایت» می‌فرستد؛ مشتری متن کامل را در اپ، سایت سالن یا لینک پیامک می‌بیند و خودش موافقت یا مخالفت می‌کند.
 * - ثبت حضوری توسط کادر (همان تأیید «متن را خواندید و صریحاً موافقت کرد؟») راه دوم می‌ماند و در سابقه «ثبت حضوری» است.
 * - هر درخواست و تصمیم یک ردیف course_photo_consents (زمان، IP، user agent، نسخه‌ی متن، منبع)؛ customer_courses.photo_consent_at
 *   وضعیت فعلی و دروازه‌ی آپلود عکس است.
 *
 * سه قاعده‌ی درخواست (تصمیم ابوالفضل، ۲۰۲۶-۱۰-۲۸):
 *  ۱. برای هر دوره فقط یک درخواست باز (تا پاسخ یا ۷ روز)؛ «ارسال دوباره» روزی یک بار (لینک تازه، لینک قبلی باطل).
 *  ۲. بعد از مخالفت مشتری، درخواست تازه فقط پس از ۲۴ ساعت؛ ثبت حضوری همیشه باز است.
 *  ۳. خود مشتری هر وقت بخواهد رضایت می‌دهد یا پس می‌گیرد.
 */
class PhotoConsentService
{
    public const REQUEST_VALID_DAYS = 7;

    public const RESEND_AFTER_HOURS = 24;

    public const REQUEST_AFTER_DECLINE_HOURS = 24;

    public const SOURCES = ['app', 'web', 'sms_link'];

    public const NOT_SENT = 'پیام به مشتری نرسید (شماره ندارد یا سهمیه‌ی پیامک سالن تمام شده). رضایت را حضوری ثبت کنید یا از مشتری بخواهید در اپ یا سایت سالن تأیید کند.';

    public function __construct(
        private readonly \App\Services\Push\AppMessenger $messenger,
        private readonly \App\Services\Links\ShortLinkService $links,
    ) {}

    // ─── درخواست کادر ─────────────────────────────────────────────

    /**
     * درخواست تازه؛ توکن خام لینک پیامک فقط همین‌جا برمی‌گردد (در جدول فقط هش آن است).
     *
     * @return array{0: CoursePhotoConsent, 1: string}
     *
     * @throws CourseException
     */
    public function request(CustomerCourse $course, User $staff): array
    {
        return DB::transaction(function () use ($course, $staff) {
            $locked = CustomerCourse::query()->lockForUpdate()->findOrFail($course->id);
            $this->assertCanRequest($locked);

            $token = $this->newToken();
            $row = CoursePhotoConsent::create([
                'customer_course_id' => $locked->id,
                'user_id' => $locked->user_id,
                'status' => CoursePhotoConsent::PENDING,
                'text_version' => PhotoConsent::VERSION,
                'requested_by' => $staff->id,
                'requested_at' => now(),
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addDays(self::REQUEST_VALID_DAYS),
                'last_sent_at' => now(),
                'send_count' => 1,
            ]);
            activity()->performedOn($locked)->causedBy($staff)->log('course_photo_consent_requested');

            return [$row, $token];
        });
    }

    /**
     * «ارسال دوباره» همان درخواست باز: روزی یک بار، با لینک تازه (لینک قبلی باطل می‌شود؛ از توکن خام چیزی نگه نمی‌داریم).
     *
     * @return array{0: CoursePhotoConsent, 1: string}
     *
     * @throws CourseException
     */
    public function resend(CustomerCourse $course, User $staff): array
    {
        return DB::transaction(function () use ($course) {
            $open = CoursePhotoConsent::query()->open()->where('customer_course_id', $course->id)->lockForUpdate()->latest('id')->first();
            if (! $open) {
                throw new CourseException('درخواست بازی برای این دوره نیست.');
            }
            $next = $open->last_sent_at?->copy()->addHours(self::RESEND_AFTER_HOURS);
            if ($next && $next->isFuture()) {
                throw new CourseException('درخواست امروز فرستاده شده؛ ارسال دوباره از '.$this->when($next).'.');
            }

            $token = $this->newToken();
            $open->update(['token_hash' => hash('sha256', $token), 'last_sent_at' => now(), 'send_count' => $open->send_count + 1]);

            return [$open, $token];
        });
    }

    /**
     * درخواست و فرستادنش به مشتری از مسیر استاندارد پیام‌ها (AppMessenger، تصمیم ۲ گزینه‌ی «ج»): اپ دارد ← پوش، و پیامک لینک‌دار
     * اگر رسید در مهلت «عادی» نیامد (حالت «آزمایشی»: هر دو)؛ اپ ندارد ← همان لحظه پیامک. پیامک از سهمیه‌ی پیامک سالن است. اگر
     * هیچ‌کدام نرفت (بی شماره، سهمیه تمام) درخواست ثبت نمی‌شود و کادر پیام روشن می‌گیرد.
     *
     * @throws CourseException
     */
    public function requestAndSend(CustomerCourse $course, User $staff): CoursePhotoConsent
    {
        [$row, $token] = $this->request($course, $staff);

        if (! $this->send($row, $token)) {
            $row->delete();
            throw new CourseException(self::NOT_SENT);
        }

        return $row;
    }

    /** @throws CourseException */
    public function resendAndSend(CustomerCourse $course, User $staff): CoursePhotoConsent
    {
        [$row, $token] = $this->resend($course, $staff);

        if (! $this->send($row, $token)) {
            throw new CourseException(self::NOT_SENT);
        }

        return $row;
    }

    /** @throws CourseException */
    public function assertCanRequest(CustomerCourse $course): void
    {
        $state = $this->state($course);
        if (! $state['can_request']) {
            throw new CourseException($state['request_blocked_reason'] ?? 'درخواست رضایت برای این دوره ممکن نیست.');
        }
    }

    // ─── تصمیم مشتری ───────────────────────────────────────────────

    /**
     * موافقت خود مشتری (قاعده‌ی ۳: با درخواست یا بی آن). درخواست باز همان ردیف را می‌گیرد، وگرنه ردیف تازه.
     *
     * @throws CourseException
     */
    public function grant(CustomerCourse $course, User $customer, string $source, ?Request $request = null): CoursePhotoConsent
    {
        return $this->decide($course, $customer, CoursePhotoConsent::GRANTED, $source, $request);
    }

    /**
     * مخالفت خود مشتری با درخواست باز. رضایتی که قبلاً داده با withdraw() پس گرفته می‌شود.
     *
     * @throws CourseException
     */
    public function decline(CustomerCourse $course, User $customer, string $source, ?Request $request = null): CoursePhotoConsent
    {
        return $this->decide($course, $customer, CoursePhotoConsent::DECLINED, $source, $request);
    }

    /**
     * ردیف لینک پیامک از روی توکن (فقط سالن جاری؛ scope سالن). پاسخ‌داده، بسته یا منقضی هم برمی‌گردد تا صفحه نتیجه را بگوید؛ تصمیم
     * فقط روی درخواست باز (decideByToken). «ارسال دوباره» توکن تازه می‌سازد و لینک قبلی دیگر پیدا نمی‌شود.
     */
    public function findByToken(string $token): ?CoursePhotoConsent
    {
        if (! preg_match('/^[A-Za-z0-9]{32,64}$/', $token)) {
            return null;
        }

        return CoursePhotoConsent::query()->with('customerCourse')->where('token_hash', hash('sha256', $token))->first();
    }

    /** @throws CourseException */
    public function decideByToken(CoursePhotoConsent $row, bool $agree, Request $request): CoursePhotoConsent
    {
        if (! $row->isOpen()) {
            throw new CourseException($row->status === CoursePhotoConsent::PENDING
                ? 'مهلت این لینک گذشته است؛ از سالن بخواهید دوباره بفرستد.'
                : 'به این درخواست قبلاً پاسخ داده شده است.');
        }
        $customer = User::query()->findOrFail($row->user_id);

        return $this->decide($row->customerCourse, $customer, $agree ? CoursePhotoConsent::GRANTED : CoursePhotoConsent::DECLINED, 'sms_link', $request, $row->id);
    }

    /** مشتری رضایت را پس می‌گیرد (ماده‌ی ۵۹ د و هـ) */
    public function withdraw(CustomerCourse $course, User $customer, string $source, ?Request $request = null): void
    {
        DB::transaction(function () use ($course, $customer, $source, $request) {
            $locked = CustomerCourse::query()->lockForUpdate()->findOrFail($course->id);
            if (! $locked->photo_consent_at) {
                return;
            }

            $locked->update(['photo_consent_at' => null, 'photo_consent_by' => null]);
            CoursePhotoConsent::create($this->decisionRow($locked, CoursePhotoConsent::WITHDRAWN, $source, $request));
            activity()->performedOn($locked)->causedBy($customer)->withProperties(['source' => $source])->log('course_photo_consent_withdrawn');
        });
    }

    // ─── ثبت حضوری (راه دوم) ───────────────────────────────────────

    /** کادر متن را برای مشتری خواند و مشتری حضوری موافقت کرد؛ درخواست باز بسته می‌شود */
    public function recordInPerson(CustomerCourse $course, User $staff, ?Request $request = null): void
    {
        DB::transaction(function () use ($course, $staff, $request) {
            $locked = CustomerCourse::query()->lockForUpdate()->findOrFail($course->id);
            if ($locked->photo_consent_at) {
                return;
            }

            $this->closeOpen($locked);
            $locked->update(['photo_consent_at' => now(), 'photo_consent_by' => $staff->id]);
            CoursePhotoConsent::create($this->decisionRow($locked, CoursePhotoConsent::GRANTED, 'in_person', $request) + ['recorded_by' => $staff->id]);
            activity()->performedOn($locked)->causedBy($staff)
                ->withProperties(['version' => PhotoConsent::VERSION, 'source' => 'in_person'])->log('course_photo_consent');
        });
    }

    // ─── وضعیت برای پنل‌ها و اپ‌ها ──────────────────────────────────

    /**
     * @return array{granted: bool, open_request: ?CoursePhotoConsent, last_decline: ?CoursePhotoConsent, can_request: bool,
     *               request_blocked_reason: ?string, next_request_at: ?Carbon, can_resend: bool, next_resend_at: ?Carbon}
     */
    public function state(CustomerCourse $course): array
    {
        $open = CoursePhotoConsent::query()->open()->where('customer_course_id', $course->id)->latest('id')->first();
        $lastDecision = CoursePhotoConsent::query()->where('customer_course_id', $course->id)
            ->whereIn('status', [CoursePhotoConsent::GRANTED, CoursePhotoConsent::DECLINED, CoursePhotoConsent::WITHDRAWN])
            ->latest('decided_at')->latest('id')->first();
        $lastDecline = $lastDecision?->status === CoursePhotoConsent::DECLINED ? $lastDecision : null;
        $granted = $course->photo_consent_at !== null;

        $nextRequest = $lastDecline?->decided_at?->copy()->addHours(self::REQUEST_AFTER_DECLINE_HOURS);
        $reason = match (true) {
            $granted => 'رضایت عکس این دوره ثبت شده است.',
            in_array($course->status, ['cancelled', 'completed'], true) => 'این دوره بسته شده است.',
            $open !== null => 'درخواست قبلی هنوز در انتظار پاسخ مشتری است.',
            $nextRequest !== null && $nextRequest->isFuture() => 'مشتری '.$this->when($lastDecline->decided_at).' مخالفت کرد؛ درخواست تازه از '.$this->when($nextRequest).'.',
            default => null,
        };
        $nextResend = $open?->last_sent_at?->copy()->addHours(self::RESEND_AFTER_HOURS);

        return [
            'granted' => $granted,
            'open_request' => $open,
            'last_decline' => $lastDecline,
            'can_request' => $reason === null,
            'request_blocked_reason' => $reason,
            'next_request_at' => $nextRequest?->isFuture() ? $nextRequest : null,
            'can_resend' => $open !== null && ($nextResend === null || ! $nextResend->isFuture()),
            'next_resend_at' => $open !== null && $nextResend?->isFuture() ? $nextResend : null,
        ];
    }

    // ─── داخلی ─────────────────────────────────────────────────────

    /** @throws CourseException */
    private function decide(CustomerCourse $course, User $customer, string $status, string $source, ?Request $request, ?int $rowId = null): CoursePhotoConsent
    {
        if (! in_array($source, self::SOURCES, true)) {
            throw new \InvalidArgumentException("Unknown consent source {$source}");
        }

        return DB::transaction(function () use ($course, $customer, $status, $source, $request, $rowId) {
            $locked = CustomerCourse::query()->lockForUpdate()->findOrFail($course->id);
            if ((int) $locked->user_id !== (int) $customer->id) {
                throw new CourseException('این دوره مال شما نیست.');
            }

            $open = CoursePhotoConsent::query()->open()->where('customer_course_id', $locked->id)
                ->when($rowId, fn ($q) => $q->whereKey($rowId))->lockForUpdate()->latest('id')->first();

            if ($status === CoursePhotoConsent::DECLINED && ! $open) {
                throw new CourseException($rowId ? 'به این درخواست قبلاً پاسخ داده شده است.' : 'درخواست رضایتی در انتظار پاسخ شما نیست.');
            }
            if ($status === CoursePhotoConsent::GRANTED && $locked->photo_consent_at) {
                $open?->update(['status' => CoursePhotoConsent::CANCELLED]);

                return $locked->photoConsents()->where('status', CoursePhotoConsent::GRANTED)->first()
                    ?? CoursePhotoConsent::create($this->decisionRow($locked, $status, $source, $request));
            }

            $values = $this->decisionRow($locked, $status, $source, $request);
            if ($open) {
                $open->update(array_diff_key($values, array_flip(['customer_course_id', 'user_id'])));
                $row = $open;
            } else {
                $row = CoursePhotoConsent::create($values);
            }

            if ($status === CoursePhotoConsent::GRANTED) {
                $locked->update(['photo_consent_at' => now(), 'photo_consent_by' => $customer->id]);
                activity()->performedOn($locked)->causedBy($customer)
                    ->withProperties(['version' => PhotoConsent::VERSION, 'source' => $source])->log('course_photo_consent');
            } else {
                activity()->performedOn($locked)->causedBy($customer)->withProperties(['source' => $source])->log('course_photo_consent_declined');
            }

            return $row->fresh();
        });
    }

    /** @return array<string, mixed> */
    private function decisionRow(CustomerCourse $course, string $status, string $source, ?Request $request): array
    {
        return [
            'customer_course_id' => $course->id,
            'user_id' => $course->user_id,
            'status' => $status,
            'source' => $source,
            'text_version' => PhotoConsent::VERSION,
            'decided_at' => now(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? (mb_substr((string) $request->userAgent(), 0, 1000) ?: null) : null,
        ];
    }

    private function send(CoursePhotoConsent $row, string $token): bool
    {
        $course = $row->customerCourse()->with('user')->firstOrFail();
        $salon = \App\Models\Salon::withoutGlobalScopes()->findOrFail($course->salon_id);
        $customer = $course->user;
        if (! $customer) {
            return false;
        }

        $link = $this->links->shorten(
            route('photo-consent.show', ['salon_slug' => $salon->slug, 'token' => $token]),
            $row->expires_at,
        );

        return $this->messenger->deliver(new \App\Services\Push\Outgoing(
            recipient: $customer,
            phone: $customer->phone,
            text: \App\Support\Sms\SmsText::photoConsentRequest($course, $link),
            salonId: $salon->id,
            smsFeature: \App\Support\Features\Feature::SmsCustomer,
            pushKey: \App\Services\Push\PushPolicy::COURSE_PHOTO_CONSENT_CUSTOMER,
            urgency: \App\Services\Push\PushUrgency::Normal,
            data: ['course_id' => $course->id, 'type' => 'photo_consent'],
            inbox: true,
        ));
    }

    private function closeOpen(CustomerCourse $course): void
    {
        CoursePhotoConsent::query()->where('customer_course_id', $course->id)->where('status', CoursePhotoConsent::PENDING)
            ->update(['status' => CoursePhotoConsent::CANCELLED]);
    }

    private function newToken(): string
    {
        return Str::random(40);
    }

    private function when(Carbon $at): string
    {
        return verta($at)->format('Y/m/d H:i');
    }
}
