<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefundService
{
    public function __construct(protected readonly SMSService $smsService, protected readonly PaymentService $paymentService)
    {
    }

    public function processRefund(Booking $booking): bool
    {
        try {
            DB::beginTransaction();

            $refund = Payment::create([
                'booking_id' => $booking->id,
                'amount' => $booking->prepayment_amount,
                'reference_id' => 'REF-' . uniqid(),
                'status' => 'processing',
                'payment_details' => [
                    'original_payment_id' => $booking->payment->id,
                    'refund_reason' => 'booking_cancelled',
                    'refund_by' => auth()->id()
                ]
            ]);

            $result = $this->paymentService->refund([
                'reference' => $booking->payment->gateway_reference,
                'amount' => $booking->prepayment_amount,
                'reason' => 'Booking Cancelled'
            ]);

            if ($result['success']) {
                $refund->update([
                    'status' => 'completed',
                    'gateway_reference' => $result['refund_reference'],
                    'gateway_response' => $result
                ]);

                $booking->update([
                    'refund_status' => 'refunded',
                    'refunded_at' => now()
                ]);

                $this->smsService->send(
                    $booking->user->phone,
                    sprintf(
                        'مبلغ %s تومان بابت لغو نوبت شماره %s به حساب شما برگشت داده شد.',
                        number_format($booking->prepayment_amount),
                        $booking->id
                    )
                );

                Log::info('Refund processed successfully', [
                    'booking_id' => $booking->id,
                    'amount' => $booking->prepayment_amount,
                    'refund_reference' => $result['refund_reference']
                ]);

                DB::commit();
                return true;
            }

            throw new \Exception($result['message'] ?? 'خطا در برگشت وجه');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Refund processing failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);

            $this->createSupportTicket($booking, $e->getMessage());

            return false;
        }
    }

    protected function createSupportTicket($booking, $error): void
    {
        SupportTicket::create([
            'title' => 'خطا در برگشت وجه نوبت' . $booking->id,
            'description' => "خطا در برگشت وجه نوبت {$booking->id}\n" .
                "مبلغ: {$booking->prepayment_amount}\n" .
                "خطا: {$error}",
            'priority' => 'high',
            'status' => 'open',
            'category' => 'refund_error',
            'user_id' => $booking->user_id
        ]);
    }
}
