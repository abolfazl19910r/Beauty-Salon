<?php

namespace Tests\Feature\Notification;

use App\Models\Booking;
use App\Models\DiscountCode;
use App\Models\Leave;
use App\Models\LoyaltyPoint;
use App\Models\NotificationSetting;
use App\Models\ReportExport;
use App\Models\Review;
use App\Models\Reward;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Notifications\Admin\Payment\AdminPaymentReceivedNotification;
use App\Notifications\Admin\Report\Export\ReportExportReadyNotification;
use App\Notifications\Admin\Withdrawal\Request\AdminNewWithdrawalRequestNotification;
use App\Notifications\Booking\AdminNewBookingNotification;
use App\Notifications\Booking\BookingNotification;
use App\Notifications\Booking\BookingRescheduledNotification;
use App\Notifications\Booking\BookingStatusUpdated;
use App\Notifications\Leave\LeaveStatusNotification;
use App\Notifications\Loyalty\PointsEarned;
use App\Notifications\Loyalty\RewardRedeemed;
use App\Notifications\Review\NegativeReviewNotification;
use App\Notifications\Review\NewReviewNotification;
use App\Notifications\Review\NewReviewReceivedNotification;
use App\Notifications\Review\SpecialistRespondedNotification;
use App\Notifications\Sms\SmsQuotaExhaustedNotification;
use App\Notifications\User\NewUserRegisteredNotification;
use App\Notifications\Withdrawal\Approved\WithdrawalApprovedNotification;
use App\Notifications\Withdrawal\Rejected\WithdrawalRejectedNotification;
use App\Support\CurrentSalon;
use App\Support\Notifications\NotificationEvents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * تنظیمات اطلاع‌رسانی از سالنِ خودِ رکورد اعلان (نوبت، پرداخت، برداشت، نظر، مرخصی، …) خوانده می‌شود، نه از گیرنده
 * (۲۰۲۶-۰۹-۳۰). قبلاً SalonOfNotifiable برای کارمند اولین سالنش در salon_admins را برمی‌داشت: اعلان سالن دوم ادمینی که
 * مالک دو سالن است با تنظیمات سالن اولش ارسال می‌شد.
 */
class NotificationSettingsFollowRecordSalonTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_settings_gated_notification_uses_the_settings_of_the_salon_it_is_about(): void
    {
        $first = app(CurrentSalon::class)->get();
        $second = Salon::factory()->create(['slug' => 'second-salon']);

        // گیرنده: مالک هر دو سالن؛ اولین ردیفش در salon_admins سالن اول است.
        $owner = User::factory()->create(['is_admin' => true]);
        $owner->salons()->syncWithoutDetaching([$first->id => ['role' => 'owner'], $second->id => ['role' => 'owner']]);

        foreach (NotificationEvents::allKeys() as $key) {
            NotificationSetting::create(['salon_id' => $first->id, 'event_key' => $key, 'sms_enabled' => true, 'database_enabled' => true, 'telegram_enabled' => false]);
            NotificationSetting::create(['salon_id' => $second->id, 'event_key' => $key, 'sms_enabled' => false, 'database_enabled' => false, 'telegram_enabled' => false]);
        }

        app(CurrentSalon::class)->set($second);
        $customer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $second->id]);
        $specialist = Specialist::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $customer->id, 'specialist_id' => $specialist->id]);
        $review = Review::factory()->create(['booking_id' => $booking->id, 'user_id' => $customer->id, 'specialist_id' => $specialist->id]);
        $leave = Leave::factory()->create(['specialist_id' => $specialist->id]);
        $withdrawal = WithdrawalRequest::factory()->create(['specialist_id' => $specialist->id, 'wallet_id' => $specialist->getOrCreateWallet()->id]);
        $export = ReportExport::factory()->create(['salon_id' => $second->id]);
        $reward = Reward::factory()->create();
        $point = LoyaltyPoint::factory()->create(['user_id' => $customer->id]);
        $discount = DiscountCode::factory()->create();
        app(CurrentSalon::class)->clear(); // مثل worker صف

        $this->assertSame($second->id, $booking->salon_id);
        $this->assertSame($second->id, $reward->salon_id);

        $notifications = [
            new BookingStatusUpdated($booking, 'confirmed'),
            new AdminNewBookingNotification($booking),
            new BookingRescheduledNotification($booking, now()),
            new BookingNotification($booking),
            new AdminPaymentReceivedNotification($booking),
            new AdminNewWithdrawalRequestNotification($withdrawal),
            new ReportExportReadyNotification($export),
            new NewReviewNotification($booking),
            new SpecialistRespondedNotification($review),
            new NewReviewReceivedNotification($review),
            new NegativeReviewNotification($review),
            new WithdrawalRejectedNotification($withdrawal, 'x'),
            new WithdrawalApprovedNotification($withdrawal),
            new SmsQuotaExhaustedNotification($second, 10),
            new NewUserRegisteredNotification($customer),
            new RewardRedeemed($reward, $discount),
            new PointsEarned($point),
            new LeaveStatusNotification($leave),
        ];

        $wrong = [];
        foreach ($notifications as $notification) {
            $via = $notification->via($owner);
            if ($via !== []) {
                $wrong[class_basename($notification)] = $via;
            }
        }

        $this->assertSame([], $wrong, 'سالن دوم همه‌ی کانال‌ها را خاموش کرده، ولی این اعلان‌ها با تنظیمات سالن اول مالک ارسال شدند');
    }
}
