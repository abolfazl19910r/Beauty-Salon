<?php

namespace App\Notifications\Review;

use App\Models\Booking;
use App\Notifications\Concerns\QueuesOnlySmsChannel;
use App\Services\SMSService;
use App\Support\Notifications\NotificationEvents;
use App\Traits\RespectsNotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class NewReviewNotification extends Notification implements ShouldQueue
{
    use Queueable, QueuesOnlySmsChannel, RespectsNotificationSettings, SerializesModels;

    private Booking $booking;

    /**
     * @return void
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    protected function settingsSalonId(): ?int
    {
        return $this->booking->salon_id;
    }

    public function via($notifiable): array
    {
        return $this->gatedChannels(NotificationEvents::REVIEW_NEW_SPECIALIST, ['database', 'sms'], $notifiable);
    }

    public function toArray($notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'message' => "یک نظر جدید با امتیاز {$this->booking->rating} ثبت شد",
            'user_name' => $this->booking->user->name,
            'service_name' => $this->booking->service->name,
            'rating' => $this->booking->rating,
            'review' => $this->booking->review,
        ];
    }

    public function toSms($notifiable): bool
    {
        $message = sprintf(
            'نظر جدید - %s:
مشتری: %s
امتیاز: %d/5
%s',
            $this->booking->service->name,
            $this->booking->user->name,
            $this->booking->rating,
            $this->booking->review ? 'نظر: '.$this->booking->review : ''
        );

        return app(SMSService::class)->send($notifiable->phone, $message, $this->settingsSalonId());
    }
}
