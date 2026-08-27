<?php

namespace App\Notifications\Booking;

use App\Models\Booking;
use App\Services\SMSService;
use App\Support\Notifications\NotificationEvents;
use App\Traits\RespectsNotificationSettings;
use Illuminate\Notifications\Notification;

class BookingStatusUpdated extends Notification
{
    use RespectsNotificationSettings;

    protected Booking $booking;

    protected string $status;

    protected ?string $reason;

    protected SMSService $smsService;

    public function __construct(Booking $booking, string $status, ?string $reason = null)
    {
        $this->booking = $booking;
        $this->status = $status;
        $this->reason = $reason;
        $this->smsService = new SMSService;
    }

    /**
     * ⭐ Fix (item 2/3 from the notification-cost review):
     * - status='completed': previously this also sent 'sms' (either always, or — after an earlier
     *   fix — behind an admin-configurable toggle). Per explicit user decision, the separate
     *   "thank you" SMS has been removed entirely (not merely defaulted off, and no longer
     *   toggle-able), because ReviewService::sendReviewRequest()'s own message already says
     *   "با موفقیت انجام شد" (successfully completed) *and* includes the review link — merging both
     *   facts into a single message. Sending this notification's own duplicate "thank you" text on
     *   top of that was pure redundancy, from the very same BookingCompleted event/listener
     *   (SendBookingCompletionNotifications). Structurally hard-disabled (not settings-gated), same
     *   treatment as 'cancelled' below.
     * - status='cancelled': same duplicate-SMS bug — BookingObserver::sendCustomerCancellationSMS()
     *   already sends the cancellation SMS to the customer for every cancellation (regardless of who
     *   cancelled it); this class's own toSms() for 'cancelled' would fire a *second*, different-text
     *   cancellation SMS. The listener that calls ->notify() here (SendBookingCancellationNotifications)
     *   already documents this intent in its own comment — the code just never actually matched it.
     *   Structurally hard-disabled here (not settings-gated) since re-enabling it would always
     *   reintroduce the duplicate; the real cancellation-SMS toggle lives on the raw send call in
     *   BookingObserver (NotificationEvents::BOOKING_CANCELLED_CUSTOMER).
     * - status='confirmed' (specialist approves the booking): the one case that was always correct
     *   (single SMS) — now settings-gated via BOOKING_CONFIRMED_CUSTOMER so admin can still turn it off.
     */
    public function via($notifiable): array
    {
        if ($this->status === 'completed' || $this->status === 'cancelled') {
            return ['database'];
        }

        return $this->gatedChannels(NotificationEvents::BOOKING_CONFIRMED_CUSTOMER, ['database', 'sms']);
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

        $amountLabel = $this->status === 'completed' ? 'مبلغ کل' : 'پیش‌پرداخت';
        $amountValue = $this->status === 'completed'
            ? number_format($this->booking->service->price)
            : number_format($this->booking->prepayment_amount);

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

        if ($this->status === 'completed') {
            return "سلام {$notifiable->name} عزیز، نوبت شما انجام شد و به پایان رسید."
                .$baseInfo
                ."\n✔️ از اینکه ما را انتخاب کردید سپاسگزاریم.🌹";
        }

        return match ($this->status) {
            'confirmed' => "سلام {$notifiable->name}، نوبت شما تایید شد.".$baseInfo."\n✅ لطفا ۱۵ دقیقه زودتر در محل حضور داشته باشید.",
            'pending_specialist' => "سلام {$notifiable->name}، نوبت شما با موفقیت ثبت شد و در انتظار تایید نهایی متخصص است. نتیجه به زودی اطلاع‌رسانی می‌شود.".$baseInfo,
            'cancelled' => "سلام {$notifiable->name}، نوبت شما لغو شد.".$baseInfo."\n❌ دلیل: ".($this->reason ?? 'ذکر نشده'),
            default => "سلام {$notifiable->name}، وضعیت نوبت شما به ".$this->status.' تغییر یافت.'.$baseInfo
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
