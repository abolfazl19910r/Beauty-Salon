<?php

namespace App\Support\Bookings;

use App\Models\Booking;

/**
 * تنها منبع برچسب فارسی وضعیت نوبت و پرداخت (بسته‌ی ۰ به‌روزرسانی ظاهری، تصمیم ۲۰۲۶-۱۱-۲۴).
 *
 * پیش‌تر هر view آرایه‌ی خودش را داشت و `pending_payment` در فهرست نوبت‌های مدیر خام انگلیسی دیده می‌شد. viewها، خروجی‌ها و
 * API همه از همین کلاس می‌خوانند؛ کلید ناشناخته هم به‌جای متن خام «نامشخص» می‌گیرد.
 *
 * tone یکی از ok|warn|bad|info|muted است و به توکن‌های --m-* پالت (rgb(var(--m-ok)) و…) نگاشت می‌شود.
 */
final class BookingLabels
{
    public const AUDIENCE_STAFF = 'staff';

    public const AUDIENCE_CUSTOMER = 'customer';

    /** ترتیب همین آرایه ترتیب گزینه‌های فیلتر وضعیت است */
    private const STATUSES = [
        'pending_payment' => ['منتظر پرداخت', 'warn'],
        'pending' => ['منتظر تأیید', 'warn'],
        'confirmed' => ['تأییدشده', 'ok'],
        'completed' => ['انجام‌شده', 'info'],
        'cancelled' => ['لغوشده', 'bad'],
        'no_show' => ['نیامد', 'bad'],
    ];

    private const CUSTOMER_STATUSES = [
        'pending' => 'منتظر تأیید سالن',
    ];

    private const PAYMENTS = [
        'paid' => ['پرداخت‌شده', 'ok'],
        'prepaid' => ['پیش‌پرداخت شده', 'ok'],
        'course' => ['پرداخت با دوره', 'ok'],
        'unpaid' => ['پرداخت‌نشده', 'bad'],
        'at_salon' => ['پرداخت در سالن', 'info'],
    ];

    public static function status(?string $status, string $audience = self::AUDIENCE_STAFF): string
    {
        if ($audience === self::AUDIENCE_CUSTOMER && isset(self::CUSTOMER_STATUSES[$status])) {
            return self::CUSTOMER_STATUSES[$status];
        }

        return self::STATUSES[$status][0] ?? 'نامشخص';
    }

    public static function statusTone(?string $status): string
    {
        return self::STATUSES[$status][1] ?? 'muted';
    }

    /** @return array<string, string> کلید ← برچسب، برای فیلتر و select */
    public static function statusOptions(string $audience = self::AUDIENCE_STAFF): array
    {
        $options = [];
        foreach (array_keys(self::STATUSES) as $key) {
            $options[$key] = self::status($key, $audience);
        }

        return $options;
    }

    /**
     * کلید پرداخت نمایشی نوبت:
     * paid (کامل) · prepaid (پیش‌پرداخت شده، مانده در سالن) · course (جلسه‌ی دوره) · at_salon (بی پیش‌پرداخت) · unpaid.
     */
    public static function paymentKey(Booking $booking): string
    {
        if ($booking->customer_course_id !== null) {
            return 'course';
        }

        if ($booking->payment_status === 'paid') {
            return $booking->remaining_amount > 0 ? 'prepaid' : 'paid';
        }

        return $booking->prepayment_required ? 'unpaid' : 'at_salon';
    }

    public static function payment(Booking $booking): string
    {
        return self::PAYMENTS[self::paymentKey($booking)][0];
    }

    public static function paymentTone(Booking $booking): string
    {
        return self::PAYMENTS[self::paymentKey($booking)][1];
    }

    /** برچسب خام `payment_status` (paid/unpaid) جایی که خود نوبت در دست نیست، مثل گزارش‌های تجمیعی */
    public static function paymentStatus(?string $paymentStatus): string
    {
        return match ($paymentStatus) {
            'paid' => self::PAYMENTS['paid'][0],
            'unpaid' => self::PAYMENTS['unpaid'][0],
            default => 'نامشخص',
        };
    }

    /** متغیر CSS پالت برای tone («--m-ok» …) */
    public static function toneVar(string $tone): string
    {
        return in_array($tone, ['ok', 'warn', 'bad', 'info'], true) ? '--m-'.$tone : '--m-mu';
    }

    /** سبک درون‌خطی قرص برچسب با توکن‌های پالت: پس‌زمینه‌ی کم‌رنگ و متن پررنگ */
    public static function pillStyle(string $tone): string
    {
        $var = self::toneVar($tone);

        return "background:rgb(var({$var}) / .12); color:rgb(var({$var}));";
    }

    /**
     * همه‌ی چیزهای نمایش یک وضعیت نوبت، با کلید عددی (۰ برچسب، ۱ پس‌زمینه، ۲ رنگ) و نام‌دار، تا viewهای قدیمی‌تر با همان
     * دسترسی `$x[0]` / `$x['label']` بی بازنویسی markup به همین منبع وصل شوند.
     *
     * @return array<int|string, string>
     */
    public static function statusPill(?string $status, string $audience = self::AUDIENCE_STAFF): array
    {
        $tone = self::statusTone($status);
        $var = self::toneVar($tone);
        $label = self::status($status, $audience);

        return [
            $label, "rgb(var({$var}) / .1)", "rgb(var({$var}))",
            'label' => $label, 'bg' => "rgb(var({$var}) / .1)", 'color' => "rgb(var({$var}))", 'border' => "rgb(var({$var}) / .35)",
            'class' => self::pillClass($tone), 'tone' => $tone,
        ];
    }

    /** @return array<string, array<int|string, string>> همه‌ی وضعیت‌ها ← statusPill() */
    public static function statusPills(string $audience = self::AUDIENCE_STAFF): array
    {
        $pills = [];
        foreach (array_keys(self::STATUSES) as $key) {
            $pills[$key] = self::statusPill($key, $audience);
        }

        return $pills;
    }

    /** @return array{label: string, class: string, tone: string} برچسب پرداخت با کلاس رنگ متن (پنل کارشناس) */
    public static function paymentInfo(Booking $booking): array
    {
        $tone = self::paymentTone($booking);

        return ['label' => self::payment($booking), 'class' => match ($tone) {
            'ok' => 'text-m-ok', 'warn' => 'text-m-warn', 'bad' => 'text-m-bad', 'info' => 'text-m-info', default => 'text-m-mu',
        }, 'tone' => $tone];
    }

    /** کلاس Tailwind همان قرص (پنل کارشناس و مشتری که با کلاس کار می‌کنند) */
    public static function pillClass(string $tone): string
    {
        return match ($tone) {
            'ok' => 'bg-m-ok/10 text-m-ok',
            'warn' => 'bg-m-warn/10 text-m-warn',
            'bad' => 'bg-m-bad/10 text-m-bad',
            'info' => 'bg-m-info/10 text-m-info',
            default => 'bg-m-tx/5 text-m-mu',
        };
    }

    /** کلاس قرص PDF (dompdf متغیر CSS ندارد؛ partials/pdf-report-style) */
    public static function pdfPill(string $tone): string
    {
        return match ($tone) {
            'ok' => 'pill-good',
            'warn' => 'pill-warn',
            'bad' => 'pill-bad',
            'info' => 'pill-info',
            default => 'pill-mute',
        };
    }
}
