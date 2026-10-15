<?php

namespace Tests\Feature\Review;

use App\Models\Booking;
use App\Services\Review\ReviewService;
use App\Services\SMSService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * درخواست نظر برای هر نوبت فقط یک بار: review_sent_at در $fillable نبود و update() آن را بی‌صدا دور می‌ریخت، پس قفل «فرستاده شد»
 * هرگز ذخیره نمی‌شد و هر فراخوانی دوباره (تکمیل دوباره‌ی نوبت، اجرای دوباره‌ی listener) پیامک دوم می‌فرستاد (پیدا شده در بسته‌ی ۴).
 */
class ReviewRequestSentOnceTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_request_is_sent_once_and_remembered(): void
    {
        $sms = Mockery::mock(SMSService::class);
        $sms->shouldReceive('send')->once()->andReturnTrue();
        $this->app->instance(SMSService::class, $sms);

        $booking = Booking::factory()->create(['status' => 'completed', 'payment_status' => 'paid']);

        $this->assertTrue(app(ReviewService::class)->sendReviewRequest($booking));
        $this->assertNotNull($booking->fresh()->review_sent_at);
        $this->assertFalse(app(ReviewService::class)->sendReviewRequest($booking->fresh()));
    }
}
