<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewReviewReceivedNotification extends Notification
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
            'title' => '⭐ نظر جدید دریافت شد',
            'message' => sprintf(
                '%s برای خدمت %s امتیاز %d ستاره به شما داد.',
                $this->review->user->name,
                $this->review->service->name,
                $this->review->overall_rating
            ),
            'link' => route('specialist.reviews.show', $this->review->id),
            'review_id' => $this->review->id,
            'rating' => $this->review->overall_rating,
            'type' => 'new_review'
        ];
    }
}
