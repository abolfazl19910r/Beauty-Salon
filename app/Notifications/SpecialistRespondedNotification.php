<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SpecialistRespondedNotification extends Notification
{
    use Queueable;

    protected Review $review;

    public function __construct(Review $review)
    {
        $this->review = $review;
    }

    public function via($notifiable): array
    {
        return ['database'];
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
            'link' => route('bookings.show', $this->review->booking_id),
            'review_id' => $this->review->id,
            'specialist_id' => $this->review->specialist_id,
            'type' => 'specialist_response'
        ];
    }
}
