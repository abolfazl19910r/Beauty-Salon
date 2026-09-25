<?php

namespace Tests\Feature\Admin;

use App\Models\Salon;
use App\Models\SecurityLog;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ نشت بین سالن‌ها (۲۰۲۶-۰۹-۲۷، ممیزی جداسازی سالن‌ها): بخش «امنیت» مدیریت (گزارش رویدادها، فهرست کاربران و
 * شمارنده‌ها) کل پلتفرم رو نشون می‌داد — نام و شماره‌ی تلفن مشتری‌ها و کارمندهای همه‌ی سالن‌ها، رویدادهای ورودشون، و
 * آمار. users ستون salon_id داره ولی کارمندها (ادمین، متخصص) salon_id=null هستن، پس «کاربر این سالن» یعنی مشتری
 * همین سالن، ادمین در salon_admins، یا کاربرِ متخصص همین سالن (UserRepository::querySalonMembers).
 */
class SecuritySalonScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $ownCustomer;

    private User $ownSpecialistUser;

    private User $otherCustomer;

    private User $otherOwner;

    private User $otherSpecialistUser;

    protected function setUp(): void
    {
        parent::setUp();
        $salonA = app(CurrentSalon::class)->get();
        $this->owner = User::factory()->create(['is_admin' => true, 'name' => 'OWN-OWNER']);
        $this->ownCustomer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $salonA->id, 'name' => 'OWN-CUSTOMER']);
        $this->ownSpecialistUser = User::find(Specialist::factory()->create()->user_id);
        SecurityLog::factory()->create(['user_id' => $this->ownCustomer->id, 'level' => 'warning', 'event' => 'login_attempt']);

        $salonB = Salon::factory()->create(['slug' => 'other-security']);
        app(CurrentSalon::class)->set($salonB);
        $this->otherOwner = User::factory()->create(['is_admin' => true, 'name' => 'OTHER-OWNER']);
        $this->otherCustomer = User::factory()->create(['user_type' => 'customer', 'salon_id' => $salonB->id, 'name' => 'OTHER-CUSTOMER', 'two_factor_enabled' => true]);
        $this->otherSpecialistUser = User::find(Specialist::factory()->create()->user_id);
        SecurityLog::factory()->count(3)->create(['user_id' => $this->otherCustomer->id, 'level' => 'warning', 'event' => 'login_attempt']);
        app(CurrentSalon::class)->clear();
    }

    public function test_security_logs_and_counters_cover_only_this_salons_users(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.security.logs'))->assertOk();

        $this->assertSame([$this->ownCustomer->id], $response->viewData('logs')->pluck('user_id')->unique()->values()->all());
        $stats = $response->viewData('stats');
        $this->assertSame(1, $stats['logs_last_30_days']);
        $this->assertSame(1, $stats['warnings_last_30_days']);
        $this->assertSame(0, $stats['users_with_2fa']);
    }

    public function test_another_salons_user_cannot_be_picked_as_a_log_filter(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.security.logs', ['user_id' => $this->otherCustomer->id]))->assertOk();

        $this->assertSame(0, $response->viewData('logs')->total());
    }

    public function test_security_user_list_shows_only_this_salons_customers_admins_and_specialists(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.security.users'))->assertOk();

        $ids = $response->viewData('users')->pluck('id')->sort()->values()->all();
        $expected = collect([$this->owner->id, $this->ownCustomer->id, $this->ownSpecialistUser->id])->sort()->values()->all();
        $this->assertSame($expected, $ids);
    }

    public function test_security_user_search_does_not_reach_other_salons(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.security.users', ['search' => 'OTHER']))->assertOk();

        $this->assertSame(0, $response->viewData('users')->total());
    }
}
