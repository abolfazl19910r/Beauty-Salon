<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Payments\Drivers\ZarinpalDriver;
use App\Payments\GatewayStartRequest;
use App\Payments\GatewayVerifyRequest;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Support\Facades\Log;

class SubscriptionPaymentService
{
    /**
     * ⭐ لایه‌ی چند درگاه — مرحله‌ی ۰ (۲۰۲۶-۰۹-۲۵): خرید اشتراک سالن‌ها (پول به حساب **پلتفرم**) حالا از
     * همون ZarinpalDriver استفاده می‌کنه، با credentials پلتفرم از config (نه درگاه هیچ سالنی). قرارداد
     * متدها و متن پیام‌ها بدون تغییر. پرداخت اشتراک عمداً روی GatewayManager نیست: درگاه پلتفرم یکیه و
     * ربطی به درگاه‌های سالن نداره.
     */
    public function __construct(protected readonly InvoiceRepositoryInterface $invoiceRepository) {}

    protected function driver(): ZarinpalDriver
    {
        return new ZarinpalDriver(
            ['merchant_id' => (string) config('services.zarinpal.merchant_id')],
            (bool) config('services.zarinpal.sandbox', true),
        );
    }

    public function createPayment(Invoice $invoice): array
    {
        // ⭐ مرحله‌ی ۰ بخش ۲: ثبت در دفتر واحد payment_transactions + بازگشت از مسیر مشترک payments.return
        $transaction = \App\Models\PaymentTransaction::create([
            'salon_id' => $invoice->salon_id,
            'driver' => 'zarinpal',
            'purpose' => 'subscription',
            'payable_type' => $invoice->getMorphClass(),
            'payable_id' => $invoice->id,
            'user_id' => $invoice->created_by,
            'amount_rial' => (int) ((float) $invoice->amount * 10),
            'callback_url' => route('admin.billing.callback', ['invoice' => $invoice->id]),
        ]);

        $result = $this->driver()->start(new GatewayStartRequest(
            $transaction->amount_rial,
            route('payments.return', ['publicId' => $transaction->public_id]),
            sprintf('خرید/تمدید اشتراک سالن «%s» (%s)', $invoice->salon->name, $invoice->subscription_type),
            $invoice->createdBy?->phone ?? '',
        ));

        $transaction->update([
            'token' => $result->token,
            'status' => $result->success ? 'pending' : 'failed',
            'start_response' => $result->raw ?: null,
        ]);

        if ($result->success) {
            $this->invoiceRepository->update($invoice, ['authority' => $result->token]);

            return [
                'success' => true,
                'payment_url' => $result->redirectUrl,
                'reference' => $result->token,
            ];
        }

        if ($result->raw === []) {
            Log::error('SubscriptionPaymentService: خطای اتصال به درگاه پرداخت اشتراک', ['invoice_id' => $invoice->id]);

            return ['success' => false, 'message' => 'خطا در اتصال به درگاه پرداخت. لطفاً دوباره تلاش کنید.'];
        }

        $errorCode = $result->raw['data']['code'] ?? $result->raw['errors']['code'] ?? -999;
        Log::error('SubscriptionPaymentService: درخواست پرداخت اشتراک ناموفق', [
            'invoice_id' => $invoice->id,
            'error_code' => $errorCode,
            'response' => $result->raw,
        ]);

        return [
            'success' => false,
            'message' => $result->raw['errors']['message'] ?? "خطای زرین‌پال (کد {$errorCode})",
        ];
    }

    public function verifyPayment(Invoice $invoice, string $status, ?string $authority): array
    {
        if ($status === 'NOK' || $status === 'cancel') {
            return ['success' => false, 'message' => 'پرداخت توسط شما لغو شد.'];
        }

        if (! $authority || $authority !== $invoice->authority) {
            Log::warning('SubscriptionPaymentService: عدم تطابق authority در کال‌بک', [
                'invoice_id' => $invoice->id,
                'expected' => $invoice->authority,
                'received' => $authority,
            ]);

            return ['success' => false, 'message' => 'اطلاعات تراکنش نامعتبر است.'];
        }

        $result = $this->driver()->verify(new GatewayVerifyRequest(
            (int) ((float) $invoice->amount * 10),
            ['Authority' => $authority, 'Status' => $status],
        ));

        \App\Models\PaymentTransaction::where('purpose', 'subscription')
            ->where('payable_type', $invoice->getMorphClass())->where('payable_id', $invoice->id)
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

        if ($result->raw === []) {
            Log::error('SubscriptionPaymentService: خطای اتصال هنگام تأیید پرداخت اشتراک', ['invoice_id' => $invoice->id]);

            return ['success' => false, 'message' => 'خطا در تأیید پرداخت.'];
        }

        $errorCode = $result->raw['data']['code'] ?? $result->raw['errors']['code'] ?? -999;
        Log::warning('SubscriptionPaymentService: تأیید پرداخت اشتراک ناموفق', [
            'invoice_id' => $invoice->id,
            'authority' => $authority,
            'error_code' => $errorCode,
        ]);

        return ['success' => false, 'message' => "پرداخت تأیید نشد (کد {$errorCode})."];
    }
}
