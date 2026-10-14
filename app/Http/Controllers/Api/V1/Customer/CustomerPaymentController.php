<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Exceptions\Api\ApiException;
use App\Http\Responses\ApiResponse;
use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Services\Api\CustomerPresenter;
use App\Services\Payment\AppCheckout;
use App\Services\Payment\BookingPaymentStarter;
use App\Services\Payment\WalletChargeService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * پرداخت در اپ «ماهرو» (بسته‌ی ۳؛ تصمیم ۲۰۲۶-۱۰-۱۱: صفحه‌ی بانک در مرورگر بیرونی):
 *  1. POST bookings/{id}/pay یا POST wallet/charge → یا «پرداخت شد» (کیف پول/تخفیف کامل)، یا checkout_url امضاشده.
 *  2. اپ checkout_url را در مرورگر بیرونی باز می‌کند؛ درگاه → /payments/return/{tx} → /s/{slug}/app/... → صفحه‌ای با
 *     mahru://payment?tx=... که مشتری را به اپ برمی‌گرداند (AppPaymentController).
 *  3. اپ همیشه وضعیت قطعی را از GET payments/{tx} می‌خواند (نه از لینک). اگر مشتری مرورگر را بست، همین را دوباره می‌پرسد.
 * منطق مالی همان صفحه‌ی وب است (BookingPaymentStarter / BookingPaymentFinalizer / WalletChargeService).
 */
class CustomerPaymentController extends CustomerController
{
    public const CHARGE_MIN = 10000;

    public const CHARGE_MAX = 50000000;

    public function __construct(
        protected readonly PaymentService $paymentService,
        protected readonly AppCheckout $checkout,
    ) {}

    /** POST bookings/{id}/pay {gateway_id?, wallet_amount?} */
    public function pay(Request $request, int $bookingId, BookingPaymentStarter $starter): JsonResponse
    {
        $input = $request->validate([
            'gateway_id' => ['nullable', 'integer'],
            'wallet_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $booking = Booking::query()->whereKey($bookingId)->where('user_id', $this->customer($request)->id)->with(['service', 'specialist'])->first();
        if (! $booking) {
            throw new ApiException('not_found', 'نوبت پیدا نشد.', 404);
        }
        if ($booking->payment_status === 'paid') {
            return ApiResponse::success(['state' => 'paid', 'booking' => CustomerPresenter::booking($booking)]);
        }
        if (! $booking->isPayable()) {
            throw new ApiException('booking_not_payable', $booking->notPayableReason(), 409);
        }

        $result = $starter->start(
            $this->customer($request),
            $booking,
            (float) ($input['wallet_amount'] ?? 0),
            isset($input['gateway_id']) ? (int) $input['gateway_id'] : null,
            route('app.payment.callback', ['salon_slug' => $this->salon()->slug, 'booking' => $booking->id]),
        );

        return match ($result['state']) {
            'paid' => ApiResponse::success(['state' => 'paid', 'booking' => CustomerPresenter::booking($result['booking']->load(['service', 'specialist']))]),
            'redirect' => ApiResponse::success($this->redirectPayload($result['gateway'])),
            default => throw new ApiException('payment_unavailable', $result['message'], 422),
        };
    }

    /** POST wallet/charge {amount, gateway_id?} — تومان */
    public function charge(Request $request): JsonResponse
    {
        $input = $request->validate([
            'amount' => ['required', 'integer', 'min:'.self::CHARGE_MIN, 'max:'.self::CHARGE_MAX],
            'gateway_id' => ['nullable', 'integer'],
        ]);

        $result = $this->paymentService->createWalletChargePayment(
            $this->customer($request),
            (int) $input['amount'],
            isset($input['gateway_id']) ? (int) $input['gateway_id'] : null,
            route('app.wallet.callback', ['salon_slug' => $this->salon()->slug]),
        );

        if (! ($result['success'] ?? false) || ! isset($result['payment_url'])) {
            throw new ApiException('payment_unavailable', $result['message'] ?? 'در حال حاضر امکان اتصال به درگاه بانکی وجود ندارد.', 422);
        }

        return ApiResponse::success($this->redirectPayload($result));
    }

    /** GET wallet/charge/gateways?amount= — درگاه‌ها و کارمزد برای صفحه‌ی شارژ */
    public function chargeGateways(Request $request): JsonResponse
    {
        $input = $request->validate(['amount' => ['nullable', 'integer', 'min:0', 'max:'.self::CHARGE_MAX]]);

        return ApiResponse::success([
            'online_payment' => $this->salon()->acceptsOnlinePayments(),
            'min' => self::CHARGE_MIN,
            'max' => self::CHARGE_MAX,
            'suggested' => [50000, 100000, 200000, 500000, 1000000],
            'gateways' => $this->paymentService->gatewayOptions((int) ($input['amount'] ?? 0)),
        ]);
    }

    /** GET payments/{publicId} — وضعیت قطعی یک پرداخت اپ (فقط تراکنش خود مشتری در همین سالن). */
    public function status(Request $request, string $publicId): JsonResponse
    {
        $transaction = PaymentTransaction::query()
            ->where('public_id', $publicId)
            ->where('user_id', $this->customer($request)->id)
            ->where('salon_id', $this->salon()->id)
            ->whereIn('purpose', ['booking', 'wallet_charge', 'course'])
            ->first();

        if (! $transaction) {
            throw new ApiException('not_found', 'پرداخت پیدا نشد.', 404);
        }

        $state = CustomerPresenter::paymentState((string) $transaction->status);
        $data = [
            'transaction' => $transaction->public_id,
            'purpose' => $transaction->purpose,
            'state' => $state,
            'amount' => intdiv((int) $transaction->amount_rial, 10),
            'fee' => intdiv((int) $transaction->fee_rial, 10),
            'ref_id' => $state === 'paid' ? $transaction->ref_id : null,
            'created_at' => $transaction->created_at?->toIso8601String(),
        ];

        if ($transaction->purpose === 'booking') {
            $booking = Booking::query()->with(['service', 'specialist'])->find($transaction->payable_id);
            // درگاه پول را گرفت ولی نوبت نگه داشته نشد (لغوشده/ساعت رزروشده) → «refunded»
            if ($state === 'paid' && $booking && $booking->payment_status !== 'paid') {
                $data['state'] = 'pending';
            }
            $data['booking'] = $booking ? CustomerPresenter::booking($booking) : null;
        } elseif ($transaction->purpose === 'course') {
            // دوره‌ی درمانی (بسته‌ی ب): پرداخت تأییدشده‌ای که هنوز روی دوره ثبت نشده ← pending (اپ دوباره می‌پرسد)
            $course = \App\Models\CustomerCourse::query()->find($transaction->payable_id);
            $recorded = $course && $course->payments()->withoutGlobalScopes()->where('payment_transaction_id', $transaction->id)->exists();
            if ($state === 'paid' && ! $recorded) {
                $data['state'] = 'pending'; // برگشت به کیف پول خودش status=refunded تراکنش است
            }
            $data['course'] = $course ? \App\Services\Api\CoursePresenter::course($course) : null;
            $data['wallet_balance'] = (float) ($this->customer($request)->wallet()->first()?->balance ?? 0);
        } else {
            $data['credited'] = $transaction->wallet_state === WalletChargeService::CREDITED;
            if ($state === 'paid' && ! $data['credited']) {
                $data['state'] = 'pending';
            }
            $data['wallet_balance'] = (float) ($this->customer($request)->wallet()->first()?->balance ?? 0);
        }

        return ApiResponse::success($data);
    }

    private function redirectPayload(array $gatewayResult): array
    {
        return [
            'state' => 'redirect',
            'checkout_url' => $this->checkout->remember($this->salon(), $gatewayResult),
            'transaction' => $gatewayResult['transaction'],
            'gateway' => $gatewayResult['gateway'] ?? null,
            'fee' => (int) ($gatewayResult['fee'] ?? 0),
            'expires_in' => (int) config('api.checkout_ttl_minutes', 15) * 60,
        ];
    }
}
