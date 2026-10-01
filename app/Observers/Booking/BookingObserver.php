<?php

namespace App\Observers\Booking;

use App\Events\Booking\BookingCreated;
use App\Events\Payment\PaymentSucceeded;
use App\Models\AdminWallet;
use App\Models\Booking;
use App\Models\LoyaltySetting;
use App\Models\WalletSetting;
use App\Notifications\Booking\BookingNotification;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\Notification\NotificationSettingService;
use App\Services\ReportCacheService;
use App\Services\SMSService;
use App\Support\Notifications\NotificationEvents;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingObserver
{
    public function __construct(
        protected readonly ReportCacheService $cacheService,
        protected readonly SMSService $smsService,
        protected readonly NotificationSettingService $notificationSettings,
        protected readonly BookingRepositoryInterface $bookingRepository,
    ) {}

    /**
     * R-Events: Previously, BookingCreated was not dispatched anywhere (not here, not in
     * BookingService::createBooking(), not in AdminBookingController::store())
     * — that is, even though the EventServiceProvider mapped
     * BookingCreated → SendAdminBookingNotifications, the admin never received a new booking notification from the day
     * first. By putting dispatch here
     * (not in the controller/service), both bookings from the customer and manual booking creation by
     * the admin (AdminBookingController::store()) are covered equally.
     */
    public function created(Booking $booking): void
    {
        try {
            event(new BookingCreated($booking));
        } catch (\Exception $e) {
            Log::error('❌ Failed to dispatch BookingCreated event', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function updated(Booking $booking): void
    {
        try {
            if ($booking->wasChanged('payment_status') && $booking->payment_status === 'paid') {
                $this->handlePaymentStatusChange($booking);
            }

            if ($booking->wasChanged('status') && $booking->status === 'cancelled') {
                $this->handleBookingCancellation($booking);
            }

            $this->cacheService->flush();

        } catch (\Exception $e) {
            Log::error('💥 Critical Error in BookingObserver', [
                'booking_id' => $booking->id ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    protected function handlePaymentStatusChange(Booking $booking): void
    {
        $cacheKey = "booking_payment_processed_{$booking->id}";

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, 180);

        /**
         * R-Observers: Previously `event(new PaymentSucceeded($booking))` was dispatched manually
         * from 3 separate call sites inside PaymentController (process/processWithWallet/callback).
         * A 4th real payment path — SecurePaymentController::verify() (routed under payments/secure,
         * 2FA-protected) — also marks the booking as paid but never dispatched this event, so the
         * admin "new payment received" notification (SendAdminPaymentNotification) was silently
         * skipped for that path. Since this idempotent cache guard already fires exactly once per
         * payment regardless of which controller triggered it, dispatching here (not in each
         * controller) is the single source of truth — the same reasoning already applied to
         * BookingCreated in R-Events — and closes the gap for every current and future payment path.
         */
        try {
            event(new PaymentSucceeded($booking));
        } catch (\Exception $e) {
            Log::error('❌ Failed to dispatch PaymentSucceeded event', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->addIncomeAndCommission($booking);

        $specialist = $booking->specialist;
        $isAutoConfirm = $specialist->auto_confirm_bookings ?? false;

        try {
            if ($booking->status === 'confirmed') {
                $this->sendCustomerConfirmationSMS($booking);
                $specialist->notify(new BookingNotification($booking, false));
            } elseif ($booking->status === 'pending') {
                $this->sendCustomerPendingSMS($booking);
                $specialist->notify(new BookingNotification($booking, true));
            }
        } catch (\Exception $e) {
            Log::error('❌ Failed to send payment notifications', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $this->addLoyaltyPoints($booking);
        } catch (\Exception $e) {
            Log::error('❌ Failed to add loyalty points', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function handleBookingCancellation(Booking $booking): void
    {
        $cacheKey = "booking_cancellation_processed_{$booking->id}";

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, 300);

        $this->handleCancellation($booking);

        $this->sendCancellationSMS($booking);
    }

    protected function addIncomeAndCommission(Booking $booking): void
    {
        try {
            $specialist = $booking->specialist;
            if (! $specialist) {
                Log::warning('⚠️ Specialist not found', ['booking_id' => $booking->id]);

                return;
            }

            $adminCommissionPercentage = $specialist->getEffectiveCommissionRate();

            $totalAmount = $booking->prepayment_amount;
            $adminCommission = ($totalAmount * $adminCommissionPercentage) / 100;
            $specialistIncome = $totalAmount - $adminCommission;

            DB::transaction(function () use ($specialist, $specialistIncome, $adminCommission, $booking) {
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
            });

        } catch (\Exception $e) {
            Log::error('❌ Failed to add income and commission', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
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
            if (! $specialist) {
                Log::warning('⚠️ Specialist not found for cancellation', ['booking_id' => $booking->id]);

                return;
            }

            $cancelledBy = $booking->cancelled_by ?? 'system';

            DB::transaction(function () use ($booking, $specialist, $cancelledBy) {
                $settings = WalletSetting::get();

                $existingRefund = \App\Models\UserWalletTransaction::where('booking_id', $booking->id)
                    ->where('type', 'refund')
                    ->exists();

                if ($existingRefund) {
                    Log::warning('⚠️ Refund already exists', ['booking_id' => $booking->id]);

                    return;
                }

                $refundAmount = 0.0;
                $refundTransaction = null;
                $refundDetails = [];

                if ($cancelledBy === 'specialist') {
                    /**
                     * ⭐ Modified by explicit user decision: Specialist cancellation penalty is not a *separate* deduction from
                     * Specialist wallet — but is deducted from the amount that was supposed to be returned in full to the client
                     * . That is, the client always gets back (prepayment - penalty),
                     * and only the penalty amount remains in the admin wallet (not the client, not the specialist). The specialist's original share from this turn is also returned in full
                     * (without restrictions) as before by reverseOriginalPayout() — that is, the specialist always remains in this net zero
                     * state, regardless of the amount of the penalty.
                     *
                     * ⭐ Added (suggestion 1): Now, like the client, it has a separate time threshold
                     * (specialist_cancellation_before_hours) — cancellation much earlier than the turn
                     * is no longer penalized.
                     * ⭐ Added (Proposal 4): Increased penalty for repeated cancellations — the number of
                     * cancellations by this specialist (by himself) in the last specialist_repeat_cancellation_window_days
                     * (including this cancellation) is counted and passed to calculateSpecialistCancellationPenalty()
                     * ; if specialist_repeat_cancellation_threshold is exceeded,
                     * specialist_repeat_cancellation_extra_percentage is added to the base percentage.
                     */
                    $recentSpecialistCancellations = $this->bookingRepository->query()->where('specialist_id', $specialist->id)
                        ->where('cancelled_by', 'specialist')
                        ->where('cancelled_at', '>=', now()->subDays($settings->specialist_repeat_cancellation_window_days))
                        ->count();

                    $specialistPenalty = $settings->calculateSpecialistCancellationPenalty(
                        $booking->prepayment_amount,
                        $booking->booking_time,
                        $recentSpecialistCancellations
                    );
                    $refundAmount = max(0, $booking->prepayment_amount - $specialistPenalty);

                    if ($refundAmount > 0) {
                        $customerWallet = $booking->user->getOrCreateWallet();
                        $refundTransaction = $customerWallet->addRefund(
                            $refundAmount,
                            $booking->id,
                            "بازگشت وجه از نوبت #{$booking->id} - لغو توسط متخصص"
                        );
                        $refundDetails = [
                            'method' => 'wallet',
                            'cancelled_by' => 'specialist',
                            'specialist_penalty' => $specialistPenalty,
                        ];
                    }

                    if ($specialistPenalty > 0) {
                        AdminWallet::getWallet()->addCommission(
                            $specialistPenalty,
                            $booking->id,
                            "جریمه لغو نوبت #{$booking->id} توسط متخصص"
                        );
                    }
                } elseif ($cancelledBy === 'customer') {
                    $customerFee = $settings->calculateCustomerCancellationFee(
                        $booking->prepayment_amount,
                        $booking->booking_time
                    );

                    $refundAmount = max(0, $booking->prepayment_amount - $customerFee);

                    if ($refundAmount > 0) {
                        $customerWallet = $booking->user->getOrCreateWallet();
                        $refundTransaction = $customerWallet->addRefund(
                            $refundAmount,
                            $booking->id,
                            "بازگشت وجه از نوبت #{$booking->id} - با جریمه لغو"
                        );
                        $refundDetails = [
                            'method' => 'wallet',
                            'cancelled_by' => 'customer',
                            'cancellation_fee' => $customerFee,
                        ];
                    }

                    if ($customerFee > 0) {
                        /**
                         * ⭐ Modified by explicit user decision: Previously, 80% of this penalty was added as
                         * "compensation" to the expert's wallet (addIncome), and the remaining 20%
                         * was not explicitly recorded anywhere. Now the entire penalty (100%) is explicitly added to the
                         * admin's wallet — the expert does not receive any share of this penalty (just like
                         * he always loses the main share of his turn, no more, no less).
                         */
                        AdminWallet::getWallet()->addCommission(
                            $customerFee,
                            $booking->id,
                            "جریمه لغو نوبت #{$booking->id} توسط مشتری"
                        );
                    }
                } elseif ($cancelledBy === 'admin') {
                    $refundAmount = (float) $booking->prepayment_amount;
                    $customerWallet = $booking->user->getOrCreateWallet();
                    $refundTransaction = $customerWallet->addRefund(
                        $refundAmount,
                        $booking->id,
                        "بازگشت وجه از نوبت #{$booking->id} - لغو توسط مدیر"
                    );
                    $refundDetails = ['method' => 'wallet', 'cancelled_by' => 'admin'];
                }

                /**
                 * R-Observers addendum: previously the specialist's original income and the
                 * admin's original commission (both credited in addIncomeAndCommission() when
                 * the booking was paid) were never reversed here — meaning every cancelled-after
                 * -paid booking cost the salon the customer refund AND the specialist's income,
                 * without recovering the specialist's/admin's share. Reversed for every actor
                 * (including 'system'), since the underlying booking is no longer a valid paid
                 * service regardless of who cancelled it.
                 */
                $this->reverseOriginalPayout($booking, $specialist);

                if ($refundAmount > 0 && $refundTransaction) {
                    $booking->update([
                        'refund_status' => 'refunded',
                        'refunded_amount' => $refundAmount,
                        'refunded_at' => now(),
                        'refund_reference' => 'WALLET-REFUND-'.$refundTransaction->id,
                        'refund_details' => $refundDetails,
                    ]);
                }
            });

        } catch (\Exception $e) {
            Log::error('❌ Failed to handle cancellation', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function reverseOriginalPayout(Booking $booking, $specialist): void
    {
        $specialistWallet = $specialist->getOrCreateWallet();

        $incomeTransaction = $specialistWallet->transactions()
            ->where('booking_id', $booking->id)
            ->where('type', 'income')
            ->first();

        if ($incomeTransaction) {
            $wasSettled = ($incomeTransaction->metadata['status'] ?? null) === 'settled';

            $specialistWallet->reverseIncome(
                $incomeTransaction,
                "برگشت سهم به‌خاطر لغو نوبت #{$booking->id}"
            );

            if ($wasSettled) {
                Log::warning('⚠️ سهم متخصص از موجودی واقعی (نه در انتظار) کسر شد — اگر قبلاً برداشت شده باشد موجودی منفی می‌شود', [
                    'booking_id' => $booking->id,
                    'specialist_wallet_id' => $specialistWallet->id,
                    'amount' => $incomeTransaction->amount,
                ]);
            }
        }

        $adminWallet = AdminWallet::getWallet();
        $commissionTransaction = $adminWallet->transactions()
            ->where('booking_id', $booking->id)
            ->where('type', 'commission')
            ->first();

        if ($commissionTransaction) {
            $adminWallet->deductCommission(
                (float) $commissionTransaction->amount,
                $booking->id,
                "برگشت کمیسیون به‌خاطر لغو نوبت #{$booking->id}"
            );
        }
    }

    protected function sendCancellationSMS(Booking $booking): void
    {
        $cacheKey = "sms_cancellation_sent_{$booking->id}";

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, 300);

        try {
            $cancelledBy = $booking->cancelled_by ?? 'system';

            $this->sendCustomerCancellationSMS($booking);

            if ($booking->specialist) {
                $this->sendSpecialistCancellationSMS($booking, $cancelledBy);
            }

        } catch (\Exception $e) {
            Log::error('❌ Failed to send cancellation SMS', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function sendCustomerCancellationSMS(Booking $booking): void
    {
        if (! $this->notificationSettings->isEnabled(NotificationEvents::BOOKING_CANCELLED_CUSTOMER, 'sms', $booking->salon_id)) {
            return;
        }

        /**
         * مبلغ بازگشتی فقط وقتی در پیامک می‌آید که واقعاً برگشت داده شده (refunded_amount را handleCancellation()
         * پیش از این متد روی همین مدل گذاشته) — «بازگشت X تومان» برای نوبتِ پرداخت‌نشده گمراه‌کننده است (۲۰۲۶-۰۹-۳۰).
         */
        $refundedAmount = (float) ($booking->refunded_amount ?? 0);

        $fee = (float) (($booking->refund_details['cancellation_fee'] ?? null)
            ?? ($booking->refund_details['specialist_penalty'] ?? 0));

        $message = \App\Support\Sms\SmsText::bookingCancelledForCustomer($booking, $booking->cancellation_reason, $refundedAmount, $fee);

        $this->smsService->send($booking->user->phone, $message, $booking->salon_id);
    }

    protected function sendSpecialistCancellationSMS(Booking $booking, string $cancelledBy): void
    {
        if (! $this->notificationSettings->isEnabled(NotificationEvents::BOOKING_CANCELLED_SPECIALIST, 'sms', $booking->salon_id)) {
            return;
        }

        $specialist = $booking->specialist;
        $user = $booking->user;

        $penalty = $cancelledBy === 'specialist' ? (float) ($booking->refund_details['specialist_penalty'] ?? 0) : 0.0;
        $message = \App\Support\Sms\SmsText::cancelledForSpecialist($booking, $cancelledBy, $penalty);

        $this->smsService->send($specialist->phone, $message, $booking->salon_id);
    }

    protected function sendCustomerConfirmationSMS(Booking $booking): void
    {
        if (! $this->notificationSettings->isEnabled(NotificationEvents::BOOKING_CONFIRMED_CUSTOMER, 'sms', $booking->salon_id)) {
            return;
        }

        $message = \App\Support\Sms\SmsText::bookingConfirmed($booking);

        $this->smsService->send($booking->user->phone, $message, $booking->salon_id);
    }

    protected function sendCustomerPendingSMS(Booking $booking): void
    {
        if (! $this->notificationSettings->isEnabled(NotificationEvents::BOOKING_PAID_PENDING_APPROVAL_CUSTOMER, 'sms', $booking->salon_id)) {
            return;
        }

        $message = \App\Support\Sms\SmsText::bookingPending($booking);
        $this->smsService->send($booking->user->phone, $message, $booking->salon_id);
    }

    /**
     * Integration (R-AdminLoyalty phase): Previously "10000" was hardcoded and the formula
     * (5 + floor(prepayment/10000)) never reacted to admin settings.
     * The constant component "5+" was intentionally kept because it is considered behavior according to the project documentation;
     * Only the denominator is read from loyalty_settings (points_per_amount key).
     */
    protected function addLoyaltyPoints(Booking $booking): void
    {
        try {
            $user = $booking->user;
            if (! $user || ! method_exists($user, 'addLoyaltyPoints')) {
                return;
            }

            $pointsPerAmount = (int) LoyaltySetting::getValue('points_per_amount', 10000);
            $pointsPerAmount = $pointsPerAmount > 0 ? $pointsPerAmount : 10000;

            $points = 5 + floor($booking->prepayment_amount / $pointsPerAmount);
            $user->addLoyaltyPoints($points, "رزرو نوبت #{$booking->id}", $booking->id);
        } catch (\Exception $e) {
            Log::error('❌ Loyalty Points Error', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
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
                'error' => $e->getMessage(),
            ]);
        }
    }
}
