<?php
namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SecurePaymentService
{
    public function createPayment($booking): Payment
    {
        $referenceId = $this->generateSecureReference();

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount' => $booking->prepayment_amount,
            'reference_id' => $referenceId,
            'card_data' => $this->encryptSensitiveData([
                'amount' => $booking->prepayment_amount,
                'user_id' => $booking->user_id,
                'timestamp' => now()->timestamp
            ])
        ]);

        Log::info('Payment initiated', [
            'payment_id' => $payment->id,
            'booking_id' => $booking->id,
            'reference' => $referenceId
        ]);

        return $payment;
    }

    public function verifyPayment($referenceId, $amount): bool
    {
        $payment = Payment::where('reference_id', $referenceId)->first();

        if (!$payment) {
            Log::warning('Payment verification failed - Payment not found', [
                'reference' => $referenceId
            ]);
            return false;
        }

        $decryptedData = $this->decryptSensitiveData($payment->card_data);

        if ($decryptedData['amount'] !== $amount) {
            Log::warning('Payment verification failed - Amount mismatch', [
                'payment_id' => $payment->id,
                'expected' => $decryptedData['amount'],
                'received' => $amount
            ]);
            return false;
        }

        if (now()->timestamp - $decryptedData['timestamp'] > 900) {
            Log::warning('Payment verification failed - Time expired', [
                'payment_id' => $payment->id
            ]);
            return false;
        }

        Log::info('Payment verified successfully', [
            'payment_id' => $payment->id,
            'reference' => $referenceId
        ]);

        return true;
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
