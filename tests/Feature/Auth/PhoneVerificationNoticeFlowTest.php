<?php

namespace Tests\Feature\Auth;

use App\Jobs\SendPhoneVerificationCodeJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * ⭐ Test-writing session 10 (option A): full coverage for the real replacement of the
 * previously no-op 'verified' middleware. See PhoneVerificationMiddlewareGapTest (now
 * removed — superseded by these tests) and Rasta_unified_prompt.md for the full history
 * of why this was a no-op and what motivated fixing it (admin-created users/specialists
 * can log in via OTP without ever going through phone verification, since
 * verifyLoginCode() only clears the login code — it never calls markPhoneAsVerified()).
 */
class PhoneVerificationNoticeFlowTest extends TestCase
{
    use RefreshDatabase;

    // ── Middleware gating ───────────────────────────────────────────────

    public function test_an_unverified_user_is_redirected_to_the_notice_page_and_the_intended_url_is_stashed(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('security.dashboard'));

        $response->assertRedirect(route('verification.notice'));
        $this->assertSame(route('security.dashboard'), session('phone_verification_intended_url'));
    }

    public function test_a_verified_user_passes_straight_through(): void
    {
        $user = User::factory()->create(); // default factory state is already phone-verified

        $this->actingAs($user)
            ->get(route('security.dashboard'))
            ->assertOk();
    }

    public function test_a_guest_is_redirected_to_login_not_the_verification_notice(): void
    {
        $this->get(route('security.dashboard'))->assertRedirect(route('login'));
    }

    public function test_a_json_request_from_an_unverified_user_gets_a_428_with_a_redirect_hint(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)
            ->getJson(route('security.dashboard'));

        $response->assertStatus(428);
        $response->assertJson([
            'success' => false,
            'phone_verification_required' => true,
            'redirect_url' => route('verification.notice'),
        ]);
    }

    // ── notice() ─────────────────────────────────────────────────────────

    public function test_notice_sends_a_code_and_renders_the_form(): void
    {
        Queue::fake();

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertOk();
        $response->assertViewIs('auth.verify-phone');
        Queue::assertPushed(SendPhoneVerificationCodeJob::class, 1);
        $this->assertNotNull($user->fresh()->verification_code);
    }

    public function test_notice_does_not_send_a_second_code_on_a_repeat_visit_within_the_same_session(): void
    {
        Queue::fake();

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('verification.notice'));
        $this->actingAs($user)->get(route('verification.notice'));

        Queue::assertPushed(SendPhoneVerificationCodeJob::class, 1);
    }

    public function test_notice_redirects_an_already_verified_user_to_their_intended_url(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['phone_verification_intended_url' => route('security.dashboard')])
            ->get(route('verification.notice'));

        $response->assertRedirect(route('security.dashboard'));
    }

    public function test_notice_redirects_an_already_verified_user_with_no_intended_url_to_their_home(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertRedirect(url('/dashboard'));
    }

    public function test_guest_cannot_access_the_notice_page(): void
    {
        $this->get(route('verification.notice'))->assertRedirect(route('login'));
    }

    // ── verify() ─────────────────────────────────────────────────────────

    public function test_verify_with_the_correct_code_marks_the_phone_verified_and_redirects_to_the_intended_url(): void
    {
        $user = User::factory()->unverified()->withActiveOtp()->create();

        $response = $this->actingAs($user)
            ->withSession(['phone_verification_intended_url' => route('security.dashboard')])
            ->postJson(route('verification.verify'), ['code' => '123456']);

        $response->assertOk();
        $response->assertJson(['redirect_url' => route('security.dashboard')]);
        $this->assertTrue($user->fresh()->hasVerifiedPhone());
        $this->assertNull(session('phone_verification_intended_url'));
    }

    public function test_verify_with_the_correct_code_allows_the_now_verified_user_through_the_middleware(): void
    {
        $user = User::factory()->unverified()->withActiveOtp()->create();

        $this->actingAs($user)->postJson(route('verification.verify'), ['code' => '123456'])->assertOk();

        $this->actingAs($user)
            ->get(route('security.dashboard'))
            ->assertOk();
    }

    public function test_verify_with_the_wrong_code_fails_without_marking_verified(): void
    {
        $user = User::factory()->unverified()->withActiveOtp()->create();

        $response = $this->actingAs($user)
            ->postJson(route('verification.verify'), ['code' => '000000']);

        $response->assertStatus(422);
        $this->assertFalse($user->fresh()->hasVerifiedPhone());
    }

    public function test_verify_with_an_expired_code_fails(): void
    {
        $user = User::factory()->unverified()->create([
            'verification_code' => '123456',
            'verification_code_expire_at' => now()->subMinute(),
        ]);

        $this->actingAs($user)
            ->postJson(route('verification.verify'), ['code' => '123456'])
            ->assertStatus(422);
    }

    public function test_verify_validation_failure_returns_422_not_a_swallowed_500(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->postJson(route('verification.verify'), ['code' => 'abc'])
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson(route('verification.verify'), [])
            ->assertStatus(422);
    }

    public function test_guest_cannot_verify(): void
    {
        $this->postJson(route('verification.verify'), ['code' => '123456'])->assertStatus(401);
    }

    // ── resend() ─────────────────────────────────────────────────────────

    public function test_resend_dispatches_a_fresh_code_regardless_of_debounce_state(): void
    {
        Queue::fake();

        $user = User::factory()->unverified()->create();

        // Simulate the debounce flag already being set (e.g. the user already visited notice()).
        $this->withSession(['phone_verification_code_sent' => true]);

        $this->actingAs($user)
            ->postJson(route('verification.resend'))
            ->assertOk();

        Queue::assertPushed(SendPhoneVerificationCodeJob::class, 1);
    }

    public function test_guest_cannot_resend(): void
    {
        $this->postJson(route('verification.resend'))->assertStatus(401);
    }

    // ── End-to-end ───────────────────────────────────────────────────────

    public function test_full_flow_an_admin_created_never_verified_user_can_reach_a_protected_page_after_verifying(): void
    {
        // Mirrors the real motivating scenario: an admin-created account (or any
        // account that only ever went through login OTP, never registration OTP)
        // has phone_verified_at permanently null.
        $user = User::factory()->unverified()->withActiveOtp()->create();

        $blocked = $this->actingAs($user)->get(route('security.dashboard'));
        $blocked->assertRedirect(route('verification.notice'));

        $notice = $this->actingAs($user)->get(route('verification.notice'));
        $notice->assertOk();

        $realCode = $user->fresh()->verification_code;

        $verify = $this->actingAs($user)->postJson(route('verification.verify'), ['code' => $realCode]);
        $verify->assertOk();
        $verify->assertJson(['redirect_url' => route('security.dashboard')]);

        $this->actingAs($user)
            ->get(route('security.dashboard'))
            ->assertOk();
    }
}
