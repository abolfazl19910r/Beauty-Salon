<?php

namespace App\Services\Api;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Salon;
use App\Models\WalletSetting;
use Illuminate\Support\Carbon;
use Morilog\Jalali\Jalalian;

/**
 * شکل داده‌ها در API اپ «ماهرو» (مشتری، بسته‌ی ۳). زمان‌ها ISO 8601 (منطق اپ) و شمسی (نمایش)؛ مبلغ‌ها تومان.
 * `actions` نوبت همان قاعده‌های وب است (BookingPolicy، Booking::isPayable) تا اپ دکمه‌ی بی‌اثر نشان ندهد.
 */
class CustomerPresenter
{
    /** پنجره‌ی لغو مشتری (BookingPolicy::cancel) — ساعت پیش از نوبت */
    public const CANCEL_DEADLINE_HOURS = 24;

    /** مهلت پرداخت نوبت «در انتظار پرداخت» پیش از لغو خودکار (CancelUnpaidBookings) — دقیقه */
    public const PAYMENT_WINDOW_MINUTES = 30;

    public const STATUS_LABELS = [
        'pending_payment' => 'در انتظار پرداخت',
        'pending' => 'در انتظار تأیید سالن',
        'confirmed' => 'تأیید شده',
        'completed' => 'انجام شده',
        'cancelled' => 'لغو شده', 'no_show' => 'نیامد',
    ];

    /** کارت عمومی سالن — تنها چیزی که پیش از ورود دیده می‌شود (تصمیم ۲۰۲۶-۱۰-۱۱). */
    public static function salonCard(Salon $salon): array
    {
        return [
            'id' => $salon->id,
            'slug' => $salon->slug,
            'name' => $salon->name,
            'tagline' => $salon->tagline,
            'logo_url' => $salon->logoUrl(),
            'public_url' => $salon->publicUrl(),
        ];
    }

    public static function salonDetail(Salon $salon): array
    {
        $settings = WalletSetting::get();

        return self::salonCard($salon) + [
            // امکانات پلن سالن (بسته‌ی پلن‌ها/ب) — اپ بخش‌هایی مثل «دوره‌ها» را فقط با کلیدشان نشان می‌دهد
            'features' => app(\App\Services\Plans\FeatureResolver::class)->enabledKeys($salon),
            'bio' => $salon->bio,
            'address' => $salon->address,
            'phone' => $salon->phone,
            'working_hours' => $salon->workingHoursLines(),
            'experience_years' => $salon->experienceYears(),
            'online_payment' => $salon->acceptsOnlinePayments(),
            // امکانات روشن پلن سالن (بسته‌ی پلن‌ها، ۲۰۲۶-۱۰-۱۴) تا اپ دکمه‌ها را پنهان/قفل کند
            'features' => $salon->enabledFeatureKeys(),
            'rules' => [
                'prepayment_percentage' => (float) $settings->prepayment_percentage,
                'minimum_prepayment' => (float) $settings->minimum_prepayment_amount,
                'payment_window_minutes' => self::PAYMENT_WINDOW_MINUTES,
                'cancel_deadline_hours' => self::CANCEL_DEADLINE_HOURS,
                'cancellation_fee_within_hours' => (int) $settings->cancellation_before_hours,
                'cancellation_fee_percentage' => (float) $settings->customer_cancellation_fee_percentage,
            ],
        ];
    }

    public static function service(BeautyService $service): array
    {
        return [
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'price' => (float) $service->price,
            'duration_minutes' => (int) $service->duration,
            'prepayment_amount' => WalletSetting::get()->calculatePrepaymentAmount((float) $service->price),
            'image_url' => $service->image_url,
            'category' => $service->relationLoaded('category') && $service->category
                ? ['id' => $service->category->id, 'name' => $service->category->name]
                : null,
        ];
    }

    public static function booking(Booking $booking): array
    {
        $time = $booking->booking_time ? Carbon::parse($booking->booking_time) : null;

        return [
            'id' => $booking->id,
            'status' => $booking->status,
            'status_label' => self::STATUS_LABELS[$booking->status] ?? $booking->status,
            'payment_status' => $booking->payment_status,
            'booking_time' => $time?->toIso8601String(),
            'booking_time_jalali' => $time ? Jalalian::fromCarbon($time)->format('Y/m/d H:i') : null,
            'booking_time_label' => $time ? Jalalian::fromCarbon($time)->format('l j F، H:i') : null,
            'duration_minutes' => $booking->duration_minutes ? (int) $booking->duration_minutes : ($booking->service?->duration !== null ? (int) $booking->service->duration : null),
            'service' => $booking->service ? [
                'id' => $booking->service->id,
                'name' => $booking->service->name,
                'price' => (float) $booking->service->price,
            ] : null,
            'specialist' => $booking->specialist ? [
                'id' => $booking->specialist->id,
                'name' => $booking->specialist->name,
                'photo_url' => $booking->specialist->photoUrl(),
            ] : null,
            'prepayment_amount' => (float) $booking->prepayment_amount,
            'discount_code' => $booking->discount_code,
            'discount_amount' => (float) $booking->discount_amount,
            'remaining_amount' => $booking->remaining_amount,
            'paid_at' => $booking->paid_at ? Carbon::parse($booking->paid_at)->toIso8601String() : null,
            'pay_before' => $booking->status === 'pending_payment' && $booking->payment_status !== 'paid'
                ? $booking->created_at?->copy()->addMinutes(self::PAYMENT_WINDOW_MINUTES)->toIso8601String()
                : null,
            'cancellation_reason' => $booking->cancellation_reason,
            'cancelled_by' => $booking->cancelled_by,
            'course' => CoursePresenter::bookingCourse($booking),
            'rating' => $booking->rating !== null ? (int) $booking->rating : null,
            'review' => $booking->review,
            'actions' => [
                'pay' => $booking->isPayable(),
                'cancel' => $booking->status !== 'cancelled' && $booking->status !== 'completed'
                    && $time !== null && $time->gt(now()->addHours(self::CANCEL_DEADLINE_HOURS)),
                'reschedule' => $booking->canBeRescheduled(),
                'rate' => $booking->status === 'completed' && $booking->rating === null,
            ],
        ];
    }

    /** وضعیت ساده‌ی یک تراکنش برای اپ: pending · paid · failed · refunded */
    public static function paymentState(string $status): string
    {
        return match ($status) {
            'paid' => 'paid',
            'pending', 'reconciling' => 'pending',
            'reversing', 'reversed', 'refunded' => 'refunded',
            default => 'failed',
        };
    }
}
