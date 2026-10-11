<?php

namespace App\Services\Payment;

use App\Models\Booking;
use App\Models\User;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;

/**
 * شروع پرداخت پیش‌پرداخت نوبت — مشترک صفحه‌ی پرداخت وب و اپ موبایل (۲۰۲۶-۱۰-۱۱).
 *
 * - پیش‌پرداخت صفر (تخفیف کامل): همان لحظه پرداخت‌شده.
 * - کیف پول کل مبلغ را می‌پوشاند: با قفل ردیف نوبت و کیف پول، همان لحظه پرداخت‌شده.
 * - در غیر این صورت: سهم کیف پول (اگر هست) کم و روی تراکنش ثبت می‌شود (BookingWalletShare) و بقیه به درگاه می‌رود.
 *
 * خروجی: ['state' => paid|redirect|error, 'booking' => Booking, 'gateway' => ?array (خروجی createPayment), 'message' => string]
 */
class BookingPaymentStarter
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly BookingRepositoryInterface $bookingRepository,
        private readonly BookingWalletShare $walletShare,
    ) {}

    public function start(User $user, Booking $booking, float $walletToman, ?int $preferredGatewayId, ?string $callbackUrl = null): array
    {
        $prepayment = (float) $booking->prepayment_amount;

        if ($prepayment <= 0) {
            return $this->markPaid($booking, [
                'method' => 'full_discount',
                'gateway_amount' => 0,
                'discount_code' => $booking->discount_code,
            ], 'FREE-DISCOUNT-'.$booking->id, 'نوبت شما با تخفیف کامل با موفقیت ثبت شد.');
        }

        $balance = (float) $user->getOrCreateWallet()->fresh()->balance;
        $walletToman = max(0.0, min($walletToman, $balance, $prepayment));

        if ($walletToman >= $prepayment) {
            return $this->payFromWallet($user, $booking, $prepayment);
        }

        if ($walletToman > 0) {
            $this->walletShare->hold($user, $booking, $walletToman, "پرداخت بخشی از نوبت #{$booking->id} از کیف پول (مابقی از درگاه)");
        }

        $result = $this->paymentService->createPayment($booking, $prepayment - $walletToman, $preferredGatewayId, $walletToman, $callbackUrl);

        if (($result['success'] ?? false) && isset($result['payment_url'])) {
            return ['state' => 'redirect', 'booking' => $booking, 'gateway' => $result, 'message' => ''];
        }

        return [
            'state' => 'error',
            'booking' => $booking,
            'gateway' => null,
            'message' => $result['message'] ?? 'در حال حاضر امکان اتصال به درگاه بانکی وجود ندارد.',
        ];
    }

    private function payFromWallet(User $user, Booking $booking, float $amount): array
    {
        return $this->markPaid($booking, [
            'method' => 'wallet',
            'wallet_amount' => $amount,
            'gateway_amount' => 0,
        ], 'WALLET-'.$booking->id.'-'.time(), 'پرداخت از کیف پول با موفقیت انجام شد', function () use ($user, $booking, $amount) {
            $wallet = $user->getOrCreateWallet();
            $wallet = $wallet->newQuery()->lockForUpdate()->findOrFail($wallet->id);
            $wallet->deductPayment($amount, $booking->id, "پرداخت نوبت #{$booking->id} از کیف پول");
        });
    }

    private function markPaid(Booking $booking, array $details, string $reference, string $message, ?\Closure $before = null): array
    {
        $paid = DB::transaction(function () use ($booking, $details, $reference, $before) {
            $locked = Booking::query()->lockForUpdate()->find($booking->id);
            if (! $locked || $locked->payment_status === 'paid' || ! $locked->isPayable()) {
                return false;
            }

            if ($before) {
                $before();
            }

            $this->bookingRepository->update($booking, [
                'payment_status' => 'paid',
                'status' => ($booking->specialist->auto_confirm_bookings ?? false) ? 'confirmed' : 'pending',
                'paid_at' => now(),
                'payment_reference' => $reference,
                'payment_details' => $details,
            ]);

            return true;
        });

        $booking->refresh();

        if (! $paid && $booking->payment_status !== 'paid') {
            return ['state' => 'error', 'booking' => $booking, 'gateway' => null, 'message' => $booking->notPayableReason()];
        }

        return ['state' => 'paid', 'booking' => $booking, 'gateway' => null, 'message' => $message];
    }
}
