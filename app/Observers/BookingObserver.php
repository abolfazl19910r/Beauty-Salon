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
        $this->cacheService->flush();
    }

    public function updated(Booking $booking): void
    {
        if ($booking->wasChanged('payment_status') && $booking->payment_status === 'paid') {
            if (method_exists(User::class, 'addLoyaltyPoints')) {
                $this->addLoyaltyPoints($booking);
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
