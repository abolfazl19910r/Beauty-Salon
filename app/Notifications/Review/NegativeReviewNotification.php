<?php

namespace App\Notifications\Review;

use App\Models\Review;
use App\Support\Notifications\NotificationEvents;
use App\Traits\RespectsNotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NegativeReviewNotification extends Notification
{
    use Queueable;
    use RespectsNotificationSettings;

    public function __construct(protected readonly Review $review) {}

    public function via($notifiable): array
    {
        return $this->gatedChannels(NotificationEvents::REVIEW_NEGATIVE_ADMIN, ['database']);
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => '⚠️ نظر منفی دریافت شد',
            'message' => sprintf(
                'نظر منفی (%d ستاره) از %s برای متخصص %s ثبت شد.',
                $this->review->overall_rating,
                $this->review->user->name,
                $this->review->specialist->name
            ),
            'link' => route('admin.reviews.show', $this->review->id, false),
            'review_id' => $this->review->id,
            'rating' => $this->review->overall_rating,
            'specialist_id' => $this->review->specialist_id,
            'type' => 'negative_review',
        ];
    }
}
