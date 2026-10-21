<?php

namespace App\Services\Payment;

use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * نتیجه‌ی بازگشت از درگاه برای پیش‌پرداخت نوبت — مشترک callback وب (PaymentController) و برگشت اپ موبایل
 * (AppPaymentCallbackController). بدون session: سهم کیف پول از ردیف تراکنش خوانده می‌شود (BookingWalletShare).
 *
 * خروجی: ['state' => paid|refunded|failed|verifying, 'booking' => ?Booking, 'message' => string]
 * verifying: تأیید درگاه نرسید و نوبت نگه داشته شد (HeldBookingPayment)؛ نتیجه با پیامک.
 */
class BookingPaymentFinalizer
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly BookingRepositoryInterface $bookingRepository,
        private readonly BookingWalletShare $walletShare,
        private readonly LostSlotRefundService $lostSlotRefund,
    ) {}

    public function handle(Request $request): array
    {
        $result = $this->paymentService->verifyPayment($request);

        if (empty($result['booking_id'])) {
            return ['state' => 'failed', 'booking' => null, 'message' => $result['message'] ?? 'پرداخت ناموفق بود'];
        }

        $booking = $this->bookingRepository->findOrFail($result['booking_id']);
        $transaction = isset($result['transaction_id']) ? PaymentTransaction::find($result['transaction_id']) : null;

        if ($result['success']) {
            return $this->succeeded($booking->refresh(), $result, $transaction);
        }

        if (! empty($result['verifying'])) {
            // نوبت نگه داشته شد؛ اگر بررسی همین حالا (صف sync) تمامش کرد، نتیجه‌ی واقعی
            $booking->refresh();
            if ($booking->payment_status === 'paid') {
                return ['state' => 'paid', 'booking' => $booking, 'message' => 'پرداخت با موفقیت انجام شد و نوبت شما ثبت شد.'];
            }

            return ['state' => $booking->status === 'cancelled' ? 'failed' : 'verifying', 'booking' => $booking, 'message' => $result['message']];
        }

        if (! empty($result['refunded'])) {
            return ['state' => 'refunded', 'booking' => $booking, 'message' => $result['message']];
        }

        // پرداخت ناموفق یا انصراف: نوبت لغو می‌شود و BookingObserver سهم کیف پول این نوبت را برمی‌گرداند
        if ($booking->payment_status !== 'paid' && $booking->status !== 'cancelled') {
            $this->bookingRepository->update($booking, ['status' => 'cancelled', 'cancellation_reason' => 'پرداخت ناموفق']);
        }
        $this->walletShare->releaseForBooking($booking, 'پرداخت ناموفق بود');

        return ['state' => 'failed', 'booking' => $booking, 'message' => $result['message'] ?? 'پرداخت ناموفق بود'];
    }

    /** تأیید دیرهنگام پرداخت نگه‌داشته (HeldBookingPayment): همان ثبت بازگشت موفق از درگاه */
    public function completeVerified(Booking $booking, array $result, ?PaymentTransaction $transaction): array
    {
        return $this->succeeded($booking, $result, $transaction);
    }

    private function succeeded(Booking $booking, array $result, ?PaymentTransaction $transaction): array
    {
        if ($booking->payment_status === 'paid') {
            return ['state' => 'paid', 'booking' => $booking, 'message' => 'پرداخت با موفقیت انجام شد و نوبت شما ثبت شد.'];
        }

        // ⭐ نوبت در این فاصله لغو شد. تصمیم ۲۰۲۶-۰۹-۲۶: اگر ساعت هنوز آزاد است، پرداخت دیرهنگام نوبتِ لغو خودکار را
        // تایید می‌کند (مثل قبل). ولی نوبتی که مدیر/متخصص/خود مشتری لغو کرده، یا سهم کیف پولش با لغو برگشته، زنده نمی‌شود — پول برمی‌گردد.
        if ($booking->status === 'cancelled' && ! $this->revivable($booking, $transaction)) {
            return $this->refundLostSlot($booking, $transaction, $this->slotTakenByAnother($booking) ? 'slot_taken' : 'booking_cancelled');
        }

        $newStatus = ($booking->specialist->auto_confirm_bookings ?? false) ? 'confirmed' : 'pending';

        try {
            DB::transaction(function () use ($booking, $result, $newStatus, $transaction) {
                $walletToman = $this->walletShare->settle($transaction);

                $paymentDetails = [
                    'method' => $walletToman > 0 ? 'wallet_gateway' : 'gateway',
                    'gateway_ref' => $result['ref_id'] ?? $result['reference'],
                    'card_pan' => $result['card_pan'] ?? null,
                    'gateway' => $result['gateway'] ?? null,
                    'gateway_fee' => $result['gateway_fee'] ?? 0,
                ];

                if ($walletToman > 0) {
                    $paymentDetails['wallet_amount'] = $walletToman;
                    $paymentDetails['gateway_amount'] = max(0, (float) $booking->prepayment_amount - $walletToman);
                }

                $this->bookingRepository->update($booking, [
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                    'payment_reference' => $result['ref_id'] ?? $result['reference'],
                    'status' => $newStatus,
                    'payment_details' => $paymentDetails,
                ]);
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            if (! str_contains($e->getMessage(), 'active_slot')) {
                throw $e;
            }

            return $this->refundLostSlot($booking->fresh(), $transaction, 'slot_taken');
        }

        // تلاش‌های قبلی پرداخت همین نوبت که به نتیجه نرسیدند (مثلاً مشتری بانک را بست و دوباره پرداخت کرد)
        $this->walletShare->releaseForBooking($booking, 'نوبت با پرداخت دیگری تسویه شد', $transaction?->id);

        return ['state' => 'paid', 'booking' => $booking, 'message' => 'پرداخت با موفقیت انجام شد و نوبت شما ثبت شد.'];
    }

    private function revivable(Booking $booking, ?PaymentTransaction $transaction): bool
    {
        if (! in_array($booking->cancelled_by, [null, 'system'], true)) {
            return false;
        }

        // سهم کیف پول این تلاش هنگام لغو برگشت داده شده — پیش‌پرداخت دیگر کامل نیست
        return ! ($transaction && (float) $transaction->wallet_toman > 0 && $transaction->fresh()->wallet_state !== BookingWalletShare::HELD);
    }

    private function slotTakenByAnother(Booking $booking): bool
    {
        return Booking::query()
            ->whereKeyNot($booking->id)
            ->where('specialist_id', $booking->specialist_id)
            ->where('booking_time', $booking->booking_time)
            ->where('status', '!=', 'cancelled')
            ->exists();
    }

    private function refundLostSlot(Booking $booking, ?PaymentTransaction $transaction, string $reason): array
    {
        $refund = $this->lostSlotRefund->refund($booking, $transaction, null, $booking->prepayment_amount, $reason);

        Log::warning('Gateway payment arrived for a booking that can no longer be kept', [
            'booking_id' => $booking->id, 'transaction_id' => $transaction?->id, 'handled' => $refund['handled'],
        ]);

        return ['state' => 'refunded', 'booking' => $booking, 'message' => $refund['handled']
            ? LostSlotRefundService::message($refund)
            : 'مبلغ این پرداخت قبلاً به شما برگشت داده شده است و نوبت ثبت نشد.'];
    }
}
