<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingStatusUpdated;
use App\Notifications\CustomerBookingNotification;
use App\Notifications\BookingNotification;
use App\Notifications\SpecialistBookingCancelledNotification;
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
        if ($booking->payment_status === 'paid' && $booking->status !== 'pending_payment') {
            $this->sendInitialNotifications($booking);
        }
        $this->cacheService->flush();
    }

    public function updated(Booking $booking): void
    {
        if ($booking->wasChanged('payment_status') && $booking->payment_status === 'paid') {
            $this->sendInitialNotifications($booking);

            if (method_exists(User::class, 'addLoyaltyPoints')) {
                $this->addLoyaltyPoints($booking);
            }
        }

        if ($booking->wasChanged('status')) {
            $this->handleStatusChange($booking);
        }

        $this->cacheService->flush();
    }

    protected function sendInitialNotifications(Booking $booking): void
    {
        if ($booking->user) {
            $booking->user->notify(new CustomerBookingNotification($booking));
        }

        if ($booking->specialist) {
            $booking->specialist->notify(new BookingNotification($booking));
        }
    }

    protected function handleStatusChange(Booking $booking): void
    {
        $newStatus = $booking->status;

        if ($newStatus === 'confirmed') {
            $booking->user->notify(new BookingStatusUpdated($booking, 'confirmed'));
        }

        elseif ($newStatus === 'cancelled') {

            $booking->user->notify(new BookingStatusUpdated($booking, 'cancelled', $booking->cancellation_reason));

            if ($booking->specialist) {
                $cancelledBy = $booking->cancelled_by ?? 'system';
                $booking->specialist->notify(new SpecialistBookingCancelledNotification($booking, $cancelledBy));
            }
        }
    }

    protected function addLoyaltyPoints(Booking $booking): void
    {
        try {
            $user = $booking->user;
            if (!$user || !method_exists($user, 'addLoyaltyPoints')) return;

            $points = 5 + floor($booking->prepayment_amount / 10000);
            $user->addLoyaltyPoints($points, "رزرو خدمت {$booking->service->name}");

            if ($user->phone) {
                $message = "مشتری گرامی، {$points} امتیاز وفاداری برای رزرو شماره #{$booking->id} به حساب شما اضافه شد.";
                $this->smsService->send($user->phone, $message);
            }
        } catch (\Exception $e) {
            Log::error('Error in Loyalty Points:', ['error' => $e->getMessage()]);
        }
    }

    public function deleted(Booking $booking): void
    {
        $this->cacheService->flush();
    }
}
