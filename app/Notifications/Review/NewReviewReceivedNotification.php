<?php

namespace App\Notifications\Review;

use App\Models\Review;
use App\Services\SMSService;
use App\Support\Notifications\NotificationEvents;
use App\Traits\RespectsNotificationSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewReviewReceivedNotification extends Notification
{
    use Queueable;
    use RespectsNotificationSettings;

    public function __construct(protected readonly Review $review) {}

    /**
     * ⭐ Fix: this is the class actually used by ReviewService::createReview() (the real,
     * token-based "leave a review" flow) — it previously only had a 'database' entry in its base
     * channel set, so no matter how the admin toggled the 'پیامک' column for «ثبت نظر جدید — اطلاع
     * به متخصص» in the settings panel, SMS could never fire (gatedChannels() only adds a channel
     * that's both enabled AND present in $base). The specialist's SMS for this flow simply never
     * existed as working code. A sibling class, NewReviewNotification, does have working SMS logic
     * but belongs to the separate, older quick-star-rating flow (BookingController::rate()) — it is
     * not touched here.
     */
    public function via($notifiable): array
    {
        return $this->gatedChannels(NotificationEvents::REVIEW_NEW_SPECIALIST, ['database', 'sms']);
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
            'link' => route('specialist.reviews.show', $this->review->id, false),
            'review_id' => $this->review->id,
            'rating' => $this->review->overall_rating,
            'type' => 'new_review',
        ];
    }

    public function toSms($notifiable): bool
    {
        $message = sprintf(
            "%s عزیز، یک نظر جدید دریافت کردید:\n👤 مشتری: %s\n💇 سرویس: %s\n⭐ امتیاز: %d از ۵%s",
            $notifiable->name,
            $this->review->user->name,
            $this->review->service->name,
            $this->review->overall_rating,
            $this->review->comment ? "\n💬 نظر: {$this->review->comment}" : ''
        );

        return (new SMSService)->send($notifiable->phone, $message);
    }
}
