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

    /**
     * ⭐ مرحله‌ی ۱: گزینه‌های صفحه‌ی انتخاب درگاه مشتری — هر درگاه فعال با کارمزد و مبلغ نهایی خودش برای
     * مبلغ پایه‌ی داده‌شده (تومان). اولی (بالاترین اولویت) پیش‌فرض انتخاب‌شده است.
     *
     * @return array<int, array{id: int, name: string, driver: string, fee: int, total: int, fee_percent: float, fee_fixed: int}>
     */
    public function gatewayOptions(float|int $baseToman): array
    {
        return $this->availableGateways()->map(fn (\App\Models\SalonPaymentGateway $g) => [
            'id' => $g->id,
            'name' => $g->displayName(),
            'driver' => $g->driver,
            'fee' => $g->feeTomanFor($baseToman),
            'total' => (int) round((float) $baseToman) + $g->feeTomanFor($baseToman),
            'fee_percent' => (float) $g->fee_percent,
            'fee_fixed' => (int) $g->fee_fixed_toman,
        ])->values()->all();
    }

    private function unavailableResult(): array
    {
        return [
            'success' => false,
            'reason' => 'merchant_missing',
            'message' => \App\Support\ZarinpalMerchant::CUSTOMER_MESSAGE,
        ];
    }

    /**
     * ⭐ مرحله‌ی ۰ بخش ۲ (۲۰۲۶-۰۹-۲۵): هر شروع پرداخت یک ردیف payment_transactions می‌سازه و آدرس بازگشتی
     * که به درگاه داده می‌شه مسیر مشترک payments.return همون تراکنشه (نه callback کسب‌وکار) — تا درگاه‌هایی
     * که با POST cross-site و بدون کوکی session برمی‌گردن هم کار کنن. callback کسب‌وکار در خود ردیف ذخیره
     * می‌شه و GatewayReturnController مشتری رو (با GET) به اون می‌فرسته.
     */
    private function startWith(GatewayStartRequest $request, string $sessionKey, ?int $preferredGatewayId, array $logContext, string $purpose, ?\Illuminate\Database\Eloquent\Model $payable, ?int $userId): array
    {
        $salon = $this->salon();
        $transaction = \App\Models\PaymentTransaction::create([
            'salon_id' => $salon?->id,
            'driver' => (string) $this->manager()->gatewaysFor($salon)->first()?->driver,
            'purpose' => $purpose,
            'payable_type' => $payable?->getMorphClass(),
            'payable_id' => $payable?->getKey(),
            'user_id' => $userId,
            'amount_rial' => $request->amountRial,
            'callback_url' => $request->callbackUrl,
        ]);

        $request = $request->with(
            callbackUrl: route('payments.return', ['publicId' => $transaction->public_id]),
            transactionId: $transaction->id,
        );

        [$result, $gateway, $feeRial] = $this->manager()->start($salon, $request, $preferredGatewayId);

        // ⭐ مرحله‌ی ۱: amount_rial = مبلغی که واقعاً از مشتری گرفته می‌شه (مبلغ پایه + کارمزد همون درگاهی که
        // در نهایت استفاده شد) — verify با همین مبلغ انجام می‌شه. کیف پول/نوبت فقط مبلغ پایه رو حساب می‌کنن.
        $transaction->update([
            'gateway_id' => $gateway?->id,
            'driver' => $gateway?->driver ?? $transaction->driver,
            'amount_rial' => $request->amountRial + (int) $feeRial,
            'fee_rial' => (int) $feeRial,
            'token' => $result->token,
            'status' => $result->success ? 'pending' : 'failed',
            'start_response' => $result->raw ?: null,
        ]);

        if ($result->success && $gateway) {
            session([$sessionKey => $gateway->id]);

            return [
                'success' => true,
                'payment_url' => $result->redirectUrl,
                'method' => $result->method,
                'form_fields' => $result->formFields,
                'reference' => $result->token,
                'gateway_id' => $gateway->id,
                'gateway' => $gateway->driver,
                'fee' => intdiv((int) $feeRial, 10),
                'transaction' => $transaction->public_id,
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

    /**
     * تراکنشِ پارامتر tx (از GatewayReturnController)، فقط اگه مال همین سالن و همین نوع پرداخت باشه.
     */
    private function transactionFrom($request, string $purpose): ?\App\Models\PaymentTransaction
    {
        $publicId = $request instanceof \Illuminate\Http\Request ? $request->query('tx', $request->input('tx')) : ($request->tx ?? null);

        if (! is_string($publicId) || $publicId === '') {
            return null;
        }

        return \App\Models\PaymentTransaction::where('public_id', $publicId)
            ->where('salon_id', $this->salon()?->id)
            ->where('purpose', $purpose)
            ->first();
    }

    private function recordVerification(?\App\Models\PaymentTransaction $transaction, \App\Payments\GatewayVerifyResult $result): void
    {
        // تراکنشی که payments:reconcile برگشت زده، با یک callback دیرهنگام دوباره «failed» نمی‌شه
        if ($transaction && in_array($transaction->fresh()?->status, \App\Models\PaymentTransaction::REVERSAL_STATUSES, true)) {
            return;
        }

        $transaction?->update([
            'status' => $result->success ? 'paid' : ($result->cancelledByUser ? 'cancelled' : 'failed'),
            'ref_id' => $result->refId,
            'card_pan' => $result->cardPan,
            'verify_response' => $result->raw ?: null,
            'verified_at' => $result->success ? now() : null,
        ]);
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

        return $this->startWith($request, 'payment_gateway_'.$booking->id, $preferredGatewayId, ['booking_id' => $booking->id], 'booking', $booking, $booking->user_id ?? null);
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
            $transaction = $this->transactionFrom($request, 'booking');
            if ($transaction && ((int) $transaction->payable_id !== (int) $booking->id)) {
                $transaction = null; // tx یک نوبت دیگه — نادیده (به مسیر session برمی‌گرده)
            }

            if ($transaction?->isPaid()) {
                // idempotent: callback تکراری همون تراکنش دوباره تأیید/ثبت نمی‌شه
                return ['status' => 'success', 'success' => true, 'booking_id' => $booking->id, 'reference' => $transaction->token, 'ref_id' => $transaction->ref_id, 'card_pan' => $transaction->card_pan, 'fee' => null, 'gateway' => $transaction->driver, 'gateway_fee' => intdiv((int) $transaction->fee_rial, 10), 'transaction_id' => $transaction->id];
            }

            if ($transaction && in_array($transaction->status, \App\Models\PaymentTransaction::REVERSAL_STATUSES, true)) {
                // ⭐ پول این تراکنش قبلاً برگشت داده شده (ساعت از دست رفته یا پاسخ verify گم‌شده) — دوباره تایید نمی‌شه
                return ['status' => 'failed', 'success' => false, 'booking_id' => $booking->id, 'refunded' => true,
                    'message' => 'مبلغ این پرداخت قبلاً به شما برگشت داده شده است و نوبت ثبت نشد.'];
            }

            $gateway = $transaction?->gateway ?? $this->gatewayFromSession('payment_gateway_'.$booking->id);

            if (! $gateway) {
                return ['status' => 'failed', 'success' => false, 'booking_id' => $booking->id, 'message' => \App\Support\ZarinpalMerchant::CUSTOMER_MESSAGE];
            }

            $partialPayment = session('partial_payment_'.$booking->id);
            $verifyAmount = $partialPayment ? $partialPayment['remaining_amount'] : $booking->prepayment_amount;

            $result = $this->manager()->verify($gateway, new GatewayVerifyRequest(
                $transaction ? $transaction->amount_rial : (int) ((float) $verifyAmount * 10),
                $request instanceof \Illuminate\Http\Request ? $request->all() : (array) $request,
                $transaction?->token,
                $transaction?->id,
            ));
            $this->recordVerification($transaction, $result);

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
                    'gateway_fee' => $transaction ? intdiv((int) $transaction->fee_rial, 10) : 0,
                    'transaction_id' => $transaction?->id,
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

        return $this->startWith($request, 'wallet_charge_gateway', $preferredGatewayId, ['user_id' => $user->id, 'amount' => $amount], 'wallet_charge', null, $user->id);
    }

    public function verifyWalletChargePayment($request, $expectedAmount): array
    {
        try {
            $transaction = $this->transactionFrom($request, 'wallet_charge');

            if ($transaction?->isPaid()) {
                return ['success' => false, 'message' => 'این تراکنش قبلاً ثبت شده است.'];
            }

            $gateway = $transaction?->gateway ?? $this->gatewayFromSession('wallet_charge_gateway');

            if (! $gateway) {
                return ['success' => false, 'message' => \App\Support\ZarinpalMerchant::CUSTOMER_MESSAGE];
            }

            $result = $this->manager()->verify($gateway, new GatewayVerifyRequest(
                $transaction ? $transaction->amount_rial : (int) ((float) $expectedAmount * 10),
                $request instanceof \Illuminate\Http\Request ? $request->all() : (array) $request,
                $transaction?->token,
                $transaction?->id,
            ));
            $this->recordVerification($transaction, $result);

            if ($result->success) {
                return [
                    'success' => true,
                    'ref_id' => $result->refId,
                    'card_pan' => $result->cardPan,
                    'gateway' => $gateway->driver,
                    'gateway_fee' => $transaction ? intdiv((int) $transaction->fee_rial, 10) : 0,
                ];
            }

            return ['success' => false, 'message' => $result->message];
        } catch (\Exception $e) {
            Log::error('💥 Wallet Charge Verification Exception', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'خطا در تایید پرداخت'];
        }
    }
}
