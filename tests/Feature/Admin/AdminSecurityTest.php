<?php

namespace Tests\Feature\Admin;

use App\Models\SecurityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The security dashboard/panel (built in the session-4 "complete security dashboard" work)
 * previously had zero test coverage. This is the first full HTTP-level pass over it.
 */
class AdminSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_logs_page_lists_paginated_logs_with_stats(): void
    {
        SecurityLog::factory()->count(3)->create(['event' => 'login_attempt', 'level' => 'info']);
        SecurityLog::factory()->count(2)->create(['event' => 'login_attempt', 'level' => 'warning']);

        $response = $this->actingAs($this->admin)->get('/admin/security/logs');

        $response->assertOk();
        $this->assertCount(5, $response->viewData('logs'));
        $this->assertSame(5, $response->viewData('stats')['logs_last_30_days']);
        $this->assertSame(2, $response->viewData('stats')['warnings_last_30_days']);
    }

    public function test_logs_page_filters_by_event_and_level(): void
    {
        SecurityLog::factory()->create(['event' => 'login_attempt', 'level' => 'warning']);
        SecurityLog::factory()->create(['event' => 'session_terminated', 'level' => 'info']);

        $response = $this->actingAs($this->admin)->get('/admin/security/logs?event=login_attempt');
        $this->assertCount(1, $response->viewData('logs'));

        $response = $this->actingAs($this->admin)->get('/admin/security/logs?level=warning');
        $this->assertCount(1, $response->viewData('logs'));
    }

    public function test_logs_page_filters_by_date_range(): void
    {
        SecurityLog::factory()->create(['created_at' => now()->subDays(10)]);
        SecurityLog::factory()->create(['created_at' => now()]);

        $response = $this->actingAs($this->admin)->get('/admin/security/logs?date_from='.now()->subDay()->format('Y-m-d'));

        $this->assertCount(1, $response->viewData('logs'));
    }

    /**
     * ⭐ Fix: previously this page used the browser's native <input type="date">, unlike every
     * other date-filter page in the admin panel (reports, announcements, discount codes, ...),
     * which all use the project's self-contained jcal (Jalali calendar) widget. Now it must render
     * the same jcal markup/script and readonly Jalali display inputs instead of native date pickers.
     */
    public function test_logs_page_renders_the_project_jcal_widget_not_native_date_inputs(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/security/logs');

        $response->assertOk();
        $response->assertSee('jcal-wrapper', false);
        $response->assertSee('id="date-from-jalali"', false);
        $response->assertSee('id="date-to-jalali"', false);
        $response->assertSee('name="date_from"', false);
        $response->assertSee('name="date_to"', false);
        $response->assertDontSee('type="date"', false);
    }

    public function test_logs_page_jcal_prefills_the_jalali_display_value_from_the_gregorian_query(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/security/logs?date_from=2026-01-01&date_to=2026-01-31');

        $response->assertOk();
        $response->assertSee(jalali_date('2026-01-01', 'Y/m/d'));
        $response->assertSee(jalali_date('2026-01-31', 'Y/m/d'));
    }

    public function test_stats_counts_failed_logins_within_the_last_24_hours_only(): void
    {
        SecurityLog::factory()->create(['event' => 'login_attempt', 'level' => 'warning', 'created_at' => now()->subHours(2)]);
        SecurityLog::factory()->create(['event' => 'login_attempt', 'level' => 'warning', 'created_at' => now()->subDays(2)]);

        $response = $this->actingAs($this->admin)->get('/admin/security/logs');

        $this->assertSame(1, $response->viewData('stats')['failed_logins_last_24h']);
    }

    public function test_stats_counts_users_with_2fa_enabled(): void
    {
        User::factory()->create(['two_factor_enabled' => true]);
        User::factory()->create(['two_factor_enabled' => true]);
        User::factory()->create(['two_factor_enabled' => false]);

        $response = $this->actingAs($this->admin)->get('/admin/security/logs');

        // +1 because $this->admin itself defaults to two_factor_enabled = false via factory
        $this->assertSame(2, $response->viewData('stats')['users_with_2fa']);
    }

    public function test_users_page_lists_users_with_suspicious_activity_count_and_last_login(): void
    {
        $user = User::factory()->create();
        SecurityLog::factory()->create([
            'user_id' => $user->id,
            'event' => 'login_attempt',
            'level' => 'warning',
            'created_at' => now()->subDays(5),
        ]);
        SecurityLog::factory()->create([
            'user_id' => $user->id,
            'event' => 'login_attempt',
            'level' => 'info',
            'created_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/security/users');

        $response->assertOk();
        $listedUser = $response->viewData('users')->firstWhere('id', $user->id);
        $this->assertSame(1, $listedUser->suspicious_activity_count);
        $this->assertNotNull($listedUser->last_successful_login_at);
    }

    public function test_users_page_excludes_warnings_older_than_30_days_from_suspicious_count(): void
    {
        $user = User::factory()->create();
        SecurityLog::factory()->create([
            'user_id' => $user->id,
            'event' => 'login_attempt',
            'level' => 'warning',
            'created_at' => now()->subDays(45),
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/security/users');

        $listedUser = $response->viewData('users')->firstWhere('id', $user->id);
        $this->assertSame(0, $listedUser->suspicious_activity_count);
    }

    public function test_users_page_search_filters_by_name_or_phone(): void
    {
        User::factory()->create(['name' => 'علی رضایی', 'phone' => '09121112233']);
        User::factory()->create(['name' => 'حسن محمدی', 'phone' => '09354445566']);

        $response = $this->actingAs($this->admin)->get('/admin/security/users?search=رضایی');

        $names = $response->viewData('users')->pluck('name');
        $this->assertTrue($names->contains('علی رضایی'));
        $this->assertFalse($names->contains('حسن محمدی'));
    }

    public function test_settings_page_renders_with_defaults_when_no_row_exists_yet(): void
    {
        $this->assertDatabaseCount('security_settings', 0);

        $response = $this->actingAs($this->admin)->get('/admin/security/settings');

        $response->assertOk();
        $this->assertNotNull($response->viewData('settings'));
        // SecuritySetting::get() lazily creates the singleton row, just like WalletSetting::get().
        $this->assertDatabaseCount('security_settings', 1);
    }

    public function test_update_settings_persists_password_expiry_days(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/security/settings', [
            'password_expiry_days' => 60,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('security_settings', ['password_expiry_days' => 60]);
    }

    public function test_update_settings_rejects_an_expiry_below_30_days(): void
    {
        $response = $this->actingAs($this->admin)->from('/admin/security/settings')->post('/admin/security/settings', [
            'password_expiry_days' => 10,
        ]);

        $response->assertSessionHasErrors('password_expiry_days');
        $this->assertDatabaseMissing('security_settings', ['password_expiry_days' => 10]);
    }

    public function test_update_settings_rejects_an_expiry_above_365_days(): void
    {
        $response = $this->actingAs($this->admin)->from('/admin/security/settings')->post('/admin/security/settings', [
            'password_expiry_days' => 400,
        ]);

        $response->assertSessionHasErrors('password_expiry_days');
    }

    public function test_non_admin_cannot_access_any_security_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/security/logs')->assertStatus(403);
        $this->actingAs($user)->get('/admin/security/users')->assertStatus(403);
        $this->actingAs($user)->get('/admin/security/settings')->assertStatus(403);
    }
}
