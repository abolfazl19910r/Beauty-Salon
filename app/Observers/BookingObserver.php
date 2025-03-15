<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\User;
use App\Models\LoyaltyPoint;
use App\Services\ReportCacheService;
use App\Services\SMSService;

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
        if ($booking->specialist && $booking->specialist->phone) {
            $message = sprintf(
                'متخصص گرامی، یک نوبت جدید:
مشتری: %s
تاریخ: %s
سرویس: %s
شماره تماس: %s',
                $booking->user->name,
                verta($booking->booking_time)->format('Y/m/d H:i'),
                $booking->service->name,
                $booking->user->phone
            );

            $this->smsService->send($booking->specialist->phone, $message);
        }

        $this->cacheService->flush();
    }

    public function updated(Booking $booking): void
    {
        if ($booking->wasChanged('payment_status') && $booking->payment_status === 'paid') {
            if ($booking->user && $booking->user->phone) {
                $message = sprintf(
                    'نوبت شما با موفقیت ثبت و پرداخت شد:
تاریخ: %s
سرویس: %s
متخصص: %s
مبلغ پیش پرداخت: %s تومان
شماره پیگیری: %s
آدرس: %s',
                    verta($booking->booking_time)->format('Y/m/d H:i'),
                    $booking->service->name,
                    $booking->specialist->name,
                    number_format($booking->prepayment_amount),
                    $booking->payment_ref,
                    config('app.salon_address')
                );

                $this->smsService->send($booking->user->phone, $message);
            }

            if (method_exists(User::class, 'addLoyaltyPoints')) {
                $this->addLoyaltyPoints($booking);
            }
        }

        if ($booking->wasChanged('status')) {
            if ($booking->status === 'cancelled' && $booking->user && $booking->user->phone) {
                $message = sprintf(
                    'نوبت شما در تاریخ %s برای خدمت %s لغو شد',
                    verta($booking->booking_time)->format('Y/m/d H:i'),
                    $booking->service->name
                );

                $this->smsService->send($booking->user->phone, $message);
            }
        }

        if ($booking->wasChanged('booking_time')) {
            if ($booking->user && $booking->user->phone) {
                $message = sprintf(
                    'تغییر زمان نوبت:
خدمت: %s
زمان قبلی: %s
زمان جدید: %s
متخصص: %s',
                    $booking->service->name,
                    verta($booking->getOriginal('booking_time'))->format('Y/m/d H:i'),
                    verta($booking->booking_time)->format('Y/m/d H:i'),
                    $booking->specialist->name
                );

                $this->smsService->send($booking->user->phone, $message);
            }
        }

        $this->cacheService->flush();
    }

    public function deleted(Booking $booking): void
    {
        $this->cacheService->flush();
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
                $message = sprintf(
                    "%d امتیاز به حساب کاربری شما اضافه شد. موجودی فعلی: %d",
                    $totalPoints,
                    LoyaltyPoint::where('user_id', $user->id)->sum('points')
                );

                $this->smsService->send($user->phone, $message);
            }
        } catch (\Exception $e) {
        }
    }
}
