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

    /** درگاه پلتفرم؛ null = مرچنت پلتفرم تنظیم نشده (درگاه واقعی) — App\Support\PlatformZarinpal */
    protected function driver(): ?ZarinpalDriver
    {
        return \App\Support\PlatformZarinpal::driver();
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
            // قسط بعدی فاکتور (۲۰۲۶-۱۰-۳۱): زیر سقف ۱۰۰ میلیون تومانی هر پرداخت زرین‌پال، منهای اعتبار پلتفرم
            'installment' => app(InvoiceInstallments::class)->nextNumber($invoice),
            'amount_rial' => app(InvoiceInstallments::class)->nextAmount($invoice) * 10,
            'callback_url' => route('admin.billing.callback', ['invoice' => $invoice->id]),
        ]);
        $part = InvoiceInstallments::label($invoice);

        $driver = $this->driver();
        $result = $driver === null ? \App\Payments\GatewayStartResult::failed(\App\Support\PlatformZarinpal::MISSING_MESSAGE, retryable: false, raw: ['platform_merchant_missing' => true]) : $driver->start(new GatewayStartRequest(
            $transaction->amount_rial,
            \App\Payments\PaymentReturnUrl::for($transaction->public_id, null), // درگاه پلتفرم ← دامنه‌ی مرکزی (بسته‌ی 5ز)
            sprintf('خرید/تمدید اشتراک سالن «%s» (%s)', $invoice->salon->name, $invoice->subscription_type).($part !== '' ? " — فاکتور {$invoice->id} — {$part}" : ''),
            $invoice->createdBy?->phone ?? '',
            orderId: 'INV-'.$invoice->id,
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

        $errorCode = ZarinpalDriver::errorCode((array) $result->raw);
        Log::error('SubscriptionPaymentService: درخواست پرداخت اشتراک ناموفق', [
            'invoice_id' => $invoice->id,
            'error_code' => $errorCode,
            'response' => $result->raw,
        ]);

        return [
            'success' => false,
            // کد فقط وقتی زرین‌پال واقعاً کدی داده (سقف مبلغ و مرچنت نامشخص پیش از درخواست رد می‌شوند و کدی ندارند)
            'message' => ($result->message ?? ZarinpalDriver::errorMessage($errorCode)).($errorCode !== -999 ? " (کد {$errorCode})" : ''),
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

        // مبلغ همین قسط از دفتر تراکنش (نه کل فاکتور)
        $transaction = \App\Models\PaymentTransaction::where('purpose', 'subscription')
            ->where('payable_type', $invoice->getMorphClass())->where('payable_id', $invoice->id)->where('token', $authority)->first();
        $driver = $this->driver();
        $result = $driver === null
            ? new \App\Payments\GatewayVerifyResult(false, $authority, message: \App\Support\PlatformZarinpal::MISSING_MESSAGE, raw: ['platform_merchant_missing' => true])
            : $driver->verify(new GatewayVerifyRequest(
                $transaction ? (int) $transaction->amount_rial : (int) ((float) $invoice->amount * 10),
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
                // ⭐ بسته‌ی 5ز: زرین‌پال ‎-50/‎-52 ← «پرداخت‌های نیازمند بررسی» ابرمدیر
                'needs_attention' => ! $result->success && ($result->raw['needs_attention'] ?? false) === true,
                'updated_at' => now(),
            ]);

        if (! $result->success && ($result->raw['needs_attention'] ?? false) === true) {
            $flagged = \App\Models\PaymentTransaction::where('purpose', 'subscription')->where('token', $authority)->value('id');
            if ($flagged) {
                app(\App\Services\Admin\AttentionNotifier::class)->paymentFlagged((int) $flagged);
            }
        }

        if ($result->success) {
            return ['success' => true, 'ref_id' => (string) $result->refId, 'toman' => intdiv((int) ($transaction?->amount_rial ?? (int) $invoice->amount * 10), 10), 'transaction' => $transaction];
        }

        if ($result->raw === []) {
            Log::error('SubscriptionPaymentService: خطای اتصال هنگام تأیید پرداخت اشتراک', ['invoice_id' => $invoice->id]);

            return ['success' => false, 'message' => 'خطا در تأیید پرداخت.'];
        }

        $errorCode = ZarinpalDriver::errorCode((array) $result->raw);
        Log::warning('SubscriptionPaymentService: تأیید پرداخت اشتراک ناموفق', [
            'invoice_id' => $invoice->id,
            'authority' => $authority,
            'error_code' => $errorCode,
        ]);

        return ['success' => false, 'message' => 'پرداخت تأیید نشد: '.($result->message ?? ZarinpalDriver::errorMessage($errorCode))." (کد {$errorCode})."];
    }
}
