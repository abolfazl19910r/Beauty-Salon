<?php

namespace App\Notifications\Booking;

use App\Models\Booking;
use App\Services\SMSService;
use Illuminate\Notifications\Notification;

class SpecialistBookingCancelledNotification extends Notification
{

    protected Booking $booking;
    protected SMSService $smsService;
    protected string $cancelledBy;

    public function __construct(Booking $booking, string $cancelledBy)
    {
        $this->booking = $booking;
        $this->cancelledBy = $cancelledBy;
        $this->smsService = new SMSService();
    }

    /**
     * R-Observers addendum: previously included 'sms' here too, contradicting
     * SendBookingCancellationNotifications' own docblock, which says SMS is deliberately NOT
     * sent from this notification because BookingObserver::sendCancellationSMS() already sends
     * a cancellation SMS to the specialist for every cancellation. With 'sms' still declared
     * here, the specialist would receive two cancellation SMS messages per cancellation once
     * the Kavenegar account's line-permission issue (an external/account-level problem, not a
     * code bug — see 'استفاده از این خط نیازمند ایجاد سطح دسترسی می‌باشد' in the logs) is
     * resolved; right now both attempts happen to fail with that same external error, which
     * was masking the duplication.
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $canceller = $this->cancellerLabel();

        return [
            'booking_id' => $this->booking->id,
            'message' => "نوبت توسط {$canceller} لغو شد.",
            'service_name' => $this->booking->service->name,
            'canceller' => $this->cancelledBy,
        ];
    }

    public function toSms($notifiable): bool
    {
        $persianDate = verta($this->booking->booking_time)->format('Y/m/d');
        $persianTime = verta($this->booking->booking_time)->format('H:i');
        $canceller = $this->cancellerLabel();

        $message = sprintf(
            "%s عزیز، سلام 👋\n\n❌ هشدار لغو نوبت.\n\n👤 مشتری: %s\n📞 تماس: %s\n📋 سرویس: %s\n📅 تاریخ: %s - ساعت %s\n\n📝 لغو کننده: %s",
            $notifiable->name,
            $this->booking->user->name,
            $this->booking->user->phone,
            $this->booking->service->name,
            $persianDate,
            $persianTime,
            $canceller
        );

        return $this->smsService->send($notifiable->phone, $message);
    }

    /**
     * R-Observers addendum: previously anything other than 'user'/'system' (i.e. the real
     * 'specialist'/'admin' values used by BookingCancelled/cancelled_by) fell into a vague
     * 'ادمین یا متخصص' ("admin or specialist") default — meaning a specialist cancelling their
     * own booking got a notification saying it was cancelled by "admin or specialist" instead of
     * clearly attributing it. Each real cancelledBy value now gets its own label.
     */
    private function cancellerLabel(): string
    {
        return match ($this->cancelledBy) {
            'user' => 'مشتری',
            'specialist' => 'متخصص',
            'admin' => 'مدیر سالن',
            'system' => 'سیستم (مانند پرداخت ناموفق)',
            default => 'نامشخص',
        };
    }
}
