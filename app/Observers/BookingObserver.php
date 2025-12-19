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
                    Log::debug('⏭️ Payment already processed, skipping Observer', [
                        'booking_id' => $booking->id,
                        'cached_at' => Cache::get($cacheKey)
                    ]);
                    return;
                }

                Cache::put($cacheKey, now()->toDateTimeString(), 60);

                Log::info('💰 Payment Status Changed to Paid', [
                    'booking_id' => $booking->id,
                    'old_status' => $booking->getOriginal('payment_status'),
                    'new_payment_status' => $booking->payment_status,
                    'current_booking_status' => $booking->status
                ]);

                $specialist = $booking->specialist;
                $isAutoConfirm = $specialist->auto_confirm_bookings ?? false;

                try {
                    if ($booking->status === 'confirmed') {
                        $booking->user->notify(new BookingStatusUpdated($booking, 'confirmed'));
                        Log::info('✅ Customer notification sent (auto-confirmed)', [
                            'booking_id' => $booking->id,
                            'status' => $booking->status
                        ]);

                        $specialist->notify(new BookingNotification($booking, false));
                        Log::info('✅ Specialist notification sent (auto-confirmed)', [
                            'booking_id' => $booking->id,
                            'specialist_id' => $specialist->id
                        ]);
                    } elseif ($booking->status === 'pending') {
                        $booking->user->notify(new BookingStatusUpdated($booking, 'pending_specialist'));
                        Log::info('✅ Customer notification sent (pending approval)', [
                            'booking_id' => $booking->id,
                            'status' => $booking->status
                        ]);

                        $specialist->notify(new BookingNotification($booking, true));
                        Log::info('✅ Specialist notification sent (needs approval)', [
                            'booking_id' => $booking->id,
                            'specialist_id' => $specialist->id
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('❌ Failed to send notifications', [
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage()
                    ]);
                }

                try {
                    $this->addLoyaltyPoints($booking);
                    Log::info('🎁 Loyalty points added', ['booking_id' => $booking->id]);
                } catch (\Exception $e) {
                    Log::error('❌ Failed to add loyalty points', [
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if ($booking->wasChanged('status') && $booking->status === 'cancelled') {
                Log::info('🚫 Booking Cancelled', [
                    'booking_id' => $booking->id,
                    'cancelled_by' => $booking->cancelled_by
                ]);

                try {
                    $booking->user->notify(new BookingStatusUpdated($booking, 'cancelled', $booking->cancellation_reason));
                    Log::info('✅ Cancellation notification sent to customer', [
                        'booking_id' => $booking->id
                    ]);
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
                        Log::info('✅ Cancellation notification sent to specialist', [
                            'booking_id' => $booking->id,
                            'specialist_id' => $booking->specialist_id
                        ]);
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

            Log::info('🎁 Loyalty points added successfully', [
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'points' => $points
            ]);
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
            Log::info('🗑️ Booking deleted, cache flushed', [
                'booking_id' => $booking->id
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error in deleted observer', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
