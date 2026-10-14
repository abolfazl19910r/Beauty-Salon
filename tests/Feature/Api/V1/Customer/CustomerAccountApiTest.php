<?php

namespace Tests\Feature\Api\V1\Customer;

use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\Salon;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * کیف پول، باشگاه مشتریان، پروفایل، حذف حساب و اعلان‌های مشتری در اپ «ماهرو».
 */
class CustomerAccountApiTest extends CustomerApiTestCase
{
    public function test_wallet_shows_balance_and_transactions(): void
    {
        $wallet = $this->customer->getOrCreateWallet();
        $wallet->update(['balance' => 30000]);
        $wallet->transactions()->create(['type' => 'refund', 'amount' => 30000, 'balance_after' => 30000, 'description' => 'برگشت']);

        $this->api()->getJson('/api/v1/customer/wallet')->assertOk()->assertJsonPath('data.balance', 30000);
        $this->api()->getJson('/api/v1/customer/wallet/transactions')
            ->assertOk()->assertJsonPath('data.0.type', 'refund')->assertJsonPath('meta.total', 1);
    }

    public function test_loyalty_lists_rewards_and_redeems_once_for_the_points(): void
    {
        Notification::fake();
        LoyaltyPoint::create(['user_id' => $this->customer->id, 'points' => 120, 'type' => 'earned', 'description' => 'نوبت']);
        $reward = Reward::factory()->create(['title' => 'تخفیف ۵۰ هزاری', 'required_points' => 100, 'discount_type' => 'fixed', 'discount_amount' => 50000, 'max_uses' => null]);

        $this->api()->getJson('/api/v1/customer/loyalty')
            ->assertOk()->assertJsonPath('data.points', 120)->assertJsonPath('data.rewards.0.can_redeem', true);

        $code = $this->api()->postJson("/api/v1/customer/loyalty/rewards/{$reward->id}/redeem")
            ->assertCreated()->assertJsonPath('data.points', 20)->json('data.code');
        $this->api()->postJson("/api/v1/customer/loyalty/rewards/{$reward->id}/redeem")
            ->assertStatus(409)->assertJsonPath('error.code', 'reward_not_available');

        $this->api()->getJson('/api/v1/customer/loyalty/codes')->assertOk()->assertJsonPath('data.active.0.code', $code);
        $this->api()->getJson('/api/v1/customer/loyalty/history')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_another_salons_reward_is_404(): void
    {
        $other = Salon::factory()->create();
        $reward = Reward::factory()->create(['salon_id' => $other->id]);

        $this->api()->postJson("/api/v1/customer/loyalty/rewards/{$reward->id}/redeem")->assertNotFound();
    }

    public function test_profile_shows_and_updates_the_name_only(): void
    {
        $this->api()->getJson('/api/v1/customer/profile')->assertOk()->assertJsonPath('data.phone', '09121110000');

        $this->api()->patchJson('/api/v1/customer/profile', ['name' => 'سمیرا', 'phone' => '09120000000'])
            ->assertOk()->assertJsonPath('data.name', 'سمیرا')->assertJsonPath('data.phone', '09121110000');
    }

    /** پیامک‌های پیگیری سالن (۲۰۲۶-۱۰-۱۴): پیش‌فرض روشن؛ اپ می‌تواند بدون فرستادن نام فقط این را عوض کند */
    public function test_profile_turns_marketing_sms_off_and_on(): void
    {
        $this->api()->getJson('/api/v1/customer/profile')->assertOk()->assertJsonPath('data.marketing_sms', true);

        $this->api()->patchJson('/api/v1/customer/profile', ['marketing_sms' => false])
            ->assertOk()->assertJsonPath('data.marketing_sms', false)->assertJsonPath('data.name', $this->customer->name);
        $this->assertNotNull($this->customer->fresh()->marketing_sms_opted_out_at);

        $this->api()->patchJson('/api/v1/customer/profile', ['marketing_sms' => true])->assertOk()->assertJsonPath('data.marketing_sms', true);
        $this->api()->patchJson('/api/v1/customer/profile', [])->assertStatus(422);
    }

    public function test_changing_the_password_needs_the_current_one_and_signs_out_every_device(): void
    {
        $this->api()->putJson('/api/v1/customer/password', ['current_password' => 'wrong', 'password' => 'new-pass-123', 'password_confirmation' => 'new-pass-123'])
            ->assertStatus(422)->assertJsonPath('error.code', 'invalid_password');

        $this->api()->putJson('/api/v1/customer/password', ['current_password' => 'password', 'password' => 'new-pass-123', 'password_confirmation' => 'new-pass-123'])
            ->assertOk();

        $this->assertTrue(Hash::check('new-pass-123', $this->customer->fresh()->password));
        $this->api()->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_account_deletion_is_refused_with_an_active_booking_and_anonymises_otherwise(): void
    {
        $booking = $this->bookingFor($this->customer, ['status' => 'confirmed', 'payment_status' => 'paid']);

        $this->api()->deleteJson('/api/v1/customer/account', ['password' => 'password'])
            ->assertStatus(409)->assertJsonPath('error.code', 'account_deletion_blocked');
        $this->api()->deleteJson('/api/v1/customer/account', ['password' => 'nope'])->assertStatus(422);

        $booking->update(['status' => 'completed', 'booking_time' => now()->subDay()]);
        $this->api()->deleteJson('/api/v1/customer/account', ['password' => 'password'])->assertOk();

        $this->assertSame('deleted-'.$this->customer->id, $this->customer->fresh()->phone);
        $this->api()->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_notifications_are_only_the_customers_own_in_this_salon(): void
    {
        $mine = $this->notify($this->customer, 'نوبت شما ثبت شد');
        $this->notify(User::factory()->create(), 'مال دیگری');

        $this->api()->getJson('/api/v1/customer/notifications')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.message', 'نوبت شما ثبت شد')->assertJsonPath('meta.unread_count', 1);

        $this->api()->postJson("/api/v1/customer/notifications/{$mine->id}/read")->assertOk()->assertJsonPath('data.read', true);
        $this->api()->postJson('/api/v1/customer/notifications/read-all')->assertOk()->assertJsonPath('data.marked', 0);
    }

    private function notify(User $user, string $message): UserNotification
    {
        return UserNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\Booking\\CustomerBookingNotification',
            'user_id' => $user->id,
            'salon_id' => $this->salon->id,
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'data' => ['message' => $message],
        ]);
    }
}
