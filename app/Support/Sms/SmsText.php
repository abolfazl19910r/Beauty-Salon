<?php

namespace App\Support\Sms;

use App\Models\Booking;
use App\Models\Salon;
use App\Services\Links\ShortLinkService;

/**
 * همه‌ی متن‌های پیامک سالن در یک جا (تصمیم‌های ۲۰۲۶-۰۹-۳۰): بدون ایموجی و بدون خط‌های عنوان‌دار؛ هر جزئیات فقط در پیامکی که
 * به کارش مربوط است؛ نام کوتاه پیامکی سالن و خدمت (smsName). هدف: بیشتر پیامک‌ها ۱ قطعه (۷۰ نویسه)، ثبت نوبت، نوبت جدید
 * نیازمند تایید و تایید نوبت (با لینک آدرس) ۲ قطعه. اندازه‌ی هر متن در SmsTextTest نگه داشته می‌شود.
 *
 * از ۲۰۲۶-۱۰-۱۴ متن‌های نوبت، باشگاه و تخفیف از SmsTemplates/SmsTemplateStore می‌آیند: هر سالن می‌تواند متن خودش را بگذارد؛
 * متن پیش‌فرض هر کلید دقیقاً همان خروجی قبلی این کلاس است. پیامک‌های کیف پول، مرخصی، نظر و سهمیه ثابت می‌مانند.
 */
final class SmsText
{
    // ─── نوبت، به مشتری ───────────────────────────────────────────────

    /** ثبت و پرداخت شد، منتظر تایید متخصص — رسید مشتری، عمداً ۲ قطعه با کل و مانده */
    public static function bookingPending(Booking $b): string
    {
        return self::render($b, 'booking_pending', [
            'کل' => self::money($b->service?->price), 'مانده' => self::money($b->remaining_amount),
        ]);
    }

    /** تایید شد — مانده + لینک صفحه‌ی سالن (آدرس و نقشه) */
    public static function bookingConfirmed(Booking $b): string
    {
        return self::render($b, 'booking_confirmed', [
            'مانده' => self::money($b->remaining_amount), 'لینک' => fn () => self::salonLink($b),
        ]);
    }

    public static function bookingCompleted(Booking $b): string
    {
        return self::render($b, 'booking_completed');
    }

    /** لغو — مبلغ بازگشتی و جریمه اگر هست، و دلیل */
    public static function bookingCancelledForCustomer(Booking $b, ?string $reason = null, float $refunded = 0, float $fee = 0): string
    {
        return self::render($b, 'booking_cancelled_customer', [
            'بازگشت' => $refunded > 0 ? sprintf('بازگشت %s تومان%s.', self::money($refunded), $fee > 0 ? ' (جریمه '.self::money($fee).')' : '') : '',
            'دلیل' => ($reason = self::clip($reason, 30)) ? 'دلیل: '.$reason.'.' : '',
        ]);
    }

    public static function bookingCancelledUnpaid(Booking $b): string
    {
        return self::render($b, 'booking_cancelled_unpaid');
    }

    public static function reminderForCustomer(Booking $b): string
    {
        return self::render($b, 'reminder_customer');
    }

    public static function rescheduledForCustomer(Booking $b, bool $awaitingApproval = false): string
    {
        return self::render($b, 'rescheduled_customer', ['منتظر_تایید' => $awaitingApproval ? '، منتظر تایید' : '']);
    }

    public static function reviewRequest(Booking $b, string $link): string
    {
        return self::render($b, 'review_request', ['لینک' => $link]);
    }

    // ─── نوبت، به متخصص ───────────────────────────────────────────────

    /** نوبت جدید — مانده (چقدر از مشتری بگیرد)؛ اگر تایید لازم است، لینک کوتاه تایید (۲ قطعه) */
    public static function newBookingForSpecialist(Booking $b, bool $needsApproval): string
    {
        if ($needsApproval) {
            return self::render($b, 'new_booking_specialist_approval', [
                'مانده' => self::money($b->remaining_amount),
                'لینک' => fn () => app(ShortLinkService::class)->shorten(
                    route('specialist.bookings.show', ['booking' => $b->id]),
                    $b->booking_time?->copy()->addDay()
                ),
            ]);
        }

        return self::render($b, 'new_booking_specialist', ['مانده' => self::money($b->remaining_amount)]);
    }

    public static function reminderForSpecialist(Booking $b): string
    {
        return self::render($b, 'reminder_specialist', ['موبایل_مشتری' => (string) $b->user?->phone]);
    }

    public static function cancelledForSpecialist(Booking $b, ?string $cancelledBy = null, float $penalty = 0): string
    {
        $by = match ($cancelledBy) {
            'user', 'customer' => ' توسط مشتری',
            'admin' => ' توسط مدیر',
            'specialist' => ' توسط شما',
            'system' => ' (عدم پرداخت)',
            default => '',
        };

        return self::render($b, 'cancelled_specialist', [
            'لغوکننده' => $by,
            'جریمه' => $penalty > 0 ? sprintf('. جریمه %s تومان کسر شد', self::money($penalty)) : '',
        ]);
    }

    public static function rescheduledForSpecialist(Booking $b, \DateTimeInterface $old): string
    {
        return self::render($b, 'rescheduled_specialist', ['تاریخ_قبلی' => verta($old)->format('Y/m/d'), 'ساعت_قبلی' => verta($old)->format('H:i')]);
    }

    // ─── مدیر، نظر، کیف پول، مرخصی، وفاداری، تخفیف ──────────────────────

    public static function paymentReceived(Booking $b, float $amount, string $method): string
    {
        return self::render($b, 'payment_received', ['مبلغ' => self::money($amount), 'روش' => $method]);
    }

    public static function newReview(string $customer, string $service, int $rating, ?string $comment): string
    {
        $text = sprintf('نظر جدید %s از ۵: %s، %s', $rating, self::clip($customer, 20), self::clip($service, 20));
        if ($comment = self::clip($comment, 30)) {
            $text .= ': '.$comment;
        }

        return $text;
    }

    public static function withdrawalApproved(float $amount, string $reference): string
    {
        return sprintf('برداشت %s تومان واریز شد. پیگیری: %s', self::money($amount), $reference);
    }

    public static function withdrawalRejected(float $amount, ?string $reason): string
    {
        $text = sprintf('برداشت %s تومان رد شد و به کیف پول برگشت.', self::money($amount));

        return ($reason = self::clip($reason, 30)) ? $text.' دلیل: '.$reason : $text;
    }

    public static function leaveDecided(\DateTimeInterface $from, \DateTimeInterface $to, bool $approved, ?string $reason): string
    {
        $text = sprintf('مرخصی %s تا %s %s شد.', verta($from)->format('Y/m/d'), verta($to)->format('Y/m/d'), $approved ? 'تایید' : 'رد');

        return (! $approved && ($reason = self::clip($reason, 30))) ? $text.' دلیل: '.$reason : $text;
    }

    public static function pointsEarned(int $points, int $total, ?Salon $salon): string
    {
        return SmsTemplateStore::render($salon?->id, 'points_earned', [
            'امتیاز' => number_format($points), 'موجودی' => number_format($total), 'سالن' => (string) $salon?->smsName(),
        ]);
    }

    public static function rewardRedeemed(string $code, string $reward, ?\DateTimeInterface $expires, ?Salon $salon): string
    {
        return SmsTemplateStore::render($salon?->id, 'reward_redeemed', [
            'کد' => $code, 'پاداش' => (string) self::clip($reward, 20), 'انقضا' => self::until($expires), 'سالن' => (string) $salon?->smsName(),
        ]);
    }

    public static function discountCodeIssued(string $code, string $value, ?\DateTimeInterface $expires, ?Salon $salon): string
    {
        return SmsTemplateStore::render($salon?->id, 'discount_code_issued', [
            'کد' => $code, 'مقدار' => $value, 'انقضا' => self::until($expires), 'سالن' => (string) $salon?->smsName(),
        ]);
    }

    public static function discountCodeUsedUp(string $code, ?Salon $salon): string
    {
        return SmsTemplateStore::render($salon?->id, 'discount_code_used_up', ['کد' => $code, 'سالن' => (string) $salon?->smsName()]);
    }

    // ─── دوره‌ی درمانی (بسته‌ی ب) ───────────────────────────────────────

    /** یادآوری رزرو جلسه‌ی بعد — $link لینک کوتاه رزرو (Closure تا فقط اگر در متن سالن هست ساخته شود) */
    public static function courseNextSession(\App\Models\CustomerCourse $course, int $remaining, string|\Closure $link): string
    {
        return self::renderCourse($course, 'course_next_session', $remaining, $link);
    }

    public static function courseExpiring(\App\Models\CustomerCourse $course, int $remaining, string|\Closure $link): string
    {
        return self::renderCourse($course, 'course_expiring', $remaining, $link);
    }

    private static function renderCourse(\App\Models\CustomerCourse $course, string $key, int $remaining, string|\Closure $link): string
    {
        return SmsTemplateStore::render($course->salon_id, $key, [
            'دوره' => (string) self::clip($course->name, 25),
            'شماره_جلسه' => to_persian_num((string) ($course->sessions_used + 1)),
            'مانده_جلسه' => to_persian_num((string) $remaining),
            'لینک_رزرو' => $link,
            'انقضا' => $course->expires_at ? verta($course->expires_at)->format('Y/m/d') : '',
            'سالن' => fn () => (string) Salon::withoutGlobalScopes()->find($course->salon_id)?->smsName(),
            'مشتری' => fn () => (string) self::clip($course->user?->name, 25),
        ]);
    }

    public static function quotaExhausted(Salon $salon): string
    {
        return sprintf('پیامک این ماه %s تمام شد؛ برای ادامه از صورتحساب بسته بخرید.', $salon->smsName());
    }

    // ─── کمکی ──────────────────────────────────────────────────────────

    /**
     * متن سالنِ خود نوبت (یا پیش‌فرض، SmsTemplates) با جای‌خالی‌های مشترک هر نوبت + $vars. مقدارهای گران (لینک کوتاه) Closure‌اند و
     * فقط اگر در متن سالن آمده باشند ساخته می‌شوند.
     */
    private static function render(Booking $b, string $key, array $vars = []): string
    {
        return SmsTemplateStore::render($b->salon_id, $key, $vars + [
            'تاریخ' => self::date($b),
            'ساعت' => self::time($b),
            'سالن' => fn () => self::salon($b),
            'مشتری' => fn () => (string) self::customer($b),
            'خدمت' => fn () => self::service($b),
            'کل' => fn () => self::money($b->service?->price),
            'مانده' => fn () => self::money($b->remaining_amount),
            'جلسه' => fn () => (string) $b->courseSessionLabel(),
        ]);
    }

    private static function until(?\DateTimeInterface $expires): string
    {
        return $expires ? '، تا '.verta($expires)->format('Y/m/d') : '';
    }

    private static function date(Booking $b): string
    {
        return $b->booking_time ? verta($b->booking_time)->format('Y/m/d') : '';
    }

    private static function time(Booking $b): string
    {
        return $b->booking_time ? verta($b->booking_time)->format('H:i') : '';
    }

    private static function money(mixed $amount): string
    {
        return number_format(max(0, (float) $amount));
    }

    private static function salonModel(Booking $b): ?Salon
    {
        return Salon::withoutGlobalScopes()->find($b->salon_id);
    }

    private static function salon(Booking $b): string
    {
        return (string) self::salonModel($b)?->smsName();
    }

    private static function service(Booking $b): string
    {
        return $b->service?->smsName() ?? '';
    }

    private static function customer(Booking $b): string
    {
        return self::clip($b->user?->name, 25) ?? '';
    }

    /** لینک کوتاه صفحه‌ی عمومی سالن (آدرس و نقشه)، تا یک روز بعد از نوبت */
    private static function salonLink(Booking $b): string
    {
        $salon = self::salonModel($b);
        if (! $salon) {
            return '';
        }

        return app(ShortLinkService::class)->shorten($salon->publicUrl(), $b->booking_time?->copy()->addDay());
    }

    private static function clip(?string $text, int $max): ?string
    {
        $text = trim(preg_replace('/\s+/u', ' ', (string) $text));
        if ($text === '') {
            return null;
        }

        return mb_strlen($text) > $max ? mb_substr($text, 0, $max - 1).'…' : $text;
    }
}
