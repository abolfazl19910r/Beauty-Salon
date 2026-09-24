<?php

namespace App\Services;

use App\Payments\GatewayManager;
use App\Payments\GatewayStartRequest;
use App\Payments\GatewayVerifyRequest;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Support\CurrentSalon;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * ⭐ لایه‌ی چند درگاه — مرحله‌ی ۰ (۲۰۲۶-۰۹-۲۵): این سرویس دیگه مستقیم با زرین‌پال حرف نمی‌زنه؛
     * درگاه‌های فعال سالن جاری رو از App\Payments\GatewayManager می‌گیره (انتخاب مشتری + جایگزینی
     * خودکار) و خود درخواست/تایید رو driver انجام می‌ده. قرارداد عمومی متدها (آرایه‌های برگشتی) و
     * رفتار با یک درگاه زرین‌پال دقیقاً مثل قبله. درگاهی که پرداخت باهاش شروع شد در session نگه داشته
     * می‌شه تا تایید با همون درگاه انجام بشه (درگاه‌ها authority همدیگه رو نمی‌شناسن).
     *
     * قانون ۲۰۲۶-۰۹-۲۴ سر جاشه: پول مشتری‌های سالن فقط به درگاه‌های خودِ سالن؛ بدون درگاه فعال →
     * {success:false, reason:merchant_missing} قبل از هر درخواست.
     */
    public function __construct(
        protected readonly BookingRepositoryInterface $bookingRepository,
        protected readonly ?GatewayManager $gateways = null,
    ) {}

    private function manager(): GatewayManager
    {
        return $this->gateways ?? app(GatewayManager::class);
    }

    private function salon(): ?\App\Models\Salon
    {
        return app(CurrentSalon::class)->get();
    }

    public function isAvailable(): bool
    {
        return $this->manager()->gatewaysFor($this->salon())->isNotEmpty();
    }

    /** درگاه‌های فعال سالن جاری برای صفحه‌ی انتخاب درگاه مشتری. */
    public function availableGateways(): \Illuminate\Support\Collection
    {
        return $this->manager()->gatewaysFor($this->salon());
    }

    private function unavailableResult(): array
    {
        return [
            'success' => false,
            'reason' => 'merchant_missing',
            'message' => \App\Support\ZarinpalMerchant::CUSTOMER_MESSAGE,
        ];
    }

    private function startWith(GatewayStartRequest $request, string $sessionKey, ?int $preferredGatewayId, array $logContext): array
    {
        [$result, $gateway] = $this->manager()->start($this->salon(), $request, $preferredGatewayId);

        if ($result->success && $gateway) {
            session([$sessionKey => $gateway->id]);

            return [
                'success' => true,
                'payment_url' => $result->redirectUrl,
                'reference' => $result->token,
                'gateway_id' => $gateway->id,
                'gateway' => $gateway->driver,
            ];
        }

        Log::error('❌ Payment Request Failed', $logContext + [
            'gateway' => $gateway?->driver,
            'message' => $result->message,
            'full_response' => $result->raw,
        ]);

        return [
            'success' => false,
            'message' => $result->message ?? 'خطا در اتصال به درگاه پرداخت. لطفاً دوباره تلاش کنید.',
        ];
    }

    /** درگاهی که پرداخت باهاش شروع شد؛ فقط از بین درگاه‌های همین سالن (session دستکاری‌شده بی‌اثره). */
    private function gatewayFromSession(string $sessionKey): ?\App\Models\SalonPaymentGateway
    {
        $gateways = $this->manager()->gatewaysFor($this->salon());
        $id = session($sessionKey);

        return $gateways->firstWhere('id', $id) ?? $gateways->first();
    }

    public function createPayment($booking, $customAmount = null, ?int $preferredGatewayId = null): array
    {
        if (! $this->isAvailable()) {
            return $this->unavailableResult();
        }

        $request = GatewayStartRequest::fromToman(
            $customAmount ?? $booking->prepayment_amount,
            route('payment.callback', ['booking' => $booking->id]),
            sprintf('پیش پرداخت نوبت سالن زیبایی - شماره %d', $booking->id),
            $booking->user->phone ?? '',
            $booking->user->email ?? '',
            (string) $booking->id,
        );

        return $this->startWith($request, 'payment_gateway_'.$booking->id, $preferredGatewayId, ['booking_id' => $booking->id]);
    }

    public function verifyPayment($request): array
    {
        try {
            $bookingId = $request->booking ?? null;
            if (! $bookingId) {
                $status = $request->Status ?? $request->status;
                if ($status === 'NOK' || $status === 'cancel') {
                    return ['success' => false, 'message' => 'پرداخت توسط کاربر لغو شد'];
                }
                Log::error('❌ Booking ID not found in callback');

                return ['success' => false, 'message' => 'شناسه رزرو یافت نشد'];
            }

            $booking = $this->bookingRepository->findOrFail($bookingId);
            $gateway = $this->gatewayFromSession('payment_gateway_'.$booking->id);

            if (! $gateway) {
                return ['status' => 'failed', 'success' => false, 'booking_id' => $booking->id, 'message' => \App\Support\ZarinpalMerchant::CUSTOMER_MESSAGE];
            }

            $partialPayment = session('partial_payment_'.$booking->id);
            $verifyAmount = $partialPayment ? $partialPayment['remaining_amount'] : $booking->prepayment_amount;

            $result = $this->manager()->verify($gateway, new GatewayVerifyRequest(
                (int) ((float) $verifyAmount * 10),
                $request instanceof \Illuminate\Http\Request ? $request->all() : (array) $request,
            ));

            if ($result->cancelledByUser) {
                Log::warning('⚠️ Payment Cancelled by User', ['authority' => $result->token]);

                return ['success' => false, 'message' => $result->message];
            }

            if ($result->success) {
                return [
                    'status' => 'success',
                    'success' => true,
                    'booking_id' => $booking->id,
                    'reference' => $result->token,
                    'ref_id' => $result->refId,
                    'card_pan' => $result->cardPan,
                    'fee' => $result->fee,
                    'gateway' => $gateway->driver,
                ];
            }

            Log::warning('⚠️ Payment Verification Failed', [
                'booking_id' => $booking->id,
                'authority' => $result->token,
                'gateway' => $gateway->driver,
                'message' => $result->message,
            ]);

            return ['status' => 'failed', 'success' => false, 'booking_id' => $booking->id, 'message' => $result->message];
        } catch (\Exception $e) {
            Log::error('💥 Payment Verification Exception', ['error' => $e->getMessage()]);

            return ['status' => 'failed', 'success' => false, 'message' => 'خطا در تایید پرداخت'];
        }
    }

    public function createWalletChargePayment($user, $amount, ?int $preferredGatewayId = null): array
    {
        if (! $this->isAvailable()) {
            return $this->unavailableResult();
        }

        $request = GatewayStartRequest::fromToman(
            $amount,
            route('wallet.charge.callback'),
            sprintf('شارژ کیف پول - کاربر: %s', $user->name ?? $user->phone),
            $user->phone ?? '',
            $user->email ?? '',
        );

        return $this->startWith($request, 'wallet_charge_gateway', $preferredGatewayId, ['user_id' => $user->id, 'amount' => $amount]);
    }

    public function verifyWalletChargePayment($request, $expectedAmount): array
    {
        try {
            $gateway = $this->gatewayFromSession('wallet_charge_gateway');

            if (! $gateway) {
                return ['success' => false, 'message' => \App\Support\ZarinpalMerchant::CUSTOMER_MESSAGE];
            }

            $result = $this->manager()->verify($gateway, new GatewayVerifyRequest(
                (int) ((float) $expectedAmount * 10),
                $request instanceof \Illuminate\Http\Request ? $request->all() : (array) $request,
            ));

            if ($result->success) {
                return [
                    'success' => true,
                    'ref_id' => $result->refId,
                    'card_pan' => $result->cardPan,
                ];
            }

            return ['success' => false, 'message' => $result->message];
        } catch (\Exception $e) {
            Log::error('💥 Wallet Charge Verification Exception', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'خطا در تایید پرداخت'];
        }
    }
}
