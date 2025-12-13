<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
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

    public function createPayment($booking): array
    {
        try {
            Log::info('Creating payment for booking', [
                'booking_id' => $booking->id,
                'amount' => $booking->prepayment_amount
            ]);

            $callbackUrl = route('payment.callback', ['booking' => $booking->id]);
            $amount = (int) $booking->prepayment_amount;

            $requestData = [
                'merchant_id' => $this->merchantId,
                'amount' => $amount,
                'callback_url' => $callbackUrl,
                'description' => sprintf('پیش پرداخت نوبت سالن زیبایی - شماره %d', $booking->id),
                'metadata' => [
                    'mobile' => $booking->user->phone ?? '',
                    'email' => $booking->user->email ?? ''
                ]
            ];

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->apiUrl . '/request.json', $requestData);

            $statusCode = $response->status();
            $result = $response->json();

            if ($response->successful() && isset($result['data']['code']) && $result['data']['code'] == 100) {
                $authority = $result['data']['authority'];
                $paymentUrl = $this->gatewayUrl . '/' . $authority;

                Log::info('Payment created successfully', [
                    'booking_id' => $booking->id,
                    'authority' => $authority,
                    'payment_url' => $paymentUrl
                ]);

                return [
                    'success' => true,
                    'payment_url' => $paymentUrl,
                    'reference' => $authority
                ];
            }

            $errorCode = $result['data']['code'] ?? $result['errors']['code'] ?? -999;
            $errorMessage = $this->getZarinpalError($errorCode);

            Log::error('Error creating payment', [
                'booking_id' => $booking->id,
                'error_code' => $errorCode,
                'message' => $errorMessage,
                'full_response' => $result
            ]);

            return [
                'success' => false,
                'message' => $errorMessage
            ];

        } catch (\Exception $e) {
            Log::error('Exception in payment creation', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در اتصال به درگاه پرداخت. لطفا دوباره تلاش کنید.'
            ];
        }
    }

    public function verifyPayment(string $authority, int $amount): array
    {
        try {

            $requestData = [
                'merchant_id' => $this->merchantId,
                'authority' => $authority,
                'amount' => $amount
            ];

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->apiUrl . '/verify.json', $requestData);

            $result = $response->json();

            if ($response->successful() && isset($result['data']['code'])) {
                $code = $result['data']['code'];
                if ($code == 100 || $code == 101) {
                    $refId = $result['data']['ref_id'] ?? $authority;

                    return [
                        'status' => 'success',
                        'success' => true,
                        'reference' => $authority,
                        'transaction_id' => $refId,
                        'card_pan' => $result['data']['card_pan'] ?? null,
                        'fee' => $result['data']['fee'] ?? null
                    ];
                }
            }

            $errorCode = $result['data']['code'] ?? $result['errors']['code'] ?? -999;
            $errorMessage = $this->getZarinpalError($errorCode);

            Log::warning('Payment verification failed', [
                'authority' => $authority,
                'error_code' => $errorCode,
                'message' => $errorMessage
            ]);

            return [
                'status' => 'failed',
                'success' => false,
                'message' => $errorMessage
            ];

        } catch (\Exception $e) {
            Log::error('Exception in payment verification', [
                'authority' => $authority,
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'failed',
                'success' => false,
                'message' => 'خطا در تایید پرداخت'
            ];
        }
    }

    public function inquiry(string $authority): array
    {
        try {
            $requestData = [
                'merchant_id' => $this->merchantId,
                'authority' => $authority
            ];

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->apiUrl . '/inquiry.json', $requestData);

            $result = $response->json();

            if ($response->successful() && isset($result['data'])) {
                return [
                    'success' => true,
                    'data' => $result['data']
                ];
            }

            return [
                'success' => false,
                'message' => $result['errors']['message'] ?? 'خطا در استعلام'
            ];

        } catch (\Exception $e) {
            Log::error('Exception in inquiry', [
                'authority' => $authority,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در استعلام تراکنش'
            ];
        }
    }

    public function unVerifiedTransactions(): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->apiUrl . '/unVerified.json', [
                    'merchant_id' => $this->merchantId
                ]);

            $result = $response->json();

            if ($response->successful() && isset($result['data']['authorities'])) {
                return [
                    'success' => true,
                    'authorities' => $result['data']['authorities']
                ];
            }

            return [
                'success' => false,
                'message' => $result['errors']['message'] ?? 'خطا در دریافت لیست'
            ];

        } catch (\Exception $e) {
            Log::error('Exception in unVerifiedTransactions', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت تراکنش‌های تایید نشده'
            ];
        }
    }

    public function refund(string $refId, int $amount, string $description = ''): array
    {
        try {
            $requestData = [
                'merchant_id' => $this->merchantId,
                'ref_id' => $refId,
                'amount' => $amount,
                'description' => $description ?: 'بازگشت وجه'
            ];

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->apiUrl . '/refund.json', $requestData);

            $result = $response->json();

            if ($response->successful() && isset($result['data'])) {
                Log::info('Refund successful', [
                    'ref_id' => $refId,
                    'amount' => $amount
                ]);

                return [
                    'success' => true,
                    'data' => $result['data']
                ];
            }

            return [
                'success' => false,
                'message' => $result['errors']['message'] ?? 'خطا در بازگشت وجه'
            ];

        } catch (\Exception $e) {
            Log::error('Exception in refund', [
                'ref_id' => $refId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در بازگشت وجه'
            ];
        }
    }

    private function getZarinpalError(int $code): string
    {
        return match ($code) {
            -1 => 'اطلاعات ارسال شده ناقص است',
            -2 => 'IP و یا مرچنت کد پذیرنده صحیح نیست',
            -3 => 'با توجه به محدودیت های شاپرک امکان پرداخت با رقم درخواست شده میسر نمی باشد',
            -4 => 'سطح تایید پذیرنده پایین تر از سطح نقره ای است',
            -9 => 'آدرس IP درخواست کننده همگام با IP ثبت شده در وب سرویس نیست',
            -10 => 'توکن دسترسی غیرفعال شده است',
            -11 => 'درخواست مورد نظر یافت نشد',
            -12 => 'امکان ویرایش درخواست میسر نمی باشد',
            -15 => 'درگاه پرداخت به حالت تعلیق در آمده است',
            -16 => 'سطح تایید پذیرنده پایین تر از سطح نقره ای است',
            -17 => 'محدودیت پذیرنده در وضعیت حاضر، امکان پرداخت را محدود می کند',
            -21 => 'هیچ نوع عملیات مالی برای این تراکنش یافت نشد',
            -22 => 'تراکنش ناموفق می باشد',
            -30 => 'پذیرنده اجازه دسترسی به متد مربوطه را ندارد',
            -31 => 'حساب بانکی پذیرنده به درستی تعریف نشده است',
            -32 => 'مبلغ درخواستی از مبلغ کل تراکنش بیشتر است',
            -33 => 'رقم تراکنش با رقم پرداخت شده مطابقت ندارد',
            -34 => 'سقف تقسیم تراکنش از لحاظ تعداد یا رقم عبور نموده است',
            -40 => 'اجازه دسترسی به متد مربوطه وجود ندارد',
            -41 => 'اطلاعات ارسال شده مربوط به AdditionalData غیرمعتبر می باشد',
            -42 => 'مدت زمان معتبر طول عمر شناسه پرداخت باید بین 30 دقیقه تا 45 روز می باشد',
            -50 => 'مبلغ پرداخت شده با مقدار مبلغ در تراکنش همخوانی ندارد',
            -51 => 'پرداخت ناموفق بوده است',
            -52 => 'خطای غیرمنتظره سرور رخ داده است. لطفا مشکل را به امور مشتریان زرین‌پال اطلاع دهید',
            -53 => 'پذیرنده در وب سرویس تسهیم شریک نیست',
            -54 => 'درخواست مورد نظر آرشیو شده است',
            100 => 'عملیات با موفقیت انجام شد',
            101 => 'عملیات پرداخت موفق بوده و قبلاً PaymentVerification تراکنش انجام شده است',
            default => 'خطای نامشخص در درگاه پرداخت (کد: ' . $code . ')'
        };
    }
}
