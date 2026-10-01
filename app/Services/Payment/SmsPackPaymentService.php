<?php

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use App\Models\SmsCreditPurchase;
use App\Payments\Drivers\ZarinpalDriver;
use App\Payments\GatewayStartRequest;
use App\Payments\GatewayVerifyRequest;
use Illuminate\Support\Facades\Log;

/**
 * پرداخت آنلاین بسته‌ی پیامک (۲۰۲۶-۰۹-۳۰) — همان مسیر خرید اشتراک: درگاه پلتفرم (زرین‌پال با merchant سراسری)، ثبت در
 * دفتر واحد payment_transactions با purpose «sms_pack» و بازگشت از payments.return به admin.billing.sms-pack.callback.
 */
class SmsPackPaymentService
{
    protected function driver(): ZarinpalDriver
    {
        return new ZarinpalDriver(
            ['merchant_id' => (string) config('services.zarinpal.merchant_id')],
            (bool) config('services.zarinpal.sandbox', true),
        );
    }

    /** @return array{success: bool, payment_url?: string, message?: string} */
    public function createPayment(SmsCreditPurchase $purchase): array
    {
        $transaction = PaymentTransaction::create([
            'salon_id' => $purchase->salon_id,
            'driver' => 'zarinpal',
            'purpose' => 'sms_pack',
            'payable_type' => $purchase->getMorphClass(),
            'payable_id' => $purchase->id,
            'user_id' => $purchase->created_by,
            'amount_rial' => $purchase->amount * 10,
            'callback_url' => route('admin.billing.sms-pack.callback', ['purchase' => $purchase->id]),
        ]);

        $result = $this->driver()->start(new GatewayStartRequest(
            $transaction->amount_rial,
            route('payments.return', ['publicId' => $transaction->public_id]),
            sprintf('بسته‌ی پیامک %s قطعه — سالن «%s»', number_format($purchase->parts), $purchase->salon()->withoutGlobalScopes()->value('name')),
            $purchase->createdBy?->phone ?? '',
        ));

        $transaction->update([
            'token' => $result->token,
            'status' => $result->success ? 'pending' : 'failed',
            'start_response' => $result->raw ?: null,
        ]);

        if ($result->success) {
            $purchase->update(['authority' => $result->token]);

            return ['success' => true, 'payment_url' => $result->redirectUrl];
        }

        Log::error('SmsPackPaymentService: شروع پرداخت بسته‌ی پیامک ناموفق', ['purchase_id' => $purchase->id, 'response' => $result->raw]);

        return ['success' => false, 'message' => 'خطا در اتصال به درگاه پرداخت. لطفاً دوباره تلاش کنید.'];
    }

    /** @return array{success: bool, ref_id?: string, message?: string} */
    public function verifyPayment(SmsCreditPurchase $purchase, ?string $status, ?string $authority): array
    {
        if ($status === 'NOK' || $status === 'cancel') {
            return ['success' => false, 'message' => 'پرداخت توسط شما لغو شد.'];
        }

        if (! $authority || $authority !== $purchase->authority) {
            Log::warning('SmsPackPaymentService: عدم تطابق authority در کال‌بک', ['purchase_id' => $purchase->id]);

            return ['success' => false, 'message' => 'اطلاعات تراکنش نامعتبر است.'];
        }

        $result = $this->driver()->verify(new GatewayVerifyRequest(
            $purchase->amount * 10,
            ['Authority' => $authority, 'Status' => (string) $status],
        ));

        PaymentTransaction::where('purpose', 'sms_pack')
            ->where('payable_type', $purchase->getMorphClass())->where('payable_id', $purchase->id)
            ->where('token', $authority)
            ->update([
                'status' => $result->success ? 'paid' : 'failed',
                'ref_id' => $result->refId,
                'card_pan' => $result->cardPan,
                'verify_response' => $result->raw ? json_encode($result->raw, JSON_UNESCAPED_UNICODE) : null,
                'verified_at' => $result->success ? now() : null,
                'updated_at' => now(),
            ]);

        if ($result->success) {
            return ['success' => true, 'ref_id' => (string) $result->refId];
        }

        Log::warning('SmsPackPaymentService: تأیید پرداخت بسته‌ی پیامک ناموفق', ['purchase_id' => $purchase->id, 'response' => $result->raw]);

        return ['success' => false, 'message' => 'پرداخت تأیید نشد.'];
    }
}
