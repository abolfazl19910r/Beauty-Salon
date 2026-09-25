<?php

namespace Tests\Feature\Notification;

use App\Events\Booking\BookingCreated;
use App\Events\Payment\PaymentSucceeded;
use App\Events\Withdrawal\Requested\WithdrawalRequested;
use App\Listeners\Admin\Booking\SendAdminBookingNotifications;
use App\Listeners\Admin\Payment\SendAdminPaymentNotification;
use App\Listeners\Admin\Withdrawal\SendAdminWithdrawalNotification;
use App\Models\Booking;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\SpecialistWallet;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Review\ReviewService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ نشت بین سالن‌ها (۲۰۲۶-۰۹-۲۷، با probe بازتولید شد): اعلان‌های مدیریت «نوبت جدید»، «پرداخت موفق»، «درخواست برداشت»
 * و «نظر منفی» به همه‌ی مدیرهای همه‌ی سالن‌ها می‌رفت (نام مشتری، خدمت، متخصص، مبلغ). getAdminRecipients() بدون شناسه‌ی
 * سالن صدا زده می‌شد، و حتی با شناسه، شرط is_admin با orWhere بیرون از گروه بود و فیلتر سالن رو دور می‌زد.
 */
class AdminNotificationRecipientsSalonScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $ownerA;

    private User $ownerB;

    private Salon $salonB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ownerA = User::factory()->create(['is_admin' => true]);
        $this->salonB = Salon::factory()->create(['slug' => 'recipients-b']);
        app(CurrentSalon::class)->set($this->salonB);
        $this->ownerB = User::factory()->create(['is_admin' => true]);
    }

    private function bookingInB(): Booking
    {
        app(CurrentSalon::class)->set($this->salonB);

        $customer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $this->salonB->id]);

        return Booking::factory()->create(['status' => 'completed', 'user_id' => $customer->id]);
    }

    private function assertOnlyOwnerBNotified(): void
    {
        $this->assertSame(0, $this->ownerA->notifications()->count(), 'another salon\'s admin was notified');
        $this->assertGreaterThan(0, $this->ownerB->notifications()->count());
    }

    public function test_new_booking_notification_reaches_only_that_salons_admins(): void
    {
        $booking = $this->bookingInB();

        app(SendAdminBookingNotifications::class)->handle(new BookingCreated($booking));

        $this->assertOnlyOwnerBNotified();
    }

    public function test_payment_notification_reaches_only_that_salons_admins(): void
    {
        $booking = $this->bookingInB();

        app(SendAdminPaymentNotification::class)->handle(new PaymentSucceeded($booking));

        $this->assertOnlyOwnerBNotified();
    }

    public function test_withdrawal_request_notification_reaches_only_that_salons_admins(): void
    {
        app(CurrentSalon::class)->set($this->salonB);
        $specialist = Specialist::factory()->create();
        $wallet = SpecialistWallet::factory()->create(['specialist_id' => $specialist->id]);
        $withdrawal = WithdrawalRequest::factory()->create(['specialist_id' => $specialist->id, 'wallet_id' => $wallet->id]);

        app(SendAdminWithdrawalNotification::class)->handle(new WithdrawalRequested($withdrawal));

        $this->assertOnlyOwnerBNotified();
    }

    public function test_negative_review_notification_reaches_only_that_salons_admins(): void
    {
        $booking = $this->bookingInB();

        app(ReviewService::class)->createReview([
            'overall_rating' => 1, 'quality_rating' => 1, 'behavior_rating' => 1,
            'cleanliness_rating' => 1, 'speed_rating' => 1, 'comment' => 'بد',
        ], $booking);

        $this->assertSame(0, $this->ownerA->notifications()->count());
        $this->assertSame(1, $this->ownerB->notifications()->where('type', 'like', '%NegativeReview%')->count());
    }

    public function test_recipients_with_the_admin_panel_permission_are_limited_to_the_salon_too(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'access_admin_panel'], ['display_name' => 'access_admin_panel']);
        $role = Role::factory()->create();
        $role->permissions()->attach($permission);
        $staffA = User::factory()->create(['user_type' => 'staff', 'salon_id' => null]);
        $staffA->roles()->attach($role);
        app(CurrentSalon::class)->set(Salon::where('slug', 'rasta')->first());
        $staffA->salons()->attach(app(CurrentSalon::class)->id(), ['role' => 'staff']);

        $ids = app(UserRepositoryInterface::class)->getAdminRecipients($this->salonB->id)->pluck('id')->all();

        $this->assertSame([$this->ownerB->id], $ids);
    }

    public function test_no_salon_means_no_recipients(): void
    {
        $this->assertCount(0, app(UserRepositoryInterface::class)->getAdminRecipients(null));
    }
}
