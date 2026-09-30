<?php

namespace Tests\Feature\Admin;

use App\Models\SecurityLog;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * صفحه‌ی «کاربران» بخش امنیت برای هر ردیف (۲۰ در صفحه) آخرین ورود موفق را با یک کوئری جدا می‌خواند (N+1؛ اندازه‌گیری ۲۰۲۶-۰۹-۳۰).
 */
class SecurityUsersPageQueryCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_last_successful_login_is_loaded_with_the_page_not_per_user(): void
    {
        $this->freezeTime();
        $salon = app(CurrentSalon::class)->get();
        $admin = User::factory()->create(['is_admin' => true]);
        $users = User::factory()->count(8)->create(['user_type' => 'customer', 'salon_id' => $salon->id]);
        foreach ($users as $i => $user) {
            SecurityLog::factory()->create(['user_id' => $user->id, 'event' => 'login_attempt', 'level' => 'info', 'created_at' => now()->subHours($i + 1)]);
            SecurityLog::factory()->create(['user_id' => $user->id, 'event' => 'login_attempt', 'level' => 'info', 'created_at' => now()->subDays(3)]);
        }

        $securityLogQueries = 0;
        DB::listen(function ($q) use (&$securityLogQueries) {
            if (preg_match('/from [`"]security_logs[`"]/', $q->sql) && ! str_contains($q->sql, 'insert')) {
                $securityLogQueries++;
            }
        });

        $response = $this->actingAs($admin)->get('/admin/security/users')->assertOk();

        $this->assertLessThanOrEqual(1, $securityLogQueries, "{$securityLogQueries} security_logs queries for one page of users");
        $listed = $response->viewData('users')->firstWhere('id', $users[0]->id);
        $this->assertSame(now()->subHour()->format('Y-m-d H:i:s'), $listed->last_successful_login_at->format('Y-m-d H:i:s'), 'the latest successful login is shown');
    }
}
