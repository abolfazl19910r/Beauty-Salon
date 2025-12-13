<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\User;
use App\Models\LoyaltyPoint;
use App\Services\ReportCacheService;
use App\Services\SMSService;
use Illuminate\Support\Facades\Log;

class BookingObserver
{
    protected ReportCacheService $cacheService;
    protected SMSService $smsService;

    public function __construct(ReportCacheService $cacheService, SMSService $smsService)
    {
        $this->cacheService = $cacheService;
        $this->smsService = $smsService;
    }

    public function created(Booking $booking): void
    {
        if ($booking->status !== 'pending_payment') {
            $this->sendBookingConfirmation($booking);
        }

        $this->cacheService->flush();
    }

    public function updated(Booking $booking): void
    {
        if ($booking->wasChanged('payment_status') && $booking->payment_status === 'paid') {
            if (method_exists(User::class, 'addLoyaltyPoints')) {
                $this->addLoyaltyPoints($booking);
            }

            if ($booking->wasChanged('status') && $booking->status === 'confirmed') {
                $this->sendBookingConfirmation($booking);
            }
        }

        if ($booking->wasChanged('status') && $booking->status === 'cancelled') {
            $this->handleCancellation($booking);
        }

        $this->cacheService->flush();
    }

    public function deleted(Booking $booking): void
    {
        $this->cacheService->flush();
    }

    protected function sendBookingConfirmation(Booking $booking): void
    {
        try {
            if ($booking->user && $booking->user->phone) {
                $message = sprintf(
                    "رزرو شما با موفقیت ثبت شد\n" .
                    "خدمت: %s\n" .
                    "متخصص: %s\n" .
                    "تاریخ: %s\n" .
                    "ساعت: %s\n" .
                    "کد پیگیری: #%s",
                    $booking->service->name,
                    $booking->specialist->name,
                    verta($booking->booking_time)->format('Y/m/d'),
                    verta($booking->booking_time)->format('H:i'),
                    $booking->id
                );

                $this->smsService->send($booking->user->phone, $message);
            }
        } catch (\Exception $e) {
            Log::error('خطا در ارسال پیامک تایید رزرو', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function handleCancellation(Booking $booking): void
    {
        try {
            $cancelledBy = $booking->cancelled_by ?? 'unknown';

            Log::info('نوبت لغو شد', [
                'booking_id' => $booking->id,
                'cancelled_by' => $cancelledBy,
                'cancellation_reason' => $booking->cancellation_reason
            ]);

        } catch (\Exception $e) {
            Log::error('خطا در مدیریت لغو نوبت', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function addLoyaltyPoints(Booking $booking): void
    {
        try {
            $user = User::find($booking->user_id);

            if (!$user || !method_exists($user, 'addLoyaltyPoints')) {
                return;
            }

            $basePoints = 5;

            $amountPoints = floor($booking->prepayment_amount / 10000);

            $totalPoints = $basePoints + $amountPoints;

            $user->addLoyaltyPoints(
                $totalPoints,
                "رزرو خدمت {$booking->service->name}"
            );

            if ($user->phone) {
                $currentPoints = LoyaltyPoint::where('user_id', $user->id)->sum('points');

                $message = sprintf(
                    "%d امتیاز به حساب کاربری شما اضافه شد. موجودی فعلی: %d امتیاز",
                    $totalPoints,
                    $currentPoints
                );

                $this->smsService->send($user->phone, $message);
            }
        } catch (\Exception $e) {
            Log::error('خطا در اضافه کردن امتیاز وفاداری', [
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
