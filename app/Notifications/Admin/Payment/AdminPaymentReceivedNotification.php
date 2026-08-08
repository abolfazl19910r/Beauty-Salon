<?php

namespace App\Notifications\Admin\Payment;

use App\Models\Booking;
use App\Services\SMSService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AdminPaymentReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private Booking $booking;

    private SMSService $smsService;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
        $this->smsService = new SMSService;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'sms'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_received_admin',
            'booking_id' => $this->booking->id,
            'message' => sprintf(
                'پرداخت %s تومان (%s) برای نوبت %s با %s ثبت شد. کد پیگیری: %s',
                number_format($this->amount()),
                $this->methodLabel(),
                $this->customerName(),
                $this->specialistName(),
                $this->referenceCode(),
            ),
            'amount' => $this->amount(),
            'method' => $this->methodKey(),
            'reference_code' => $this->referenceCode(),
            'customer_name' => $this->customerName(),
            'specialist_name' => $this->specialistName(),
            'link' => route('admin.bookings.show', $this->booking->id, false),
        ];
    }

    public function toSms(object $notifiable): bool
    {
        $message = sprintf(
            "💰 پرداخت جدید ثبت شد\nمبلغ: %s تومان\nروش: %s\nمشتری: %s\nمتخصص: %s\nکد پیگیری: %s\nنوبت #%s",
            number_format($this->amount()),
            $this->methodLabel(),
            $this->customerName(),
            $this->specialistName(),
            $this->referenceCode(),
            $this->booking->id,
        );

        return $this->smsService->send($notifiable->phone, $message);
    }

    private function amount(): float
    {
        return (float) ($this->booking->prepayment_amount ?? 0);
    }

    private function referenceCode(): string
    {
        return $this->booking->payment_reference ?? 'نامشخص';
    }

    private function customerName(): string
    {
        return $this->booking->user->name ?? 'نامشخص';
    }

    private function specialistName(): string
    {
        return $this->booking->specialist->name ?? 'نامشخص';
    }

    private function methodKey(): string
    {
        return $this->booking->payment_details['method'] ?? 'unknown';
    }

    private function methodLabel(): string
    {
        return match ($this->methodKey()) {
            'full_discount' => 'تخفیف کامل',
            'wallet' => 'کیف‌پول',
            'wallet_gateway' => 'کیف‌پول + درگاه',
            'gateway' => 'درگاه بانکی',
            default => 'نامشخص',
        };
    }
}
