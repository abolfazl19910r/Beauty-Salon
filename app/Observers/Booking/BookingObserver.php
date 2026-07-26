<?php

namespace App\Observers\Booking;

use App\Events\Booking\BookingCreated;
use App\Events\Payment\PaymentSucceeded;
use App\Models\AdminWallet;
use App\Models\Booking;
use App\Models\LoyaltySetting;
use App\Models\WalletSetting;
use App\Notifications\Booking\BookingNotification;
use App\Services\ReportCacheService;
use App\Services\SMSService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
            if (!$specialist) {
                Log::warning('⚠️ Specialist not found', ['booking_id' => $booking->id]);
                return;
            }

            $adminCommissionPercentage = $specialist->getEffectiveCommissionRate();

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
                Log::warning('⚠️ Specialist not found for cancellation', ['booking_id' => $booking->id]);
                return;
            }

            $cancelledBy = $booking->cancelled_by ?? 'system';

            DB::transaction(function() use ($booking, $specialist, $cancelledBy) {
                $settings = WalletSetting::get();

                $existingRefund = \App\Models\UserWalletTransaction::where('booking_id', $booking->id)
                    ->where('type', 'refund')
                    ->exists();

                if ($existingRefund) {
                    Log::warning('⚠️ Refund already exists', ['booking_id' => $booking->id]);
                    return;
                }

                if ($cancelledBy === 'specialist') {
                    $customerWallet = $booking->user->getOrCreateWallet();
                    $customerWallet->addRefund(
                        $booking->prepayment_amount,
                        $booking->id,
                        "بازگشت وجه از نوبت #{$booking->id} - لغو توسط متخصص"
                    );

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
                        $wallet = $specialist->getOrCreateWallet();
                        $specialistShare = $customerFee * 0.8;

                        $wallet->addIncome(
                            $specialistShare,
                            $booking->id,
                            "جریمه لغو نوبت #{$booking->id} توسط مشتری"
                        );
                    }
                }
                elseif ($cancelledBy === 'admin') {
                    $customerWallet = $booking->user->getOrCreateWallet();
                    $customerWallet->addRefund(
                        $booking->prepayment_amount,
                        $booking->id,
                        "بازگشت وجه از نوبت #{$booking->id} - لغو توسط مدیر"
                    );
                }
            });

        } catch (\Exception $e) {
            Log::error('❌ Failed to handle cancellation', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);
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
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function sendCustomerCancellationSMS(Booking $booking): void
    {
        $persianDate = verta($booking->booking_time)->format('Y/m/d');
        $persianTime = verta($booking->booking_time)->format('H:i');

        $message = sprintf(
            "سلام %s، نوبت شما لغو شد.\n👤 متخصص: %s\n💇 سرویس: %s\n📅 تاریخ: %s\n⏰ زمان: %s\n💰 پیش‌پرداخت: %s تومان\n🔢 پیگیری: #%s\n🏠 آدرس: تهران، خیابان ... \n❌ دلیل: %s",
            $booking->user->name,
            $booking->specialist->name,
            $booking->service->name,
            $persianDate,
            $persianTime,
            number_format($booking->prepayment_amount),
            $booking->id,
            $booking->cancellation_reason ?? 'ذکر نشده'
        );

        $this->smsService->send($booking->user->phone, $message);
    }

    protected function sendSpecialistCancellationSMS(Booking $booking, string $cancelledBy): void
    {
        $specialist = $booking->specialist;
        $user = $booking->user;

        $title = match($cancelledBy) {
            'specialist' => 'نوبت توسط شما لغو شد',
            'admin' => 'نوبت توسط مدیر سیستم لغو شد',
            'customer' => 'نوبت توسط مشتری لغو شد',
            default => 'نوبت لغو شد'
        };

        $message = "{$specialist->name} عزیز، سلام 👋\n\n";
        $message .= "📋 {$title}\n\n";
        $message .= "👤 مشتری: {$user->name}\n";
        $message .= "📞 تماس: {$user->phone}\n";
        $message .= "💇 سرویس: {$booking->service->name}\n";
        $message .= "📅 تاریخ: " . verta($booking->booking_time)->format('Y/m/d') . " - ساعت " . verta($booking->booking_time)->format('H:i');

        $this->smsService->send($specialist->phone, $message);
    }

    protected function sendCustomerConfirmationSMS(Booking $booking): void
    {
        $persianDate = verta($booking->booking_time)->format('Y/m/d');
        $persianTime = verta($booking->booking_time)->format('H:i');

        $message = sprintf(
            "سلام %s، نوبت شما تایید شد.\n👤 متخصص: %s\n💇 سرویس: %s\n📅 تاریخ: %s\n⏰ زمان: %s\n💰 پیش‌پرداخت: %s تومان\n🔢 پیگیری: #%s\n🏠 آدرس: تهران، خیابان ... \n✅ لطفا ۱۵ دقیقه زودتر در محل حضور داشته باشید.",
            $booking->user->name,
            $booking->specialist->name,
            $booking->service->name,
            $persianDate,
            $persianTime,
            number_format($booking->prepayment_amount),
            $booking->id
        );

        $this->smsService->send($booking->user->phone, $message);
    }

    protected function sendCustomerPendingSMS(Booking $booking): void
    {
        $message = sprintf(
            "سلام %s، نوبت شما با موفقیت ثبت شد و در انتظار تایید نهایی متخصص است. نتیجه به زودی اطلاع‌رسانی می‌شود.",
            $booking->user->name
        );
        $this->smsService->send($booking->user->phone, $message);
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
            if (!$user || !method_exists($user, 'addLoyaltyPoints')) {
                return;
            }

            $pointsPerAmount = (int) LoyaltySetting::getValue('points_per_amount', 10000);
            $pointsPerAmount = $pointsPerAmount > 0 ? $pointsPerAmount : 10000;

            $points = 5 + floor($booking->prepayment_amount / $pointsPerAmount);
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
