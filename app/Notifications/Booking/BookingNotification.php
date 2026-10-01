<?php

namespace App\Notifications\Booking;

use App\Models\Booking;
use App\Notifications\Concerns\QueuesOnlySmsChannel;
use App\Services\SMSService;
use App\Support\Notifications\NotificationEvents;
use App\Traits\RespectsNotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class BookingNotification extends Notification implements ShouldQueue
{
    use Queueable, QueuesOnlySmsChannel, RespectsNotificationSettings, SerializesModels;

    public function __construct(
        private readonly Booking $booking,
        private readonly bool $needsApproval = false
    ) {}

    protected function settingsSalonId(): ?int
    {
        return $this->booking->salon_id;
    }

    public function via($notifiable): array
    {
        return $this->gatedChannels(NotificationEvents::BOOKING_CREATED_SPECIALIST, ['database', 'sms'], $notifiable);
    }

    public function toArray($notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'message' => 'یک نوبت جدید برای شما ثبت شده است',
            'user_name' => $this->booking->user->name,
            'service_name' => $this->booking->service->name,
            'booking_time' => $this->booking->booking_time,
            'total_price' => (float) $this->booking->service->price,
            'prepayment_amount' => (float) $this->booking->prepayment_amount,
            'remaining_amount' => $this->booking->remaining_amount,
        ];
    }

    public function toSms($notifiable): bool
    {
        $message = \App\Support\Sms\SmsText::newBookingForSpecialist($this->booking, (bool) $this->needsApproval);

        return app(SMSService::class)->send($notifiable->phone, $message, $this->booking->salon_id);
    }
}
