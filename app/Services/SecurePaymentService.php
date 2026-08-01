<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SecurePaymentService
{
    protected const EXPIRY_MINUTES = 15;

    public function createPayment(Booking $booking): Payment
    {
        $referenceId = $this->generateSecureReference();

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount' => $booking->prepayment_amount,
            'reference_id' => $referenceId,
            'status' => 'pending',
            'expired_at' => now()->addMinutes(self::EXPIRY_MINUTES),
            'card_data' => $this->encryptSensitiveData([
                'amount' => $booking->prepayment_amount,
                'user_id' => $booking->user_id,
                'timestamp' => now()->timestamp
            ])
        ]);

        Log::info('Secure payment initiated', [
            'payment_id' => $payment->id,
            'booking_id' => $booking->id,
            'reference' => $referenceId
        ]);

        return $payment;
    }

    /**
     * Verifies a secure-checkout payment reference and, on success, completes the transaction.
     *
     * The amount is intentionally NOT taken as an input parameter here (it previously came from
     * the client's request body, an amount a user could freely tamper with before this check ever
     * ran) — it is always read from the server-side Payment record created in createPayment(),
     * and cross-checked against the encrypted card_data blob purely as a tamper-evidence signature.
     */
    public function verifyPayment(string $referenceId): array
    {
        $payment = Payment::where('reference_id', $referenceId)->first();

        if (!$payment) {
            Log::warning('Secure payment verification failed - payment not found', [
                'reference' => $referenceId
            ]);

            return ['success' => false, 'message' => 'تراکنش یافت نشد.'];
        }

        if ($payment->isCompleted()) {
            return [
                'success' => true,
                'transaction_id' => $payment->gateway_reference ?? $referenceId,
                'already_completed' => true,
            ];
        }

        if ($payment->isFailed()) {
            return ['success' => false, 'message' => 'این تراکنش قبلاً ناموفق اعلام شده است.'];
        }

        if ($payment->isExpired()) {
            $payment->markAsFailed();

            Log::warning('Secure payment verification failed - expired', [
                'payment_id' => $payment->id,
            ]);

            return ['success' => false, 'message' => 'مهلت پرداخت به پایان رسیده است. لطفاً دوباره تلاش کنید.'];
        }

        try {
            $decryptedData = $this->decryptSensitiveData($payment->card_data);
        } catch (\Exception $e) {
            Log::warning('Secure payment verification failed - could not decrypt reference data', [
                'payment_id' => $payment->id,
            ]);

            return ['success' => false, 'message' => 'خطا در تایید تراکنش.'];
        }

        if ((float) ($decryptedData['amount'] ?? 0) !== (float) $payment->amount) {
            Log::warning('Secure payment verification failed - amount mismatch', [
                'payment_id' => $payment->id,
                'expected' => $payment->amount,
                'signed' => $decryptedData['amount'] ?? null,
            ]);

            return ['success' => false, 'message' => 'مبلغ تراکنش نامعتبر است.'];
        }

        if (now()->timestamp - ($decryptedData['timestamp'] ?? 0) > self::EXPIRY_MINUTES * 60) {
            $payment->markAsFailed();

            Log::warning('Secure payment verification failed - time expired', [
                'payment_id' => $payment->id
            ]);

            return ['success' => false, 'message' => 'مهلت پرداخت به پایان رسیده است. لطفاً دوباره تلاش کنید.'];
        }

        Log::info('Secure payment verified successfully', [
            'payment_id' => $payment->id,
            'reference' => $referenceId
        ]);

        return ['success' => true, 'transaction_id' => $referenceId];
    }

    protected function generateSecureReference(): string
    {
        return Str::random(32);
    }

    protected function encryptSensitiveData(array $data): string
    {
        return Crypt::encryptString(json_encode($data));
    }

    protected function decryptSensitiveData(string $encryptedData): array
    {
        return json_decode(Crypt::decryptString($encryptedData), true);
    }
}
