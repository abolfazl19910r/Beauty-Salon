<?php

namespace Tests\Feature\Notification;

use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * رگرسیون (۲۰۲۶-۱۰-۱۱): بعد از باز کردن یک اعلان و «برگشت» مرورگر، فهرست از کش تاریخچه‌ی مرورگر
 * نشان داده می‌شد و اعلان خوانده‌شده هنوز «جدید» دیده می‌شد (no-cache جلوی آن را نمی‌گیرد، no-store می‌گیرد).
 */
class NotificationPagesNoStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_notification_pages_are_never_served_from_browser_history_cache(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'user_type' => 'staff']);

        $this->assertStringContainsString('no-store', $this->actingAs($admin)->get(route('admin.notifications.index'))->assertOk()->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $this->actingAs($admin)->getJson(route('admin.notifications.count'))->headers->get('Cache-Control'));
    }

    public function test_specialist_notification_pages_are_never_served_from_browser_history_cache(): void
    {
        $user = User::find(Specialist::factory()->create()->user_id);

        $this->assertStringContainsString('no-store', $this->actingAs($user)->get(route('specialist.notifications.index'))->assertOk()->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $this->actingAs($user)->getJson(route('specialist.notifications.count'))->headers->get('Cache-Control'));
    }
}
