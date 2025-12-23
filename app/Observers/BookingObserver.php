<?php

namespace App\Observers;

use App\Models\Booking;
use App\Notifications\BookingNotification;
use App\Notifications\BookingStatusUpdated;
use App\Notifications\SpecialistBookingCancelledNotification;
use App\Services\ReportCacheService;
use App\Services\SMSService;
use Illuminate\Support\Facades\Cache;
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

    public function updated(Booking $booking): void
    {
        try {
            if ($booking->wasChanged('payment_status') && $booking->payment_status === 'paid') {

                $cacheKey = "booking_payment_processed_{$booking->id}";

                if (Cache::has($cacheKey)) {
                    return;
                }

                Cache::put($cacheKey, now()->toDateTimeString(), 60);

                $specialist = $booking->specialist;
                $isAutoConfirm = $specialist->auto_confirm_bookings ?? false;

                try {
                    if ($booking->status === 'confirmed') {
                        $booking->user->notify(new BookingStatusUpdated($booking, 'confirmed'));

                        $specialist->notify(new BookingNotification($booking, false));
                    } elseif ($booking->status === 'pending') {
                        $booking->user->notify(new BookingStatusUpdated($booking, 'pending_specialist'));
                        $specialist->notify(new BookingNotification($booking, true));
                    }
                } catch (\Exception $e) {
                    Log::error('❌ Failed to send notifications', [
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage()
                    ]);
                }

                try {
                    $this->addLoyaltyPoints($booking);
                } catch (\Exception $e) {
                    Log::error('❌ Failed to add loyalty points', [
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if ($booking->wasChanged('status') && $booking->status === 'cancelled') {
                try {
                    $booking->user->notify(new BookingStatusUpdated($booking, 'cancelled', $booking->cancellation_reason));
                } catch (\Exception $e) {
                    Log::error('❌ Failed to send cancellation notification to customer', [
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage()
                    ]);
                }

                if ($booking->specialist) {
                    try {
                        $cancelledBy = $booking->cancelled_by ?? 'system';
                        $booking->specialist->notify(new SpecialistBookingCancelledNotification($booking, $cancelledBy));
                    } catch (\Exception $e) {
                        Log::error('❌ Failed to send cancellation notification to specialist', [
                            'booking_id' => $booking->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            $this->cacheService->flush();

        } catch (\Exception $e) {
            Log::error('💥 Critical Error in BookingObserver', [
                'booking_id' => $booking->id ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function addLoyaltyPoints(Booking $booking): void
    {
        try {
            $user = $booking->user;
            if (!$user || !method_exists($user, 'addLoyaltyPoints')) {
                Log::warning('⚠️ User does not have addLoyaltyPoints method', [
                    'booking_id' => $booking->id,
                    'user_id' => $user->id ?? null
                ]);
                return;
            }

            $points = 5 + floor($booking->prepayment_amount / 10000);
            $user->addLoyaltyPoints($points, "رزرو نوبت #{$booking->id}", $booking->id);
        } catch (\Exception $e) {
            Log::error('❌ Loyalty Points Error', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function deleted(Booking $booking): void
    {
        try {
            $this->cacheService->flush();
        } catch (\Exception $e) {
            Log::error('❌ Error in deleted observer', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
