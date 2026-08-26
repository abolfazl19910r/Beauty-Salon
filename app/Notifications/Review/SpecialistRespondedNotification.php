<?php

namespace App\Notifications\Review;

use App\Models\Review;
use App\Support\Notifications\NotificationEvents;
use App\Traits\RespectsNotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SpecialistRespondedNotification extends Notification
{
    use Queueable;
    use RespectsNotificationSettings;

    public function __construct(protected readonly Review $review) {}

    public function via($notifiable): array
    {
        return $this->gatedChannels(NotificationEvents::REVIEW_RESPONDED_CUSTOMER, ['database']);
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => '💬 پاسخ به نظر شما',
            'message' => sprintf(
                '%s به نظر شما درباره %s پاسخ داد.',
                $this->review->specialist->name,
                $this->review->service->name
            ),
            'link' => route('bookings.show', $this->review->booking_id, false),
            'review_id' => $this->review->id,
            'specialist_id' => $this->review->specialist_id,
            'type' => 'specialist_response',
        ];
    }
}
