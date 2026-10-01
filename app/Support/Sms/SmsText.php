<?php

namespace App\Support\Sms;

use App\Models\Booking;
use App\Models\Salon;
use App\Services\Links\ShortLinkService;

/**
 * همه‌ی متن‌های پیامک سالن در یک جا (تصمیم‌های ۲۰۲۶-۰۹-۳۰): بدون ایموجی و بدون خط‌های عنوان‌دار؛ هر جزئیات فقط در پیامکی که
 * به کارش مربوط است؛ نام کوتاه پیامکی سالن و خدمت (smsName). هدف: بیشتر پیامک‌ها ۱ قطعه (۷۰ نویسه)، ثبت نوبت، نوبت جدید
 * نیازمند تایید و تایید نوبت (با لینک آدرس) ۲ قطعه. اندازه‌ی هر متن در SmsTextTest نگه داشته می‌شود.
 */
final class SmsText
{
    // ─── نوبت، به مشتری ───────────────────────────────────────────────

    /** ثبت و پرداخت شد، منتظر تایید متخصص — رسید مشتری، عمداً ۲ قطعه با کل و مانده */
    public static function bookingPending(Booking $b): string
    {
        return sprintf('نوبت %s ساعت %s ثبت شد، منتظر تایید. کل %s، مانده %s تومان. %s',
            self::date($b), self::time($b), self::money($b->service?->price), self::money($b->remaining_amount), self::salon($b));
    }

    /** تایید شد — مانده + لینک صفحه‌ی سالن (آدرس و نقشه) */
    public static function bookingConfirmed(Booking $b): string
    {
        return trim(sprintf('نوبت %s ساعت %s تایید شد. مانده %s تومان. %s %s',
            self::date($b), self::time($b), self::money($b->remaining_amount), self::salon($b), self::salonLink($b)));
    }

    public static function bookingCompleted(Booking $b): string
    {
        return sprintf('از انتخاب %s سپاسگزاریم.', self::salon($b));
    }

    /** لغو — مبلغ بازگشتی و جریمه اگر هست، و دلیل */
    public static function bookingCancelledForCustomer(Booking $b, ?string $reason = null, float $refunded = 0, float $fee = 0): string
    {
        $text = sprintf('نوبت %s ساعت %s لغو شد.', self::date($b), self::time($b));
        if ($refunded > 0) {
            $text .= sprintf(' بازگشت %s تومان%s.', self::money($refunded), $fee > 0 ? ' (جریمه '.self::money($fee).')' : '');
        }
        if ($reason = self::clip($reason, 30)) {
            $text .= ' دلیل: '.$reason.'.';
        }

        return $text.' '.self::salon($b);
    }

    public static function bookingCancelledUnpaid(Booking $b): string
    {
        return sprintf('نوبت %s ساعت %s به دلیل عدم پرداخت لغو شد. %s', self::date($b), self::time($b), self::salon($b));
    }

    public static function reminderForCustomer(Booking $b): string
    {
        return sprintf('نوبت امروز ساعت %s در %s؛ ۱۵ دقیقه زودتر بیایید.', self::time($b), self::salon($b));
    }

    public static function rescheduledForCustomer(Booking $b, bool $awaitingApproval = false): string
    {
        return sprintf('نوبت شما به %s ساعت %s تغییر کرد%s. %s',
            self::date($b), self::time($b), $awaitingApproval ? '، منتظر تایید' : '', self::salon($b));
    }

    public static function reviewRequest(Booking $b, string $link): string
    {
        return sprintf('نظر شما درباره %s مهم است: %s', self::salon($b), $link);
    }

    // ─── نوبت، به متخصص ───────────────────────────────────────────────

    /** نوبت جدید — مانده (چقدر از مشتری بگیرد)؛ اگر تایید لازم است، لینک کوتاه تایید (۲ قطعه) */
    public static function newBookingForSpecialist(Booking $b, bool $needsApproval): string
    {
        if ($needsApproval) {
            $link = app(ShortLinkService::class)->shorten(
                route('specialist.bookings.show', ['booking' => $b->id]),
                $b->booking_time?->copy()->addDay()
            );

            return sprintf('نوبت %s %s %s، مانده %s تومان. تایید: %s',
                self::customer($b), self::date($b), self::time($b), self::money($b->remaining_amount), $link);
        }

        return sprintf('نوبت جدید: %s، %s %s، مانده %s تومان', self::customer($b), self::date($b), self::time($b), self::money($b->remaining_amount));
    }

    public static function reminderForSpecialist(Booking $b): string
    {
        return sprintf('یادآوری: %s، %s، ساعت %s، %s', self::customer($b), self::service($b), self::time($b), $b->user?->phone);
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
        $text = sprintf('لغو نوبت%s: %s، %s ساعت %s', $by, self::customer($b), self::date($b), self::time($b));

        return $penalty > 0 ? $text.sprintf('. جریمه %s تومان کسر شد', self::money($penalty)) : $text;
    }

    public static function rescheduledForSpecialist(Booking $b, \DateTimeInterface $old): string
    {
        return sprintf('تغییر نوبت %s: از %s %s به %s %s',
            self::customer($b), verta($old)->format('Y/m/d'), verta($old)->format('H:i'), self::date($b), self::time($b));
    }

    // ─── مدیر، نظر، کیف پول، مرخصی، وفاداری، تخفیف ──────────────────────

    public static function paymentReceived(Booking $b, float $amount, string $method): string
    {
        return sprintf('پرداخت %s تومان (%s) از %s، نوبت %s ساعت %s',
            self::money($amount), $method, self::customer($b), self::date($b), self::time($b));
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
        return trim(sprintf('%s امتیاز گرفتید؛ موجودی: %s. %s', number_format($points), number_format($total), $salon?->smsName()));
    }

    public static function rewardRedeemed(string $code, string $reward, ?\DateTimeInterface $expires, ?Salon $salon): string
    {
        return trim(sprintf('کد %s برای «%s»%s. %s', $code, self::clip($reward, 20),
            $expires ? '، تا '.verta($expires)->format('Y/m/d') : '', $salon?->smsName()));
    }

    public static function discountCodeIssued(string $code, string $value, ?\DateTimeInterface $expires, ?Salon $salon): string
    {
        return trim(sprintf('کد تخفیف %s: %s%s. %s', $code, $value,
            $expires ? '، تا '.verta($expires)->format('Y/m/d') : '', $salon?->smsName()));
    }

    public static function discountCodeUsedUp(string $code, ?Salon $salon): string
    {
        return trim(sprintf('کد تخفیف %s تمام شد. %s', $code, $salon?->smsName()));
    }

    public static function quotaExhausted(Salon $salon): string
    {
        return sprintf('پیامک این ماه %s تمام شد؛ برای ادامه از صورتحساب بسته بخرید.', $salon->smsName());
    }

    // ─── کمکی ──────────────────────────────────────────────────────────

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
