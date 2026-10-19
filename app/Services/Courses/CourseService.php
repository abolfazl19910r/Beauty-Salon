<?php

namespace App\Services\Courses;

use App\Exceptions\BookingNotAvailableException;
use App\Models\AdminWallet;
use App\Models\Booking;
use App\Models\CoursePackage;
use App\Models\CourseSetting;
use App\Models\CustomerCourse;
use App\Models\CustomerCoursePayment;
use App\Models\DiscountCode;
use App\Models\DiscountUsage;
use App\Models\LoyaltySetting;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\WalletSetting;
use App\Repositories\Contracts\DiscountCodeRepositoryInterface;
use App\Services\Booking\BookingService;
use App\Services\Discount\DiscountCalculator;
use App\Support\CurrentSalon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * قلب دوره‌های درمانی (بسته‌ی ب، تصمیم‌های ۲۰۲۶-۱۰-۱۴). هر تغییر شمارش یا پول یک دوره زیر قفل ردیف customer_courses انجام
 * می‌شود؛ رزرو جلسه اول قفل دوره و بعد قفل متخصص (BookingService::assertBookingFits) — همیشه به همین ترتیب.
 *
 * - خرید: ردیف pending_payment با snapshot بسته؛ با اولین پرداختی که به حداقل پیش‌پرداخت برسد active می‌شود و مهلت از همان لحظه.
 * - رزرو: فقط به اندازه‌ی پول پرداخت‌شده (CustomerCourse::paidSessions)، از زودترین روز مجاز تا پایان مهلت؛ کادر می‌تواند فاصله‌ی
 *   حداقل را دور بزند. نوبت پرداخت‌شده (paid) با پیش‌پرداخت ۰ ساخته می‌شود؛ پیامک‌ها مثل نوبت پرداخت‌شده‌ی عادی.
 * - انجام: یک جلسه مصرف، موعد بعد = زمان نوبت + interval_days، درآمد متخصص یک بار = per_session_value × سهم آنلاین پرداخت‌ها.
 * - لغو: به‌موقع یا توسط سالن ← جلسه برمی‌گردد؛ لغو دیرهنگام مشتری فقط با تنظیم سالن سوخت می‌شود (تصمیم ۷).
 * - لغو دوره: نوبت‌های آینده لغو، برگشت به کیف پول مشتری (تصمیم ۱۰).
 */
class CourseService
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly DiscountCodeRepositoryInterface $discountCodes,
        private readonly DiscountCalculator $discountCalculator,
    ) {}

    // ─── خرید ─────────────────────────────────────────────────────

    /**
     * @throws CourseException
     */
    public function purchase(User $customer, CoursePackage $package, ?string $discountCode = null, ?User $soldBy = null): CustomerCourse
    {
        if (! $package->is_active) {
            throw new CourseException('این دوره دیگر فروخته نمی‌شود.');
        }
        \App\Services\Salon\SalonPauseService::assertOpen((int) $package->salon_id);
        if ((int) $customer->salon_id !== (int) $package->salon_id) {
            throw new CourseException('این دوره مال سالن دیگری است.');
        }

        return DB::transaction(function () use ($customer, $package, $discountCode, $soldBy) {
            $listPrice = (float) $package->price;
            $multi = $package->isMultiService();
            $discount = 0.0;
            $code = null;

            if ($discountCode !== null && trim($discountCode) !== '') {
                $code = $this->discountCodes->lockByCode(trim($discountCode));
                if (! $code || ! $code->isValid() || ! $code->canBeUsedBy($customer->id)) {
                    throw new CourseException('کد تخفیف نامعتبر است یا منقضی شده.');
                }
                if (! $code->valid_for_courses) {
                    throw new CourseException('این کد تخفیف روی دوره‌ها معتبر نیست.');
                }
                $discount = (float) $this->discountCalculator->calculate($code, $listPrice)['discount_amount'];
            }

            $price = max(0.0, $listPrice - $discount);

            $course = CustomerCourse::create([
                'salon_id' => $package->salon_id,
                'user_id' => $customer->id,
                'course_package_id' => $package->id,
                'name' => $package->name,
                'service_id' => $package->service_id,
                'extra_service_ids' => $multi ? array_values(array_map('intval', $package->extra_service_ids)) : null,
                'session_duration' => $multi ? $package->sessionDuration() : null,
                'specialist_id' => $package->specialist_id,
                'sessions_total' => $package->sessions_count,
                'interval_days' => $package->interval_days,
                'min_interval_days' => $package->min_interval_days,
                'validity_days' => $package->validity_days,
                'list_price' => $listPrice,
                'discount_code' => $code?->code,
                'discount_amount' => $discount,
                'price_total' => $price,
                'per_session_value' => round($price / max(1, $package->sessions_count), 2),
                'min_prepayment' => $package->minPrepaymentFor($price),
                'status' => 'pending_payment',
                'sold_by' => $soldBy?->id,
            ]);

            if ($code) {
                $code->incrementUsage();
                DiscountUsage::create([
                    'discount_code_id' => $code->id,
                    'user_id' => $customer->id,
                    'customer_course_id' => $course->id,
                    'amount_saved' => $discount,
                ]);
            }

            if ($price <= 0) {
                $this->recordPaymentLocked($course, 0, 'full_discount', []);
            }

            return $course->fresh();
        });
    }

    /** دوره‌ی پرداخت‌نشده‌ای که دیگر پرداخت نمی‌شود (۳۰ دقیقه، یا انصراف): لغو و آزاد کردن کد تخفیف */
    public function abandon(CustomerCourse $course, string $reason): bool
    {
        return DB::transaction(function () use ($course, $reason) {
            $locked = CustomerCourse::withoutGlobalScopes()->lockForUpdate()->find($course->id);
            if (! $locked || $locked->status !== 'pending_payment' || $locked->amount_paid > 0) {
                return false;
            }

            $locked->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancel_reason' => $reason]);
            $this->releaseDiscount($locked);

            return true;
        });
    }

    private function releaseDiscount(CustomerCourse $course): void
    {
        $usage = DiscountUsage::query()->where('customer_course_id', $course->id)->first();
        if (! $usage) {
            return;
        }

        DiscountCode::withoutGlobalScopes()->whereKey($usage->discount_code_id)->where('used_count', '>', 0)->decrement('used_count');
        $usage->delete();
    }

    // ─── پرداخت ───────────────────────────────────────────────────

    /**
     * ثبت یک پرداخت (آنلاین بعد از تأیید درگاه، کیف پول، یا حضوری توسط مدیر). مبلغ بیش از مانده‌ی دوره پذیرفته نمی‌شود.
     * idempotent برای تراکنش درگاه (payment_transaction_id یکتا).
     *
     * @param  array{payment_transaction_id?: int, wallet_amount?: float, recorded_by?: int, reference?: string, note?: string}  $extra
     */
    public function recordPayment(CustomerCourse $course, float $amount, string $method, array $extra = []): ?CustomerCoursePayment
    {
        return DB::transaction(fn () => $this->recordPaymentLocked($course, $amount, $method, $extra));
    }

    private function recordPaymentLocked(CustomerCourse $course, float $amount, string $method, array $extra): ?CustomerCoursePayment
    {
        $locked = CustomerCourse::withoutGlobalScopes()->lockForUpdate()->findOrFail($course->id);

        if (isset($extra['payment_transaction_id'])
            && CustomerCoursePayment::withoutGlobalScopes()->where('payment_transaction_id', $extra['payment_transaction_id'])->exists()) {
            return null;
        }
        if (in_array($locked->status, ['cancelled', 'completed'], true) && $method !== 'full_discount') {
            throw new CourseException('این دوره دیگر پرداخت نمی‌پذیرد.');
        }

        // پولی بیش از مانده (مثلاً مدیر هم‌زمان پرداخت حضوری ثبت کرد) — اضافه‌ی پرداخت آنلاین به کیف پول مشتری
        $excess = round($amount - $locked->balanceDue(), 2);
        if ($excess > 0.5 && in_array($method, CustomerCoursePayment::ONLINE_METHODS, true)) {
            User::find($locked->user_id)?->getOrCreateWallet()->addRefund($excess, null, "مبلغ اضافه‌ی پرداخت دوره‌ی «{$locked->name}»", ['customer_course_id' => $locked->id]);
        }
        $amount = round(min($amount, $locked->balanceDue()), 2);
        $wasFullyPaid = $locked->isFullyPaid() && $locked->status !== 'pending_payment';

        $payment = CustomerCoursePayment::create([
            'salon_id' => $locked->salon_id,
            'customer_course_id' => $locked->id,
            'amount' => $amount,
            'wallet_amount' => (float) ($extra['wallet_amount'] ?? 0),
            'method' => $method,
            'payment_transaction_id' => $extra['payment_transaction_id'] ?? null,
            'recorded_by' => $extra['recorded_by'] ?? null,
            'reference' => $extra['reference'] ?? null,
            'note' => $extra['note'] ?? null,
        ]);

        $locked->amount_paid = round($locked->amount_paid + $amount, 2);

        if ($locked->status === 'pending_payment' && $locked->amount_paid + 0.5 >= $locked->min_prepayment) {
            $locked->status = 'active';
            $locked->purchased_at = now();
            $locked->expires_at = $locked->validity_days ? now()->addDays($locked->validity_days)->endOfDay() : null;
            $locked->next_due_at = now();
        }
        $locked->save();

        // امتیاز باشگاه یک بار روی مبلغ کل دوره، وقتی کامل پرداخت شد (نه هر جلسه) — User::addLoyaltyPoints امکان پلن را می‌سنجد
        if (! $wasFullyPaid && $locked->status !== 'pending_payment' && $locked->isFullyPaid() && $locked->price_total > 0) {
            $this->awardLoyalty($locked);
        }

        $course->setRawAttributes($locked->getAttributes(), true);

        return $payment;
    }

    private function awardLoyalty(CustomerCourse $course): void
    {
        try {
            $perAmount = (int) LoyaltySetting::getValue('points_per_amount', 10000);
            $points = (int) floor($course->price_total / ($perAmount > 0 ? $perAmount : 10000));
            if ($points > 0) {
                $course->user?->addLoyaltyPoints($points, "خرید دوره‌ی «{$course->name}»");
            }
        } catch (\Throwable $e) {
            Log::warning('Course loyalty points failed', ['course_id' => $course->id, 'error' => $e->getMessage()]);
        }
    }

    // ─── رزرو جلسه ────────────────────────────────────────────────

    /**
     * @param  bool  $byStaff  مدیر/منشی: فاصله‌ی حداقل را دور می‌زند (با هشدار در فرم)، ولی مهلت و پول همچنان مهم‌اند
     *
     * @throws CourseException|BookingNotAvailableException
     */
    public function bookSession(CustomerCourse $course, int $specialistId, string $bookingTime, bool $byStaff = false, string $source = 'online'): Booking
    {
        \App\Services\Salon\SalonPauseService::assertOpen((int) $course->salon_id);
        $time = Carbon::parse($bookingTime);

        $booking = DB::transaction(function () use ($course, $specialistId, $time, $byStaff, $source) {
            $locked = CustomerCourse::withoutGlobalScopes()->lockForUpdate()->findOrFail($course->id);
            $reserved = $locked->reservedCount();

            if ($blocker = $locked->bookingBlocker($reserved)) {
                throw new CourseException($blocker);
            }
            if ($locked->specialist_id && (int) $locked->specialist_id !== $specialistId) {
                throw new CourseException('این دوره فقط با متخصص تعیین‌شده انجام می‌شود.');
            }
            foreach ($locked->sessionServices() as $service) {
                if (! $this->specialistOffersService($specialistId, (int) $service->id)) {
                    throw new CourseException('این متخصص همه‌ی خدمت‌های این دوره را انجام نمی‌دهد.');
                }
            }
            if (! $time->isFuture()) {
                throw new CourseException('زمان جلسه باید در آینده باشد.');
            }
            if ($locked->expires_at && $time->gt($locked->expires_at)) {
                throw new CourseException('این زمان بعد از پایان مهلت دوره است.');
            }
            if (! $byStaff && $time->lt($locked->earliestNextSession())) {
                throw new CourseException(sprintf('جلسه‌ی بعد زودتر از %s ممکن نیست (فاصله‌ی حداقل %s روز).',
                    verta($locked->earliestNextSession())->format('Y/m/d'), to_persian_num((string) $locked->min_interval_days)));
            }

            // جلسه‌ی چندخدمتی: یک نوبت با مدت جمع خدمت‌ها
            $this->bookingService->assertBookingFits($specialistId, $time->format('Y-m-d H:i:s'), (int) $locked->service_id, null, $locked->session_duration ?: null);
            $specialist = \App\Models\Specialist::withoutGlobalScopes()->find($specialistId);

            try {
                return Booking::create([
                    'service_id' => $locked->service_id,
                    'duration_minutes' => $locked->session_duration ?: null,
                    'specialist_id' => $specialistId,
                    'user_id' => $locked->user_id,
                    'booking_time' => $time->format('Y-m-d H:i:s'),
                    'status' => ($specialist?->auto_confirm_bookings ?? false) ? 'confirmed' : 'pending',
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                    'prepayment_amount' => 0,
                    'discount_amount' => 0,
                    'payment_reference' => 'COURSE-'.$locked->id,
                    'payment_details' => ['method' => 'course', 'customer_course_id' => $locked->id],
                    'source' => $source,
                    'customer_course_id' => $locked->id,
                    'course_session_number' => $locked->sessions_used + $reserved + 1,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                if ($this->bookingService->isDuplicateActiveSlotError($e)) {
                    throw BookingNotAvailableException::slotTaken('Course session slot taken concurrently.', ['course_id' => $locked->id]);
                }
                throw $e;
            }
        });

        // همان پیامک/اعلان نوبت پرداخت‌شده‌ی عادی (تأیید یا «منتظر تایید» به مشتری، نوبت تازه به متخصص)
        app(\App\Observers\Booking\BookingObserver::class)->notifyPaidBooking($booking->load(['service', 'specialist', 'user']));

        return $booking;
    }

    private function specialistOffersService(int $specialistId, int $serviceId): bool
    {
        return DB::table('specialist_services')->where('specialist_id', $specialistId)->where('beauty_service_id', $serviceId)->exists();
    }

    // ─── انجام و لغو جلسه (از BookingObserver) ───────────────────────

    public function onBookingCompleted(Booking $booking): void
    {
        if (! $booking->customer_course_id) {
            return;
        }

        DB::transaction(function () use ($booking) {
            $locked = CustomerCourse::withoutGlobalScopes()->lockForUpdate()->find($booking->customer_course_id);
            // یک بار برای هر نوبت: course_income_at روی خود نوبت با update شرطی
            $claimed = Booking::withoutGlobalScopes()->whereKey($booking->id)->whereNull('course_income_at')
                ->toBase()->update(['course_income_at' => now()]);
            if (! $locked || $claimed !== 1) {
                return;
            }

            $locked->sessions_used = min($locked->sessions_total, $locked->sessions_used + 1);
            $locked->last_session_at = $booking->booking_time;
            $locked->next_due_at = $locked->sessions_used < $locked->sessions_total
                ? Carbon::parse($booking->booking_time)->addDays($locked->interval_days)
                : null;
            if ($locked->sessions_used >= $locked->sessions_total) {
                $locked->status = 'completed';
            }
            $locked->save();

            $this->creditSpecialist($booking, $locked);
        });
    }

    /** درآمد متخصص از یک جلسه = ارزش جلسه × سهم پرداخت آنلاین دوره؛ حضوری مثل نوبت حضوری امروز درآمد کیف پول ندارد */
    private function creditSpecialist(Booking $booking, CustomerCourse $course): void
    {
        $specialist = \App\Models\Specialist::withoutGlobalScopes()->find($booking->specialist_id);
        if (! $specialist || $course->price_total <= 0) {
            return;
        }

        $share = min(1.0, $course->onlinePaid() / $course->price_total);
        $value = round($course->per_session_value * $share, 2);
        if ($value <= 0) {
            return;
        }

        app(CurrentSalon::class)->withSalon((int) $booking->salon_id, function () use ($specialist, $booking, $course, $value) {
            $commission = round($value * $specialist->getEffectiveCommissionRate() / 100, 2);
            $specialist->getOrCreateWallet()->addIncome($value - $commission, $booking->id,
                "درآمد جلسه‌ی {$booking->course_session_number} دوره‌ی «{$course->name}» — نوبت #{$booking->id}");
            AdminWallet::getWallet()->addCommission($commission, $booking->id, "کمیسیون جلسه‌ی دوره — نوبت #{$booking->id}");
        });
    }

    public function onBookingCancelled(Booking $booking): void
    {
        if (! $booking->customer_course_id || $booking->course_session_burned) {
            return;
        }

        if (! $this->isLateCustomerCancellation($booking)) {
            return; // جلسه برمی‌گردد: نوبت لغوشده دیگر «رزروشده» شمرده نمی‌شود
        }

        $this->burnSession($booking);
    }

    /** «نیامد» (ادامه ۷): جلسه‌ی دوره همیشه می‌سوزد، بدون درآمد متخصص (مثل لغو دیرهنگام) */
    public function onBookingNoShow(Booking $booking): void
    {
        if ($booking->customer_course_id && ! $booking->course_session_burned) {
            $this->burnSession($booking);
        }
    }

    /** یک بار برای هر نوبت (claim اتمی روی course_session_burned)، زیر قفل دوره */
    private function burnSession(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $burned = Booking::withoutGlobalScopes()->whereKey($booking->id)->where('course_session_burned', false)
                ->toBase()->update(['course_session_burned' => true]);
            $locked = CustomerCourse::withoutGlobalScopes()->lockForUpdate()->find($booking->customer_course_id);
            if ($burned !== 1 || ! $locked) {
                return;
            }

            $locked->sessions_used = min($locked->sessions_total, $locked->sessions_used + 1);
            $locked->sessions_burned++;
            if ($locked->sessions_used >= $locked->sessions_total) {
                $locked->status = 'completed';
                $locked->next_due_at = null;
            }
            $locked->save();
        });
    }

    private function isLateCustomerCancellation(Booking $booking): bool
    {
        if (! in_array($booking->cancelled_by, ['customer', 'user'], true)) {
            return false;
        }
        if (! CourseSetting::forSalon((int) $booking->salon_id)->burn_late_cancellation) {
            return false;
        }

        $hours = (int) app(CurrentSalon::class)->withSalon((int) $booking->salon_id, fn () => WalletSetting::get()->cancellation_before_hours);
        $cancelledAt = $booking->cancelled_at ?? now();

        return $cancelledAt->gt(Carbon::parse($booking->booking_time)->subHours(max(0, $hours)));
    }

    // ─── کارهای مدیر ──────────────────────────────────────────────

    /** مبلغ برگشت پیشنهادی: پرداخت‌شده − جلسه‌های مصرف‌شده × ارزش هر جلسه */
    public function refundableAmount(CustomerCourse $course): float
    {
        if (in_array($course->status, ['cancelled', 'completed'], true)) {
            return 0.0;
        }

        return max(0.0, round($course->amount_paid - $course->sessions_used * $course->per_session_value - $course->refunded_amount, 2));
    }

    /**
     * لغو دوره توسط مدیر: نوبت‌های آینده لغو (با پیامک لغو عادی)، برگشت به کیف پول مشتری (تصمیم ۱۰)، حداکثر مبلغ پیشنهادی.
     *
     * @throws CourseException
     */
    public function cancel(CustomerCourse $course, float $refund, string $reason, User $by): CustomerCourse
    {
        $bookings = DB::transaction(function () use ($course, $refund, $reason, $by) {
            $locked = CustomerCourse::withoutGlobalScopes()->lockForUpdate()->findOrFail($course->id);
            if (in_array($locked->status, ['cancelled', 'completed'], true)) {
                throw new CourseException('این دوره قبلاً بسته شده است.');
            }

            $max = $this->refundableAmount($locked);
            $refund = round(max(0.0, $refund), 2);
            if ($refund > $max + 0.5) {
                throw new CourseException(sprintf('حداکثر مبلغ قابل برگشت %s تومان است.', number_format($max)));
            }

            if ($refund > 0) {
                $customer = User::find($locked->user_id);
                $wallet = $customer?->getOrCreateWallet();
                if (! $wallet) {
                    throw new CourseException('مشتری این دوره دیگر وجود ندارد؛ برگشت پول ممکن نیست.');
                }
                $wallet = UserWallet::query()->lockForUpdate()->findOrFail($wallet->id);
                $wallet->addRefund($refund, null, "برگشت وجه لغو دوره‌ی «{$locked->name}»", ['customer_course_id' => $locked->id]);
            }

            $locked->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $by->id,
                'cancel_reason' => $reason,
                'refunded_amount' => $locked->refunded_amount + $refund,
                'next_due_at' => null,
            ]);

            activity()->performedOn($locked)->causedBy($by)->withProperties(['refund' => $refund, 'reason' => $reason])->log('course_cancelled');

            return Booking::withoutGlobalScopes()->where('customer_course_id', $locked->id)
                ->whereIn('status', CustomerCourse::ACTIVE_BOOKING_STATUSES)->get();
        });

        foreach ($bookings as $booking) {
            $booking->update(['status' => 'cancelled', 'cancelled_by' => 'admin', 'cancelled_at' => now(), 'cancellation_reason' => 'لغو دوره: '.$reason]);
        }

        return $course->fresh();
    }

    public function extend(CustomerCourse $course, Carbon $expiresAt, User $by): CustomerCourse
    {
        return DB::transaction(function () use ($course, $expiresAt, $by) {
            $locked = CustomerCourse::withoutGlobalScopes()->lockForUpdate()->findOrFail($course->id);
            if (in_array($locked->status, ['cancelled', 'completed', 'pending_payment'], true)) {
                throw new CourseException('مهلت این دوره قابل تمدید نیست.');
            }

            $from = $locked->expires_at;
            $locked->update(['expires_at' => $expiresAt->endOfDay(), 'status' => $locked->status === 'expired' ? 'active' : $locked->status]);
            activity()->performedOn($locked)->causedBy($by)
                ->withProperties(['from' => $from?->toDateString(), 'to' => $expiresAt->toDateString()])->log('course_extended');

            return $locked;
        });
    }

    public function recordConsent(CustomerCourse $course, User $by): void
    {
        if ($course->photo_consent_at) {
            return;
        }

        $course->update(['photo_consent_at' => now(), 'photo_consent_by' => $by->id]);
        activity()->performedOn($course)->causedBy($by)->log('course_photo_consent');
    }

    /** دوره‌های فعال با مهلت گذشته ← expired؛ نوبت‌های رزروشده‌ی پیش از انقضا انجام‌شدنی می‌مانند */
    public function expireDue(): int
    {
        return CustomerCourse::withoutGlobalScopes()
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired', 'updated_at' => now()]);
    }

    /** دوره‌ی خریده‌نشده که ۳۰ دقیقه پرداخت نشد ← لغو (مثل CancelUnpaidBookings) */
    public function abandonUnpaid(int $minutes = 30): int
    {
        $count = 0;
        CustomerCourse::withoutGlobalScopes()->where('status', 'pending_payment')->where('amount_paid', 0)
            ->where('created_at', '<', now()->subMinutes($minutes))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('payment_transactions')
                ->where('payment_transactions.purpose', 'course')
                ->whereColumn('payment_transactions.payable_id', 'customer_courses.id')
                ->where('payment_transactions.status', 'pending')
                ->where('payment_transactions.created_at', '>', now()->subMinutes(\App\Models\PaymentTransaction::PENDING_LIFETIME_MINUTES)))
            ->orderBy('id')
            ->each(function (CustomerCourse $course) use (&$count) {
                if ($this->abandon($course, 'پرداخت در مهلت انجام نشد')) {
                    $count++;
                }
            });

        return $count;
    }
}
