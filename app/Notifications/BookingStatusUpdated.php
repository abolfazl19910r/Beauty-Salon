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

    private function getSmsMessage($notifiable): string
    {
        $persianDate = verta($this->booking->booking_time)->format('Y/m/d');
        $persianTime = verta($this->booking->booking_time)->format('H:i');

        if ($this->status === 'completed') {
            $amountLabel = "مبلغ کل";
            $amountValue = number_format($this->booking->service->price);
        } else {
            $amountLabel = "پیش‌پرداخت";
            $amountValue = number_format($this->booking->prepayment_amount);
        }

        $baseInfo = sprintf(
            "\n👤 متخصص: %s\n💇 سرویس: %s\n📅 تاریخ: %s\n⏰ زمان: %s\n💰 %s: %s تومان\n🔢 پیگیری: #%s\n🏠 آدرس: تهران، خیابان ... ",
            $this->booking->specialist->name,
            $this->booking->service->name,
            $persianDate,
            $persianTime,
            $amountLabel,
            $amountValue,
            $this->booking->id
        );

        return match ($this->status) {
            'completed' => "سلام {$notifiable->name} عزیز، نوبت شما انجام شد و به پایان رسید." . $baseInfo . "\n✔️ از اینکه ما را انتخاب کردید سپاسگزاریم. لطفاً نظر خود را ثبت کرده و ما را به دوستانتان معرفی کنید.🌹",
            'confirmed' => "سلام {$notifiable->name}، نوبت شما تایید شد." . $baseInfo . "\n✅ لطفا ۱۵ دقیقه زودتر در محل حضور داشته باشید.",
            'pending_specialist' => "سلام {$notifiable->name}، نوبت شما با موفقیت ثبت شد و در انتظار تایید نهایی متخصص است. نتیجه به زودی اطلاع‌رسانی می‌شود." . $baseInfo,
            'cancelled' => "سلام {$notifiable->name}، نوبت شما لغو شد." . $baseInfo . "\n❌ دلیل: " . ($this->reason ?? 'ذکر نشده'),
            default => "سلام {$notifiable->name}، وضعیت نوبت شما به " . $this->status . " تغییر یافت." . $baseInfo
        };
    }

    private function getDatabaseMessage(): string
    {
        return match ($this->status) {
            'paid' => 'رزرو شما با موفقیت پرداخت شد.',
            'confirmed' => 'نوبت شما توسط متخصص تایید شد.',
            'completed' => 'خدمات شما با موفقیت انجام شد. لطفاً نظر خود را ثبت کنید.',
            'cancelled' => 'نوبت شما لغو شد.',
            default => 'وضعیت نوبت تغییر کرد.'
        };
    }
}
