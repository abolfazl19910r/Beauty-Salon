<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TwoFactorController (security.2fa.*) had zero test coverage before this session, despite
 * this exact flow having a documented history of critical bugs (the code stored in Cache with
 * the `array` driver never actually persisted across requests, and hash comparison used a
 * loose === on a numeric code — both fixed under "باگ بحرانی OTP دو مرحله‌ای" in
 * Rasta_unified_prompt.md). This is the first HTTP-level regression guard for that flow.
 */
class TwoFactorControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['two_factor_enabled' => false]);
    }

    public function test_show_renders_the_2fa_status_page(): void
    {
        $response = $this->actingAs($this->user)->get('/security/2fa');

        $response->assertOk();
        $response->assertViewHas('enabled', false);
    }

    public function test_show_setup_generates_a_code_and_persists_it(): void
    {
        $response = $this->actingAs($this->user)->get('/security/2fa/setup');

        $response->assertOk();
        $this->assertNotNull($this->user->fresh()->two_factor_code);
        $this->assertNotNull($this->user->fresh()->two_factor_code_expires_at);
    }

    public function test_show_setup_redirects_when_already_enabled(): void
    {
        $this->user->update(['two_factor_enabled' => true]);

        $response = $this->actingAs($this->user)->get('/security/2fa/setup');

        $response->assertRedirect(route('security.2fa'));
        $response->assertSessionHas('error');
    }

    public function test_enable_activates_2fa_with_a_valid_code(): void
    {
        $this->user->update([
            'two_factor_code' => '123456',
            'two_factor_code_expires_at' => now()->addMinutes(2),
        ]);

        $response = $this->actingAs($this->user)->postJson('/security/2fa/enable', [
            'code' => '123456',
        ]);

        $response->assertOk();
        $this->assertTrue($this->user->fresh()->two_factor_enabled);
        // The code is consumed after a successful verification (single-use).
        $this->assertNull($this->user->fresh()->two_factor_code);
    }

    public function test_enable_rejects_an_invalid_code(): void
    {
        $this->user->update([
            'two_factor_code' => '123456',
            'two_factor_code_expires_at' => now()->addMinutes(2),
        ]);

        $response = $this->actingAs($this->user)->postJson('/security/2fa/enable', [
            'code' => '000000',
        ]);

        $response->assertStatus(422);
        $this->assertFalse($this->user->fresh()->two_factor_enabled);
    }

    public function test_enable_rejects_an_expired_code(): void
    {
        $this->user->update([
            'two_factor_code' => '123456',
            'two_factor_code_expires_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($this->user)->postJson('/security/2fa/enable', [
            'code' => '123456',
        ]);

        $response->assertStatus(422);
    }

    public function test_disable_deactivates_2fa_with_a_valid_code(): void
    {
        $this->user->update([
            'two_factor_enabled' => true,
            'two_factor_code' => '654321',
            'two_factor_code_expires_at' => now()->addMinutes(2),
        ]);

        $response = $this->actingAs($this->user)->postJson('/security/2fa/disable', [
            'code' => '654321',
        ]);

        $response->assertOk();
        $this->assertFalse($this->user->fresh()->two_factor_enabled);
    }

    public function test_verify_sets_the_2fa_verified_session_flag(): void
    {
        $this->user->update([
            'two_factor_code' => '111222',
            'two_factor_code_expires_at' => now()->addMinutes(2),
        ]);

        $response = $this->actingAs($this->user)->postJson('/security/2fa/verify', [
            'code' => '111222',
        ]);

        $response->assertOk();
        $this->assertTrue(session('2fa_verified'));
    }

    public function test_verify_does_not_set_the_session_flag_for_a_wrong_code(): void
    {
        $this->user->update([
            'two_factor_code' => '111222',
            'two_factor_code_expires_at' => now()->addMinutes(2),
        ]);

        $this->actingAs($this->user)->postJson('/security/2fa/verify', [
            'code' => '999999',
        ]);

        $this->assertFalse((bool) session('2fa_verified'));
    }

    public function test_resend_generates_a_fresh_code_replacing_the_old_one(): void
    {
        $this->user->update([
            'two_factor_code' => '111111',
            'two_factor_code_expires_at' => now()->addMinutes(2),
        ]);

        $response = $this->actingAs($this->user)->postJson('/security/2fa/resend');

        $response->assertOk();
        $this->assertNotSame('111111', $this->user->fresh()->two_factor_code);
    }

    public function test_validation_failures_return_a_proper_422_not_a_swallowed_500(): void
    {
        // ⭐ Regression guard (real bug found+fixed this session): all four action methods
        // wrapped $request->validate() inside a broad catch(\Exception $e) block, which
        // also catches Laravel's own ValidationException — converting what should be a
        // normal 422 with field errors into a generic 500 "system error" response. This is
        // the same bug fingerprint documented for AdminSpecialistScheduleController::update()
        // in a previous test-writing session, this time in TwoFactorController.
        $response = $this->actingAs($this->user)->postJson('/security/2fa/verify', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('code');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/security/2fa')->assertRedirect(route('login'));
    }
}
