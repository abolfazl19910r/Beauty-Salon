<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ⭐ فاز ۲ از ۲، محور «۱. پرداخت آنلاین و صورتحساب». اتصال واقعی به درگاه زرین‌پال برای خرید/
 * تمدید آنلاین اشتراک سالن — جایگزین ثبت دستی سوپر ادمین (که همچنان به‌عنوان یک مسیر موازی، از
 * طریق InvoiceService::recordManualRenewal(), باقی می‌ماند).
 *
 * ⚠️ عمداً یک سرویس جدا از PaymentService (هم‌الگو با جداسازی موجود ZarinpalPayoutService): اینجا
 * جهت پول برعکسِ PaymentService است — سالن به پلتفرم پول اشتراک پرداخت می‌کند، نه مشتری به سالن.
 * پس این سرویس همیشه merchant_id سراسری پلتفرم را مستقیم از config می‌خواند و هرگز از
 * CurrentSalon/salons.zarinpal_merchant_id (مورد ۹) استفاده نمی‌کند — برخلاف PaymentService که
 * دقیقاً برعکس این کار را انجام می‌دهد.
 */
class SubscriptionPaymentService
{
    protected string $merchantId;

    protected string $apiUrl;

    protected string $gatewayUrl;

    protected bool $sandbox;

    public function __construct()
    {
        $this->merchantId = config('services.zarinpal.merchant_id');
        $this->sandbox = config('services.zarinpal.sandbox', true);

        if ($this->sandbox) {
            $this->apiUrl = 'https://sandbox.zarinpal.com/pg/v4/payment';
            $this->gatewayUrl = 'https://sandbox.zarinpal.com/pg/StartPay';
        } else {
            $this->apiUrl = 'https://api.zarinpal.com/pg/v4/payment';
            $this->gatewayUrl = 'https://www.zarinpal.com/pg/StartPay';
        }
    }

    /**
     * @return array{success: bool, payment_url?: string, reference?: string, message?: string}
     */
    public function createPayment(Invoice $invoice): array
    {
        try {
            $callbackUrl = route('admin.billing.callback', ['invoice' => $invoice->id]);
            $amount = (int) ($invoice->amount * 10);

            $requestData = [
                'merchant_id' => $this->merchantId,
                'amount' => $amount,
                'callback_url' => $callbackUrl,
                'description' => sprintf(
                    'خرید/تمدید اشتراک سالن «%s» (%s)',
                    $invoice->salon->name,
                    $invoice->subscription_type
                ),
                'metadata' => [
                    'mobile' => $invoice->createdBy?->phone ?? '',
                ],
            ];

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->apiUrl.'/request.json', $requestData);

            $result = $response->json();

            if ($response->successful() && isset($result['data']['code']) && $result['data']['code'] == 100) {
                $authority = $result['data']['authority'];
                $invoice->update(['authority' => $authority]);

                return [
                    'success' => true,
                    'payment_url' => $this->gatewayUrl.'/'.$authority,
                    'reference' => $authority,
                ];
            }

            $errorCode = $result['data']['code'] ?? $result['errors']['code'] ?? -999;
            $errorMessage = $result['errors']['message'] ?? "خطای زرین‌پال (کد {$errorCode})";

            Log::error('SubscriptionPaymentService: درخواست پرداخت اشتراک ناموفق', [
                'invoice_id' => $invoice->id,
                'error_code' => $errorCode,
                'response' => $result,
            ]);

            return [
                'success' => false,
                'message' => $errorMessage,
            ];
        } catch (\Throwable $e) {
            Log::error('SubscriptionPaymentService: خطای اتصال به درگاه پرداخت اشتراک', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'خطا در اتصال به درگاه پرداخت. لطفاً دوباره تلاش کنید.',
            ];
        }
    }

    /**
     * @return array{success: bool, ref_id?: string, message?: string}
     */
    public function verifyPayment(Invoice $invoice, string $status, ?string $authority): array
    {
        if ($status === 'NOK' || $status === 'cancel') {
            return [
                'success' => false,
                'message' => 'پرداخت توسط شما لغو شد.',
            ];
        }

        // ⭐ اعتبار authority همیشه از رکورد سمت سرور (همان چیزی که createPayment() ذخیره کرده)
        // خوانده می‌شود، نه از پارامتر querystring به‌تنهایی — پارامتر querystring فقط باید با آن
        // مطابقت داشته باشد، وگرنه یک کاربر می‌تواند authority دلخواه را در URL جایگزین کند.
        if (! $authority || $authority !== $invoice->authority) {
            Log::warning('SubscriptionPaymentService: عدم تطابق authority در کال‌بک', [
                'invoice_id' => $invoice->id,
                'expected' => $invoice->authority,
                'received' => $authority,
            ]);

            return [
                'success' => false,
                'message' => 'اطلاعات تراکنش نامعتبر است.',
            ];
        }

        try {
            $amount = (int) ($invoice->amount * 10);

            $requestData = [
                'merchant_id' => $this->merchantId,
                'authority' => $authority,
                'amount' => $amount,
            ];

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->apiUrl.'/verify.json', $requestData);

            $result = $response->json();

            if ($response->successful() && isset($result['data']['code'])) {
                $code = $result['data']['code'];
                if ($code == 100 || $code == 101) {
                    return [
                        'success' => true,
                        'ref_id' => (string) ($result['data']['ref_id'] ?? $authority),
                    ];
                }
            }

            $errorCode = $result['data']['code'] ?? $result['errors']['code'] ?? -999;

            Log::warning('SubscriptionPaymentService: تأیید پرداخت اشتراک ناموفق', [
                'invoice_id' => $invoice->id,
                'authority' => $authority,
                'error_code' => $errorCode,
            ]);

            return [
                'success' => false,
                'message' => "پرداخت تأیید نشد (کد {$errorCode}).",
            ];
        } catch (\Throwable $e) {
            Log::error('SubscriptionPaymentService: خطای اتصال هنگام تأیید پرداخت اشتراک', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'خطا در تأیید پرداخت.',
            ];
        }
    }
}
