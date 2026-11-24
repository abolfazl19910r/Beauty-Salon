<?php

namespace Tests\Feature\Admin;

use App\Models\Booking;
use App\Models\Leave;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Support\Admin\AdminMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بسته‌ی ۲ ظاهری (۲۰۲۶-۱۱-۲۴): منوی پنل مدیر — «کار روزانه» همیشه باز با شمار کار مانده، ۴ گروه جمع‌شونده با گروه‌بندی
 * تأییدشده، «پشتیبانی/پروفایل» در منوی حساب، کارت اشتراک و دکمه‌ی «ثبت نوبت».
 */
class AdminMenuGroupsTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salon = Salon::where('slug', 'rasta')->firstOrFail();
        $this->owner = User::factory()->create(['is_admin' => true]); // اولین is_admin = مالک سالن جاری
    }

    public function test_groups_follow_the_approved_layout(): void
    {
        $groups = collect(AdminMenu::groups($this->owner, $this->salon))->keyBy('key');

        $this->assertSame(['daily', 'salon', 'marketing', 'finance', 'settings', 'account'], $groups->keys()->all());
        $this->assertFalse($groups['daily']['collapsible']);
        $this->assertTrue($groups['salon']['collapsible']);
        $this->assertSame('account', $groups['account']['placement']);

        $keys = fn (string $g) => array_column($groups[$g]['items'], 'key');
        $this->assertSame(['dashboard', 'bookings', 'customers', 'leaves', 'notifications'], $keys('daily'));
        $this->assertSame(['services', 'categories', 'specialists', 'courses', 'gallery', 'salon-qr'], $keys('salon'));
        $this->assertSame(['reviews', 'loyalty', 'winback', 'discount-codes', 'announcements', 'blog'], $keys('marketing'));
        $this->assertSame(['reports', 'wallets', 'withdrawals', 'wallet-settings', 'billing'], $keys('finance'));
        $this->assertSame(['salon-settings', 'appearance', 'booking-policy', 'payment-gateways', 'verification', 'notification-settings', 'security', 'users', 'permissions', 'roles'], $keys('settings'));
        $this->assertSame(['support', 'profile'], $keys('account'));
    }

    public function test_daily_counts_show_waiting_bookings_and_leaves_of_this_salon_only(): void
    {
        $specialist = Specialist::factory()->create(['salon_id' => $this->salon->id]);
        Booking::factory()->create(['salon_id' => $this->salon->id, 'specialist_id' => $specialist->id, 'status' => 'pending', 'booking_time' => now()->addDay()]);
        Booking::factory()->create(['salon_id' => $this->salon->id, 'specialist_id' => $specialist->id, 'status' => 'pending_payment', 'booking_time' => now()->addDays(2)]);
        Booking::factory()->create(['salon_id' => $this->salon->id, 'specialist_id' => $specialist->id, 'status' => 'pending', 'booking_time' => now()->subDay()]); // گذشته
        Booking::factory()->create(['salon_id' => $this->salon->id, 'specialist_id' => $specialist->id, 'status' => 'confirmed', 'booking_time' => now()->addDay()->addHours(3)]);
        Leave::factory()->create(['specialist_id' => $specialist->id, 'status' => 'pending']);

        // سالن دیگر: نباید شمرده شود
        $other = Salon::factory()->create();
        $otherSpecialist = Specialist::factory()->create();
        Leave::factory()->create(['specialist_id' => $otherSpecialist->id, 'status' => 'pending']);
        // BelongsToSalon سالن جاری را هنگام ساخت می‌گذارد؛ کارشناس را مستقیم به سالن دیگر می‌بریم
        Specialist::withoutGlobalScopes()->whereKey($otherSpecialist->id)->toBase()->update(['salon_id' => $other->id]);

        $html = $this->actingAs($this->owner)->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('data-menu-count="bookings" aria-label="۲ مورد منتظر"', $html);
        $this->assertStringContainsString('data-menu-count="leaves" aria-label="۱ مورد منتظر"', $html);
    }

    public function test_drawer_has_collapsible_groups_account_menu_and_subscription_card(): void
    {
        $html = $this->actingAs($this->owner)->get(route('admin.bookings.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-adm-group-fixed="daily"', $html);
        // گروه صفحه‌ی فعلی باز است، بقیه بسته
        $this->assertMatchesRegularExpression('/data-adm-group="settings"\s*>/', $html);
        $this->assertStringContainsString('data-adm-group="finance"', $html);
        $this->assertStringContainsString('data-menu-key="support"', $html);
        $this->assertStringContainsString('data-menu-key="profile"', $html);
        $this->assertStringContainsString('data-drawer-subscription', $html);
        $this->assertStringContainsString('data-new-booking', $html);

        $html = $this->actingAs($this->owner)->get(route('admin.billing.index'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/data-adm-group="finance"\s+open\s+data-adm-group-active/', $html);
    }
}
