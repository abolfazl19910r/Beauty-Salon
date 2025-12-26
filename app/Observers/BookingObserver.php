<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\WalletSetting;
use App\Models\AdminWallet;
use App\Notifications\BookingNotification;
use App\Notifications\BookingStatusUpdated;
use App\Notifications\SpecialistBookingCancelledNotification;
use App\Services\ReportCacheService;
use App\Services\SMSService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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

                $this->addIncomeAndCommission($booking);

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
                $this->handleCancellation($booking);

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

    protected function addIncomeAndCommission(Booking $booking): void
    {
        try {
            $specialist = $booking->specialist;
            if (!$specialist) {
                Log::warning('⚠️ Specialist not found for booking', ['booking_id' => $booking->id]);
                return;
            }

            $settings = WalletSetting::first();
            $adminCommissionPercentage = $settings->admin_commission_percentage ?? 10;

            $totalAmount = $booking->prepayment_amount;
            $adminCommission = ($totalAmount * $adminCommissionPercentage) / 100;
            $specialistIncome = $totalAmount - $adminCommission;

            DB::transaction(function() use ($specialist, $specialistIncome, $adminCommission, $booking) {

                $wallet = $specialist->getOrCreateWallet();
                $wallet->addIncome(
                    $specialistIncome,
                    $booking->id,
                    "درآمد از نوبت #{$booking->id} - {$booking->service->name}"
                );

                $adminWallet = AdminWallet::getWallet();
                $adminWallet->addCommission(
                    $adminCommission,
                    $booking->id,
                    "کمیسیون از نوبت #{$booking->id} - متخصص: {$specialist->name}"
                );

                Log::info('✅ Income and commission added', [
                    'booking_id' => $booking->id,
                    'specialist_id' => $specialist->id,
                    'specialist_income' => $specialistIncome,
                    'admin_commission' => $adminCommission
                ]);
            });

        } catch (\Exception $e) {
            Log::error('❌ Failed to add income and commission', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function handleCancellation(Booking $booking): void
    {
        try {
            if ($booking->payment_status !== 'paid') {
                return;
            }

            $specialist = $booking->specialist;
            if (!$specialist) {
                return;
            }

            $cancelledBy = $booking->cancelled_by ?? 'system';

            DB::transaction(function() use ($booking, $specialist, $cancelledBy) {
                $settings = WalletSetting::get();
                $wallet = $specialist->getOrCreateWallet();

                if ($cancelledBy === 'specialist') {

                    $customerWallet = $booking->user->getOrCreateWallet();
                    $customerWallet->addRefund(
                        $booking->prepayment_amount,
                        $booking->id,
                        "بازگشت وجه از نوبت #{$booking->id} - لغو توسط متخصص"
                    );

                    Log::info('✅ Refund issued to customer', [
                        'booking_id' => $booking->id,
                        'customer_id' => $booking->user_id,
                        'amount' => $booking->prepayment_amount
                    ]);
                }

                elseif ($cancelledBy === 'customer') {
                    $customerFee = $settings->calculateCustomerCancellationFee(
                        $booking->prepayment_amount,
                        $booking->booking_time
                    );

                    $refundAmount = $booking->prepayment_amount - $customerFee;

                    if ($refundAmount > 0) {
                        $customerWallet = $booking->user->getOrCreateWallet();
                        $customerWallet->addRefund(
                            $refundAmount,
                            $booking->id,
                            "بازگشت وجه از نوبت #{$booking->id} - با جریمه لغو"
                        );
                    }

                    if ($customerFee > 0) {
                        $specialistShare = $customerFee * 0.8;

                        $wallet->addIncome(
                            $specialistShare,
                            $booking->id,
                            "جریمه لغو نوبت #{$booking->id} توسط مشتری"
                        );
                    }

                    Log::info('✅ Customer cancellation processed', [
                        'booking_id' => $booking->id,
                        'customer_id' => $booking->user_id,
                        'fee' => $customerFee,
                        'refund' => $refundAmount
                    ]);
                }
            });

        } catch (\Exception $e) {
            Log::error('❌ Failed to handle cancellation', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function addLoyaltyPoints(Booking $booking): void
    {
        try {
            $user = $booking->user;
            if (!$user || !method_exists($user, 'addLoyaltyPoints')) {
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
