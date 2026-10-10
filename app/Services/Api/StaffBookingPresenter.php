<?php

namespace App\Services\Api;

use App\Models\Booking;
use Morilog\Jalali\Jalalian;

/**
 * شکل نوبت در API اپ «ماهرو همکار» (بسته‌ی ۲). زمان‌ها ISO 8601 (برای منطق اپ) و شمسی (برای نمایش).
 * `actions` همان قاعده‌های SpecialistBookingActions است تا اپ دکمه‌ی بی‌اثر نشان ندهد.
 */
class StaffBookingPresenter
{
    public const STATUS_LABELS = [
        'pending' => 'در انتظار تأیید',
        'confirmed' => 'تأیید شده',
        'completed' => 'انجام شده',
        'cancelled' => 'لغو شده',
        'pending_payment' => 'در انتظار پرداخت',
    ];

    public static function present(Booking $booking): array
    {
        $time = $booking->booking_time;

        return [
            'id' => $booking->id,
            'status' => $booking->status,
            'status_label' => self::STATUS_LABELS[$booking->status] ?? $booking->status,
            'payment_status' => $booking->payment_status,
            'booking_time' => $time?->toIso8601String(),
            'booking_time_jalali' => $time ? Jalalian::fromCarbon($time)->format('Y/m/d H:i') : null,
            'duration_minutes' => $booking->service?->duration !== null ? (int) $booking->service->duration : null,
            'service' => $booking->service ? [
                'id' => $booking->service->id,
                'name' => $booking->service->name,
                'price' => (float) $booking->service->price,
            ] : null,
            'customer' => $booking->user ? [
                'id' => $booking->user->id,
                'name' => $booking->user->name,
                'phone' => $booking->user->phone,
            ] : null,
            'prepayment_amount' => (float) $booking->prepayment_amount,
            'discount_amount' => (float) $booking->discount_amount,
            'remaining_amount' => $booking->remaining_amount,
            'source' => $booking->source,
            'notes' => $booking->notes,
            'cancellation_reason' => $booking->cancellation_reason,
            'cancelled_by' => $booking->cancelled_by,
            'actions' => [
                'confirm' => $booking->status === 'pending',
                'cancel' => ! in_array($booking->status, ['cancelled', 'completed'], true),
                'complete' => $booking->status === 'confirmed',
            ],
        ];
    }
}
