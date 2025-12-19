<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Services\SMSService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingStatusUpdated extends Notification
{

    protected Booking $booking;
    protected string $status;
    protected ?string $reason;
    protected SMSService $smsService;

    public function __construct(Booking $booking, string $status, ?string $reason = null)
    {
        $this->booking = $booking;
        $this->status = $status;
        $this->reason = $reason;
        $this->smsService = new SMSService();
    }

    public function via($notifiable): array
    {
        return ['database', 'sms'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'status' => $this->status,
            'message' => $this->getDatabaseMessage(),
            'created_at' => now(),
        ];
    }

    public function toSms($notifiable): bool
    {
        $message = $this->getSmsMessage($notifiable);
        return $this->smsService->send($notifiable->phone, $message);
    }

    private function getSmsMessage($user): string
    {
        $persianDate = verta($this->booking->booking_time)->format('Y/m/d');
        $persianTime = verta($this->booking->booking_time)->format('H:i');

        $baseInfo = sprintf(
            "\n👤 متخصص: %s\n💇 سرویس: %s\n📅 تاریخ: %s\n⏰ زمان: %s\n💳 پیش‌پرداخت: %s تومان\n🔢 پیگیری: #%s\n📍 آدرس: %s",
            $this->booking->specialist->name,
            $this->booking->service->name,
            $persianDate,
            $persianTime,
            number_format($this->booking->prepayment_amount),
            $this->booking->id,
            "تهران، خیابان ..."
        );

        return match ($this->status) {
            'confirmed' => "سلام {$user->name}، نوبت شما تایید شد." . $baseInfo . "\n✅ لطفا ۱۵ دقیقه زودتر تشریف بیاورید.",
            'pending_specialist' => "سلام {$user->name}، نوبت شما ثبت شد و منتظر تایید متخصص است." . $baseInfo,
            'cancelled' => "سلام {$user->name}، نوبت شما لغو شد." . $baseInfo . "\n❌ دلیل: " . ($this->reason ?? 'ذکر نشده'),
            default => "وضعیت نوبت شما تغییر کرد: " . $this->status
        };
    }

    private function getDatabaseMessage(): string
    {
        return match ($this->status) {
            'paid' => 'رزرو شما با موفقیت پرداخت شد.',
            'confirmed' => 'نوبت شما توسط متخصص تایید شد.',
            'cancelled' => 'نوبت شما لغو شد.',
            default => 'وضعیت نوبت تغییر کرد.'
        };
    }
}
