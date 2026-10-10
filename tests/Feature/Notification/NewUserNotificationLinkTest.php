<?php

namespace Tests\Feature\Notification;

use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\User\NewUserRegisteredNotification;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * رگرسیون: اعلان «کاربر جدید» در زنگوله‌ی ادمین به /admin/users/{id} می‌رفت که برای مشتری 404 است
 * (فهرست کاربران ادمین فقط مدیران سالن را دارد).
 */
class NewUserNotificationLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_customer_notification_has_no_dead_link_and_bell_opens_the_notification(): void
    {
        $customer = User::factory()->create(['user_type' => 'customer', 'salon_id' => app(CurrentSalon::class)->id()]);
        $data = (new NewUserRegisteredNotification($customer))->toArray($customer);

        $this->assertNull($data['link']);
        $this->assertStringContainsString($customer->phone, $data['message']);
    }

    public function test_bell_never_points_stale_new_user_notifications_at_the_customer_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'user_type' => 'staff']); // مدیر سالن جاری به‌طور خودکار عضو salon_admins می‌شود

        // اعلانی که قبل از رفع ذخیره شده و هنوز لینک مرده دارد
        $stale = UserNotification::create([
            'id' => (string) Str::uuid(),
            'type' => NewUserRegisteredNotification::class,
            'notifiable_type' => $admin->getMorphClass(),
            'notifiable_id' => $admin->id,
            'data' => ['type' => 'new_user_registered', 'message' => 'یک کاربر جدید ثبت‌نام کرد: x', 'link' => url('/admin/users/999')],
        ]);

        $link = collect($this->actingAs($admin)->getJson(route('admin.notifications.latest'))->json('notifications'))
            ->firstWhere('id', $stale->id)['link'];

        $this->assertSame(route('admin.notifications.show', $stale->id), $link);
        $this->get($link)->assertOk()->assertDontSee('/admin/users/999');
    }
}
