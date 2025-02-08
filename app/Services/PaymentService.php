<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected mixed $merchantId;
    protected string $baseUrl;
    protected bool $sandbox;

    public function __construct()
    {
        $this->merchantId = config('services.zarinpal.merchant_id');
        $this->sandbox = config('services.zarinpal.sandbox', true);
        $this->baseUrl = $this->sandbox
            ? 'https://sandbox.zarinpal.com/pg/rest/WebGate'
            : 'https://api.zarinpal.com/pg/v4';
    }

    public function createPayment($booking): array
    {
        try {
            Log::info('Creating payment for booking', ['booking_id' => $booking->id]);

            $response = Http::post($this->baseUrl . '/PaymentRequest.json', [
                'MerchantID' => $this->merchantId,
                'Amount' => $booking->prepayment_amount / 10,
                'CallbackURL' => route('payment.callback', $booking->id),
                'Description' => sprintf('پیش پرداخت نوبت سالن زیبایی - شماره %d', $booking->id),
                'Email' => $booking->user->email,
                'Mobile' => $booking->user->phone
            ]);

            $result = $response->json();

            if ($response->successful() && $result['Status'] == 100) {
                $paymentUrl = $this->sandbox
                    ? "https://sandbox.zarinpal.com/pg/StartPay/{$result['Authority']}"
                    : "https://www.zarinpal.com/pg/StartPay/{$result['Authority']}";

                Log::info('Payment created successfully', [
                    'booking_id' => $booking->id,
                    'authority' => $result['Authority']
                ]);

                return [
                    'success' => true,
                    'payment_url' => $paymentUrl,
                    'reference' => $result['Authority']
                ];
            }

            Log::error('Error creating payment', [
                'booking_id' => $booking->id,
                'status' => $result['Status'] ?? 'unknown',
                'message' => $result['Message'] ?? 'Unknown error'
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ایجاد تراکنش: ' . ($result['Message'] ?? 'خطای ناشناخته')
            ];

        } catch (\Exception $e) {
            Log::error('Exception in payment creation', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function verifyPayment($authority, $amount): array
    {
        try {
            Log::info('Verifying payment', ['authority' => $authority]);

            $response = Http::post($this->baseUrl . '/PaymentVerification.json', [
                'MerchantID' => $this->merchantId,
                'Authority' => $authority,
                'Amount' => $amount / 10
            ]);

            $result = $response->json();

            if ($response->successful() && $result['Status'] == 100) {
                Log::info('Payment verified successfully', [
                    'authority' => $authority,
                    'ref_id' => $result['RefID']
                ]);

                return [
                    'success' => true,
                    'reference' => $authority,
                    'transaction_id' => $result['RefID']
                ];
            }

            Log::warning('Payment verification failed', [
                'authority' => $authority,
                'status' => $result['Status'] ?? 'unknown',
                'message' => $this->getZarinpalError($result['Status'] ?? 0)
            ]);

            return [
                'success' => false,
                'message' => $this->getZarinpalError($result['Status'] ?? 0)
            ];

        } catch (\Exception $e) {
            Log::error('Exception in payment verification', [
                'authority' => $authority,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function getZarinpalError($status): string
    {
        return match ($status) {
            -1 => 'اطلاعات ارسال شده ناقص است',
            -2 => 'IP و یا مرچنت کد پذیرنده صحیح نیست',
            -3 => 'با توجه به محدودیت های شاپرک امکان پرداخت با رقم درخواست شده میسر نمی باشد',
            -4 => 'سطح تایید پذیرنده پایین تر از سطح نقره ای است',
            -11 => 'درخواست مورد نظر یافت نشد',
            -12 => 'امکان ویرایش درخواست میسر نمی باشد',
            -21 => 'هیچ نوع عملیات مالی برای این تراکنش یافت نشد',
            -22 => 'تراکنش ناموفق می باشد',
            -33 => 'رقم تراکنش با رقم پرداخت شده مطابقت ندارد',
            -34 => 'سقف تقسیم تراکنش از لحاظ تعداد یا رقم عبور نموده است',
            -40 => 'اجازه دسترسی به متد مربوطه وجود ندارد',
            -41 => 'اطلاعات ارسال شده مربوط به AdditionalData غیرمعتبر می باشد',
            -42 => 'مدت زمان معتبر طول عمر شناسه پرداخت باید بین 30 دقیقه تا 45 روز می باشد',
            -54 => 'درخواست مورد نظر آرشیو شده است',
            101 => 'عملیات پرداخت موفق بوده و قبلا PaymentVerification تراکنش انجام شده است',
            default => 'خطای ناشناخته'
        };
    }
}
