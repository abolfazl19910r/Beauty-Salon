<?php

namespace App\Services\Review;

use App\Models\Booking;
use App\Models\Review;
use App\Models\ReviewToken;
use App\Notifications\Review\NegativeReviewNotification;
use App\Notifications\Review\NewReviewReceivedNotification;
use App\Notifications\Review\SpecialistRespondedNotification;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use App\Repositories\Contracts\ReviewTokenRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Notification\NotificationSettingService;
use App\Services\SMSService;
use App\Support\Notifications\NotificationEvents;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ReviewService
{
    public function __construct(
        protected readonly SMSService $smsService,
        protected readonly NotificationSettingService $notificationSettings,
        protected readonly ReviewRepositoryInterface $reviewRepository,
        protected readonly ReviewTokenRepositoryInterface $reviewTokenRepository,
        protected readonly BookingRepositoryInterface $bookingRepository,
        protected readonly UserRepositoryInterface $userRepository,
    ) {}

    public function sendReviewRequest(Booking $booking): bool
    {
        try {
            if ($booking->review_sent_at) {
                return false;
            }

            if (! $this->notificationSettings->isEnabled(NotificationEvents::BOOKING_COMPLETED_REVIEW_REQUEST, 'sms')) {
                return false;
            }

            $reviewToken = ReviewToken::createForBooking($booking);

            $baseUrl = request()?->getSchemeAndHttpHost() ?: rtrim(config('app.url'), '/');
            $reviewUrl = $baseUrl.route('reviews.create', ['token' => $reviewToken->token], false);

            $message = sprintf(
                "سلام %s، خدمت %s با موفقیت انجام شد. لطفاً نظر خود را ثبت کنید:\n%s",
                $booking->user->name,
                $booking->service->name,
                $reviewUrl
            );

            $sent = $this->smsService->send($booking->user->phone, $message, $booking->salon_id);

            if ($sent) {
                $this->bookingRepository->update($booking, ['review_sent_at' => now()]);
            }

            return $sent;

        } catch (\Exception $e) {
            Log::error('❌ Failed to send review request', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function createReview(array $data, Booking $booking): Review
    {
        try {
            $review = $this->reviewRepository->create([
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'specialist_id' => $booking->specialist_id,
                'service_id' => $booking->service_id,
                'overall_rating' => $data['overall_rating'],
                'quality_rating' => $data['quality_rating'],
                'behavior_rating' => $data['behavior_rating'],
                'cleanliness_rating' => $data['cleanliness_rating'],
                'speed_rating' => $data['speed_rating'],
                'comment' => $data['comment'] ?? null,
            ]);

            $this->bookingRepository->update($booking, [
                'rating' => $data['overall_rating'],
                'review' => $data['comment'] ?? null,
                'reviewed_at' => now(),
            ]);

            Cache::forget('specialist_avg_rating_'.$booking->specialist_id);

            $booking->specialist->notify(new NewReviewReceivedNotification($review));

            if ($review->isNegative()) {
                $this->notifyAdminAboutNegativeReview($review);
            }

            try {
                $booking->user->addLoyaltyPoints(
                    10,
                    "ثبت نظر برای نوبت #{$booking->id}",
                    $booking->id
                );
            } catch (\Exception $e) {
                Log::warning('Failed to add loyalty points for review', [
                    'review_id' => $review->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return $review;

        } catch (\Exception $e) {
            Log::error('❌ Failed to create review', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function respondToReview(Review $review, string $response): bool
    {
        try {
            $review = $this->reviewRepository->update($review, [
                'specialist_response' => $response,
                'responded_at' => now(),
            ]);

            $review->user->notify(new SpecialistRespondedNotification($review));

            return true;

        } catch (\Exception $e) {
            Log::error('❌ Failed to respond to review', [
                'review_id' => $review->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getSpecialistAverageRating(int $specialistId): float
    {
        return Cache::remember(
            'specialist_avg_rating_'.$specialistId,
            now()->addHours(6),
            function () use ($specialistId) {
                return $this->reviewRepository->calculateSpecialistAverage($specialistId);
            }
        );
    }

    protected function notifyAdminAboutNegativeReview(Review $review): void
    {
        try {
            $admins = $this->userRepository->getAdminRecipients();

            foreach ($admins as $admin) {
                $admin->notify(new NegativeReviewNotification($review));
            }

        } catch (\Exception $e) {
            Log::error('Failed to notify admin about negative review', [
                'review_id' => $review->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function validateToken(string $token): ?array
    {
        $reviewToken = $this->reviewTokenRepository->findValidToken($token);

        if (! $reviewToken) {
            Log::warning('❌ Invalid or expired token', ['token' => $token]);

            return null;
        }

        return [
            'booking_id' => $reviewToken->booking_id,
            'user_id' => $reviewToken->user_id,
        ];
    }

    public function consumeToken(string $token): void
    {
        $reviewToken = $this->reviewTokenRepository->findByToken($token);

        if ($reviewToken) {
            $reviewToken->markAsUsed();
        }
    }
}
