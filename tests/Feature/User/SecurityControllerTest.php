<?php

namespace Tests\Feature\User;

use App\Models\SecurityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The customer-facing security dashboard (SecurityController::dashboard/sessions/activity/
 * terminateSession/terminateAllSessions) was built in the "تکمیل داشبورد امنیتی حساب
 * کاربری" session but never got its own HTTP test — only the login-time SecurityLog writes
 * were covered (SecurityLogLoginTest). This is the first coverage of the dashboard itself.
 */
class SecurityControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function seedSession(User $user, ?string $id = null): string
    {
        $id = $id ?? \Illuminate\Support\Str::random(40);
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => base64_encode('test'),
            'last_activity' => now()->timestamp,
        ]);

        return $id;
    }

    public function test_dashboard_renders_with_security_score_and_session_count(): void
    {
        $this->seedSession($this->user, session()->getId());
        SecurityLog::factory()->create(['user_id' => $this->user->id, 'level' => 'info']);

        $response = $this->actingAs($this->user)->get('/security/dashboard');

        $response->assertOk();
        $response->assertViewHas('active_sessions_count', 1);
        $response->assertViewHas('security_score');
    }

    public function test_sessions_lists_only_the_authenticated_users_sessions(): void
    {
        $this->seedSession($this->user, session()->getId());
        $other = User::factory()->create();
        $this->seedSession($other);

        $response = $this->actingAs($this->user)->get('/security/sessions');

        $response->assertOk();
        $this->assertCount(1, $response->viewData('sessions'));
    }

    public function test_activity_paginates_the_users_own_logs(): void
    {
        SecurityLog::factory()->count(3)->create(['user_id' => $this->user->id]);
        $other = User::factory()->create();
        SecurityLog::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user)->get('/security/activity');

        $response->assertOk();
        $this->assertCount(3, $response->viewData('logs'));
    }

    public function test_terminate_session_deletes_another_session_belonging_to_the_user(): void
    {
        $otherSessionId = $this->seedSession($this->user);

        $response = $this->actingAs($this->user)
            ->postJson("/security/sessions/{$otherSessionId}/terminate");

        $response->assertOk();
        $this->assertDatabaseMissing('sessions', ['id' => $otherSessionId]);
    }

    public function test_terminate_session_refuses_to_terminate_the_current_session(): void
    {
        // The 'array' session driver used for tests doesn't carry a session id across
        // separate TestCase HTTP calls, so exercising "is this the current session" purely
        // through the HTTP kernel is unreliable here. Call the controller action directly
        // instead, in-process, where session()->getId() really is the same instance the
        // controller reads.
        $this->actingAs($this->user);
        session()->setId('forced-current-session-id-40-characters-x');
        $currentId = session()->getId();
        $this->seedSession($this->user, $currentId);

        $response = app(\App\Http\Controllers\User\SecurityController::class)
            ->terminateSession($currentId);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertDatabaseHas('sessions', ['id' => $currentId]);
    }

    public function test_terminate_session_cannot_delete_another_users_session(): void
    {
        $other = User::factory()->create();
        $otherSessionId = $this->seedSession($other);

        $this->actingAs($this->user)
            ->postJson("/security/sessions/{$otherSessionId}/terminate");

        // The query is scoped to auth()->id(), so another user's session must survive.
        $this->assertDatabaseHas('sessions', ['id' => $otherSessionId]);
    }

    public function test_terminate_all_sessions_removes_every_session_except_the_current_one(): void
    {
        $this->actingAs($this->user);
        session()->setId('forced-current-session-id-40-characters-x');
        $currentId = session()->getId();
        $this->seedSession($this->user, $currentId);
        $otherSessionId = $this->seedSession($this->user);

        $response = app(\App\Http\Controllers\User\SecurityController::class)
            ->terminateAllSessions();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertDatabaseHas('sessions', ['id' => $currentId]);
        $this->assertDatabaseMissing('sessions', ['id' => $otherSessionId]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/security/dashboard')->assertRedirect(route('login'));
    }
}
