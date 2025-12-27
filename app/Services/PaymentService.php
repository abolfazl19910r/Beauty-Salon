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

    public function createPayment($booking, $customAmount = null): array
    {
        try {
            $callbackUrl = route('payment.callback', ['booking' => $booking->id]);
            $paymentAmount = $customAmount ?? $booking->prepayment_amount;
            $amount = (int) ($paymentAmount * 10);

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

                return [
                    'success' => true,
                    'payment_url' => $paymentUrl,
                    'reference' => $authority
                ];
            }

            $errorCode = $result['data']['code'] ?? $result['errors']['code'] ?? -999;
            $errorMessage = $this->getZarinpalError($errorCode);

            Log::error('❌ Payment Request Failed', [
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
            Log::error('💥 Payment Creation Exception', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در اتصال به درگاه پرداخت. لطفاً دوباره تلاش کنید.'
            ];
        }
    }

    public function verifyPayment($request): array
    {
        try {
            $authority = $request->Authority ?? $request->authority;
            $status = $request->Status ?? $request->status;

            if ($status === 'NOK' || $status === 'cancel') {
                Log::warning('⚠️ Payment Cancelled by User', ['authority' => $authority]);
                return [
                    'success' => false,
                    'message' => 'پرداخت توسط کاربر لغو شد'
                ];
            }

            $bookingId = $request->booking ?? null;

            if (!$bookingId) {
                Log::error('❌ Booking ID not found in callback');
                return [
                    'success' => false,
                    'message' => 'شناسه رزرو یافت نشد'
                ];
            }

            $booking = \App\Models\Booking::findOrFail($bookingId);
            $partialPayment = session('partial_payment_' . $booking->id);
            $verifyAmount = $partialPayment
                ? $partialPayment['remaining_amount']
                : $booking->prepayment_amount;

            $amount = (int) ($verifyAmount * 10);

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
                        'booking_id' => $booking->id,
                        'reference' => $authority,
                        'ref_id' => $refId,
                        'card_pan' => $result['data']['card_pan'] ?? null,
                        'fee' => $result['data']['fee'] ?? null
                    ];
                }
            }

            $errorCode = $result['data']['code'] ?? $result['errors']['code'] ?? -999;
            $errorMessage = $this->getZarinpalError($errorCode);

            Log::warning('⚠️ Payment Verification Failed', [
                'booking_id' => $booking->id,
                'authority' => $authority,
                'error_code' => $errorCode,
                'message' => $errorMessage
            ]);

            return [
                'status' => 'failed',
                'success' => false,
                'booking_id' => $booking->id,
                'message' => $errorMessage
            ];

        } catch (\Exception $e) {
            Log::error('💥 Payment Verification Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => 'failed',
                'success' => false,
                'message' => 'خطا در تایید پرداخت'
            ];
        }
    }

    public function createWalletChargePayment($user, $amount): array
    {
        try {
            $callbackUrl = route('wallet.charge.callback');
            $amountInRials = (int) ($amount * 10);

            $requestData = [
                'merchant_id' => $this->merchantId,
                'amount' => $amountInRials,
                'callback_url' => $callbackUrl,
                'description' => sprintf('شارژ کیف پول - کاربر: %s', $user->name ?? $user->phone),
                'metadata' => [
                    'mobile' => $user->phone ?? '',
                    'email' => $user->email ?? ''
                ]
            ];

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->apiUrl . '/request.json', $requestData);

            $result = $response->json();

            if ($response->successful() && isset($result['data']['code']) && $result['data']['code'] == 100) {
                $authority = $result['data']['authority'];
                $paymentUrl = $this->gatewayUrl . '/' . $authority;

                return [
                    'success' => true,
                    'payment_url' => $paymentUrl,
                    'reference' => $authority
                ];
            }

            $errorCode = $result['data']['code'] ?? $result['errors']['code'] ?? -999;
            $errorMessage = $this->getZarinpalError($errorCode);

            Log::error('❌ Wallet Charge Request Failed', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error_code' => $errorCode,
                'message' => $errorMessage,
            ]);

            return [
                'success' => false,
                'message' => $errorMessage
            ];

        } catch (\Exception $e) {
            Log::error('💥 Wallet Charge Creation Exception', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'خطا در اتصال به درگاه پرداخت.'
            ];
        }
    }

    public function verifyWalletChargePayment($request, $expectedAmount): array
    {
        try {
            $authority = $request->Authority ?? $request->authority;
            $status = $request->Status ?? $request->status;

            if ($status === 'NOK' || $status === 'cancel') {
                return [
                    'success' => false,
                    'message' => 'پرداخت توسط کاربر لغو شد'
                ];
            }

            $amount = (int) ($expectedAmount * 10);

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
                    return [
                        'success' => true,
                        'ref_id' => $result['data']['ref_id'] ?? $authority,
                        'card_pan' => $result['data']['card_pan'] ?? null,
                    ];
                }
            }

            $errorCode = $result['data']['code'] ?? $result['errors']['code'] ?? -999;
            $errorMessage = $this->getZarinpalError($errorCode);

            return [
                'success' => false,
                'message' => $errorMessage
            ];

        } catch (\Exception $e) {
            Log::error('💥 Wallet Charge Verification Exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'خطا در تایید پرداخت'
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
            -52 => 'خطایی غیرمنتظره سرور رخ داده است. لطفا مشکل را به امور مشتریان زرین‌پال اطلاع دهید',
            -53 => 'پذیرنده در وب سرویس تسهیم شریک نیست',
            -54 => 'درخواست مورد نظر آرشیو شده است',
            100 => 'عملیات با موفقیت انجام شد',
            101 => 'عملیات پرداخت موفق بوده و قبلاً PaymentVerification تراکنش انجام شده است',
            default => 'خطایی نامشخص در درگاه پرداخت (کد: ' . $code . ')'
        };
    }
}
