<?php

namespace App\Notifications\Booking;

use App\Models\Booking;
use App\Notifications\Concerns\QueuesOnlySmsChannel;
use App\Services\SMSService;
use App\Support\Notifications\NotificationEvents;
use App\Traits\RespectsNotificationSettings;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class BookingRescheduledNotification extends Notification implements ShouldQueue
{
    use Queueable, QueuesOnlySmsChannel, RespectsNotificationSettings, SerializesModels;

    private Booking $booking;

    private string|Carbon $oldTime;

    /**
     * @param  Carbon|string  $oldTime
     * @return void
     */
    public function __construct(Booking $booking, $oldTime)
    {
        $this->booking = $booking;
        $this->oldTime = $oldTime;
    }

    protected function settingsSalonId(): ?int
    {
        return $this->booking->salon_id;
    }

    public function via($notifiable): array
    {
        // متخصص و مشتری هر کدام کلید تنظیمات خودشان را دارند (۲۰۲۶-۰۹-۳۰)
        $event = $notifiable instanceof \App\Models\Specialist
            ? NotificationEvents::BOOKING_RESCHEDULED_SPECIALIST
            : NotificationEvents::BOOKING_RESCHEDULED_CUSTOMER;

        return $this->gatedChannels($event, ['database', 'sms'], $notifiable);
    }

    public function toArray($notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'message' => 'زمان نوبت تغییر کرد',
            'user_name' => $this->booking->user->name,
            'service_name' => $this->booking->service->name,
            'old_time' => $this->oldTime,
            'new_time' => $this->booking->booking_time,
        ];
    }

    public function toSms($notifiable): bool
    {
        $message = $notifiable instanceof \App\Models\Specialist
            ? \App\Support\Sms\SmsText::rescheduledForSpecialist($this->booking, $this->oldTime)
            : \App\Support\Sms\SmsText::rescheduledForCustomer($this->booking);

        return app(SMSService::class)->send($notifiable->phone, $message, $this->settingsSalonId());
    }
}
