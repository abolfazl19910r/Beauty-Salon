<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\DemoRequest;
use App\Models\Plan;
use App\Models\PlatformAnnouncement;
use App\Models\Role;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\SuperAdminAuditLog;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\SuperAdmin\PlatformAlertNotification;
use App\Services\Plans\DefaultPlans;
use App\Services\SuperAdmin\SuperAdminImpersonation;
use App\Services\SupportTicket\SupportTicketService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * ⭐ ابزارهای تازه‌ی ابرمدیر (۲۰۲۶-۱۰-۱۵): گزارش کارها، ورود اضطراری، مالک سالن، صندوق اعلان، اطلاعیه‌ی سراسری،
 * وضعیت پشتیبان‌گیری، جستجوی شماره، دسترسی قالب‌ها.
 */
class SuperAdminToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $super;

    private Salon $salon;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salon = app(CurrentSalon::class)->get();
        $this->owner = User::factory()->create(['is_admin' => true, 'user_type' => 'staff', 'phone' => '09121112233', 'name' => 'مالک آزمایشی']);
        $this->salon->admins()->syncWithoutDetaching([$this->owner->id => ['role' => 'owner']]);

        $this->super = User::factory()->create(['is_admin' => true, 'user_type' => 'staff', 'name' => 'ابرمدیر آزمایشی']);
        $this->super->roles()->attach(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));
        $this->super->salons()->detach();
        @unlink(storage_path('app/backup-status.json'));
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('app/backup-status.json'));
        parent::tearDown();
    }

    // ─── ۵) گزارش کارهای ابرمدیر ────────────────────────────

    public function test_manual_super_admin_actions_are_written_to_the_audit_log(): void
    {
        $this->actingAs($this->super)->post(route('superadmin.salons.sms-credit', $this->salon), ['parts' => 500, 'note' => 'جبران قطعی'])->assertSessionHas('success');
        $this->actingAs($this->super)->post(route('superadmin.salons.renew', $this->salon), ['subscription_type' => '3m'])->assertSessionHas('success');

        $credit = SuperAdminAuditLog::where('action', 'salon.sms_credit')->firstOrFail();
        $this->assertSame($this->super->id, $credit->user_id);
        $this->assertSame('ابرمدیر آزمایشی', $credit->user_name);
        $this->assertSame($this->salon->id, $credit->salon_id);
        $this->assertStringContainsString('جبران قطعی', $credit->description);
        $this->assertTrue(SuperAdminAuditLog::where('action', 'salon.renewed')->exists());

        $this->actingAs($this->super)->get(route('superadmin.audit-log.index', ['action' => 'salon.renewed']))->assertOk()
            ->assertSee('تمدید دستی اشتراک')->assertDontSee('جبران قطعی');
    }

    // ─── ۱) ورود اضطراری ────────────────────────────────────

    public function test_super_admin_can_enter_a_salon_panel_as_its_owner_and_come_back(): void
    {
        $this->actingAs($this->super)->post(route('superadmin.salons.impersonate', $this->salon))->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->owner);
        $this->assertSame($this->super->id, session(SuperAdminImpersonation::SESSION_KEY));

        $this->get(route('admin.dashboard'))->assertOk()->assertSee('data-impersonation-banner', false)->assertSee('بازگشت به پنل ابرمدیر');

        // رمز مالک در حالت پشتیبانی عوض نمی‌شود
        $hash = $this->owner->fresh()->password;
        $this->put(route('admin.profile.password'), ['current_password' => 'x', 'password' => 'NewPass1234', 'password_confirmation' => 'NewPass1234'])
            ->assertSessionHas('error');
        $this->assertSame($hash, $this->owner->fresh()->password);

        $this->post(route('impersonation.leave'))->assertRedirect(route('superadmin.salons.edit', $this->salon->id));
        $this->assertAuthenticatedAs($this->super);
        $this->assertNull(session(SuperAdminImpersonation::SESSION_KEY));

        $this->assertSame(['impersonation.started', 'impersonation.ended'], SuperAdminAuditLog::orderBy('id')->pluck('action')->all());
        $this->assertSame($this->super->id, SuperAdminAuditLog::where('action', 'impersonation.ended')->value('user_id'), 'به نام ابرمدیر، نه مالک');
    }

    public function test_salon_admins_cannot_impersonate_and_leave_without_session_is_harmless(): void
    {
        $this->actingAs($this->owner)->post(route('superadmin.salons.impersonate', $this->salon))->assertStatus(403);

        $this->actingAs($this->owner)->post(route('impersonation.leave'))->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->owner);
    }

    // ─── ۲) مالک سالن ────────────────────────────────────────

    public function test_super_admin_updates_owner_name_phone_and_password(): void
    {
        $this->actingAs($this->super)->get(route('superadmin.salons.edit', $this->salon))->assertOk()
            ->assertSee('data-owner-card', false)->assertSee('09121112233');

        $this->actingAs($this->super)->put(route('superadmin.salons.owner', $this->salon), [
            'owner_name' => 'مالک تازه', 'owner_phone' => '۰۹۱۲۹۹۹۸۸۷۷', 'owner_password' => 'StrongPass99', 'owner_password_confirmation' => 'StrongPass99',
        ])->assertSessionHas('success');

        $owner = $this->owner->fresh();
        $this->assertSame('مالک تازه', $owner->name);
        $this->assertSame('09129998877', $owner->phone);
        $this->assertTrue(Hash::check('StrongPass99', $owner->password));

        $log = SuperAdminAuditLog::where('action', 'salon.owner')->firstOrFail();
        $this->assertSame(['09121112233', '09129998877'], $log->changes['phone']);
        $this->assertSame('عوض شد', $log->changes['password'][1]);
        $this->assertStringNotContainsString('StrongPass99', json_encode($log->changes));
    }

    public function test_owner_phone_must_be_unique_among_staff(): void
    {
        User::factory()->create(['is_admin' => true, 'user_type' => 'staff', 'phone' => '09125556677']);

        $this->actingAs($this->super)->from(route('superadmin.salons.edit', $this->salon))
            ->put(route('superadmin.salons.owner', $this->salon), ['owner_name' => 'x', 'owner_phone' => '09125556677'])
            ->assertSessionHasErrors('owner_phone');
        $this->assertSame('09121112233', $this->owner->fresh()->phone);
    }

    // ─── ۳) صندوق اعلان ──────────────────────────────────────

    public function test_platform_events_land_in_the_super_admin_inbox(): void
    {
        $this->post(route('demo-requests.store'), ['name' => 'سارا محمدی', 'salon' => 'سالن رزگلد', 'phone' => '09123456789', 'type' => 'nails']);
        app(SupportTicketService::class)->createTicket($this->owner->id, ['title' => 'درگاه وصل نمی‌شود', 'description' => 'توضیح', 'category' => 'payment']);

        $alerts = UserNotification::where('notifiable_id', $this->super->id)->where('type', PlatformAlertNotification::class)->get();
        $this->assertEqualsCanonicalizing(['demo', 'ticket'], $alerts->pluck('data.kind')->all());
        $this->assertSame(0, UserNotification::where('notifiable_id', $this->owner->id)->where('type', PlatformAlertNotification::class)->count());

        $this->actingAs($this->super)->get(route('superadmin.notifications.index'))->assertOk()
            ->assertSee('درگاه وصل نمی‌شود')->assertSee('سارا محمدی')->assertSee('data-inbox-badge', false);

        $ticketAlert = $alerts->firstWhere('data.kind', 'ticket');
        $this->actingAs($this->super)->get(route('superadmin.notifications.open', $ticketAlert->id))->assertRedirect();
        $this->assertNotNull($ticketAlert->fresh()->read_at);

        $this->actingAs($this->super)->post(route('superadmin.notifications.read-all'));
        $this->assertSame(0, UserNotification::where('notifiable_id', $this->super->id)->whereNull('read_at')->count());
    }

    public function test_a_failed_queue_job_alerts_once_per_half_hour(): void
    {
        foreach ([1, 2] as $_) {
            try {
                dispatch(new \Tests\Feature\SuperAdmin\Fixtures\AlwaysFailingJob);
            } catch (\Throwable) {
            }
        }

        $alerts = UserNotification::where('notifiable_id', $this->super->id)->where('type', PlatformAlertNotification::class)->get();
        $this->assertCount(1, $alerts);
        $this->assertSame('job_failed', $alerts->first()->data['kind']);
        $this->assertStringContainsString('AlwaysFailingJob', $alerts->first()->data['message']);
    }

    // ─── ۴) اطلاعیه‌ی سراسری ──────────────────────────────────

    public function test_announcements_show_on_the_chosen_panels_within_their_window(): void
    {
        $this->actingAs($this->super)->post(route('superadmin.announcements.store'), [
            'title' => 'به‌روزرسانی شبانه', 'body' => 'امشب چند دقیقه قطعی داریم.', 'level' => 'warning', 'audience' => 'admins', 'is_active' => '1',
        ])->assertRedirect(route('superadmin.announcements.index'));
        $this->assertTrue(SuperAdminAuditLog::where('action', 'announcement.saved')->exists());

        $this->actingAs($this->owner)->get(route('admin.dashboard'))->assertOk()->assertSee('به‌روزرسانی شبانه');

        $specialist = Specialist::factory()->create();
        $this->actingAs(User::find($specialist->user_id))->get(route('specialist.my-dashboard'))->assertOk()->assertDontSee('به‌روزرسانی شبانه');

        PlatformAnnouncement::query()->update(['audience' => 'all', 'level' => 'critical']);
        $html = $this->actingAs(User::find($specialist->user_id))->get(route('specialist.my-dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('به‌روزرسانی شبانه', $html);
        $this->assertStringNotContainsString('بستن اطلاعیه', $html, '«مهم / قطعی» بستنی نیست');

        PlatformAnnouncement::query()->update(['ends_at' => now()->subMinute()]);
        $this->actingAs($this->owner)->get(route('admin.dashboard'))->assertOk()->assertDontSee('به‌روزرسانی شبانه');
    }

    public function test_announcement_rejects_end_before_start(): void
    {
        $this->actingAs($this->super)->from(route('superadmin.announcements.create'))->post(route('superadmin.announcements.store'), [
            'title' => 't', 'body' => 'b', 'level' => 'info', 'audience' => 'admins', 'starts_at' => '1405/08/10', 'ends_at' => '1405/08/01',
        ])->assertSessionHasErrors('ends_at');
        $this->assertSame(0, PlatformAnnouncement::count());
    }

    // ─── ۶) وضعیت پشتیبان‌گیری ───────────────────────────────

    public function test_dashboard_shows_the_last_backup_state(): void
    {
        $this->actingAs($this->super)->get(route('superadmin.dashboard'))->assertOk()
            ->assertSee('data-backup-state="never"', false)->assertSee('هیچ پشتیبانی ثبت نشده');

        file_put_contents(storage_path('app/backup-status.json'), json_encode(['status' => 'ok', 'at' => now()->subHours(3)->toIso8601String(), 'size' => '120M', 'dest' => '/x']));
        $this->actingAs($this->super)->get(route('superadmin.dashboard'))->assertOk()
            ->assertSee('data-backup-state="ok"', false)->assertSee('120M');

        file_put_contents(storage_path('app/backup-status.json'), json_encode(['status' => 'ok', 'at' => now()->subHours(50)->toIso8601String()]));
        $this->actingAs($this->super)->get(route('superadmin.dashboard'))->assertSee('data-backup-state="stale"', false);

        file_put_contents(storage_path('app/backup-status.json'), json_encode(['status' => 'failed', 'at' => now()->toIso8601String(), 'message' => 'exit code 2']));
        $this->actingAs($this->super)->get(route('superadmin.dashboard'))->assertSee('data-backup-state="failed"', false)->assertSee('exit code 2');
    }

    // ─── ۷) جستجوی شماره ─────────────────────────────────────

    public function test_phone_search_finds_people_across_salons(): void
    {
        $other = Salon::factory()->create(['slug' => 'other-search', 'name' => 'سالن دیگر']);
        $sp = Specialist::factory()->create(['name' => 'متخصص سالن دیگر', 'phone' => '09121112299']);
        Specialist::withoutGlobalScopes()->whereKey($sp->id)->toBase()->update(['salon_id' => $other->id]);
        DemoRequest::create(['name' => 'دموخواه', 'phone' => '09121112233']);

        $this->actingAs($this->super)->get(route('superadmin.search.index', ['q' => '+98 912 111 2233']))->assertOk()
            ->assertSee('مالک آزمایشی')->assertSee('مالک سالن')->assertSee($this->salon->name)->assertSee('دموخواه');

        $this->actingAs($this->super)->get(route('superadmin.search.index', ['q' => '۱۱۱۲۲۹۹']))->assertOk()
            ->assertSee('متخصص سالن دیگر')->assertSee('سالن دیگر');

        $this->actingAs($this->super)->get(route('superadmin.search.index', ['q' => '091']))->assertOk()->assertSee('دست‌کم ۴ رقم');
    }

    // ─── ۸) دسترسی قالب‌ها ───────────────────────────────────

    public function test_template_allowances_follow_the_decided_table_and_are_editable(): void
    {
        app(DefaultPlans::class)->sync();
        $full = Plan::byKey('full');
        $pro = Plan::byKey('pro');

        $this->assertSame([4, 4, 6, 12], [$full->siteThemeAllowance('1m'), $full->siteThemeAllowance('3m'), $full->siteThemeAllowance('6m'), $full->siteThemeAllowance('12m')]);
        $this->assertSame([4, 5, 6], [$pro->siteThemeAllowance('1m'), $pro->siteThemeAllowance('6m'), $pro->siteThemeAllowance('12m')]);
        $this->assertSame(12, Plan::byKey('clinic')->siteThemeAllowance('1m'));
        $this->assertSame(3, Plan::byKey('clinic')->adminThemeAllowance());
        $this->assertSame(0, Plan::byKey('basic')->siteThemeAllowance('3m'));
        $this->assertSame(2, Plan::byKey('basic')->siteThemeAllowance('12m'));

        $this->actingAs($this->super)->get(route('superadmin.templates.index'))->assertOk()->assertSee('data-template-plan="clinic"', false);

        $payload = Plan::query()->get()->mapWithKeys(fn (Plan $p) => [$p->id => $p->only(['theme_site_count', 'theme_site_count_6m', 'theme_site_count_12m', 'theme_admin_count'])])->all();
        $payload[$pro->id] = ['theme_site_count' => 4, 'theme_site_count_6m' => 6, 'theme_site_count_12m' => 12, 'theme_admin_count' => 1];
        $this->actingAs($this->super)->put(route('superadmin.templates.update'), ['plans' => $payload])->assertSessionHas('success');
        $this->assertSame(12, $pro->fresh()->siteThemeAllowance('12m'));
        $this->assertSame(1, SuperAdminAuditLog::where('action', 'template_access.updated')->count(), 'فقط پلنی که عوض شد');

        $payload[$pro->id]['theme_site_count_12m'] = 5;
        $this->actingAs($this->super)->from(route('superadmin.templates.index'))->put(route('superadmin.templates.update'), ['plans' => $payload])
            ->assertSessionHasErrors("plans.{$pro->id}.theme_site_count_12m");
        $this->assertSame(12, $pro->fresh()->theme_site_count_12m);
    }
}
