<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_code_can_be_requested_for_an_existing_phone_number(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/forgot-password', ['phone' => $user->phone]);

        $response->assertRedirect();
        $this->assertNotNull($user->fresh()->verification_code);
        $this->assertDatabaseHas('password_reset_tokens', ['phone' => $user->phone]);
    }

    public function test_reset_code_request_fails_for_an_unregistered_phone_number(): void
    {
        $response = $this->from('/forgot-password')->post('/forgot-password', ['phone' => '09129999999']);

        $response->assertSessionHasErrors('phone');
    }

    public function test_reset_screen_can_be_rendered_with_a_valid_token(): void
    {
        $user = User::factory()->create();
        $this->post('/forgot-password', ['phone' => $user->phone]);
        $token = DB::table('password_reset_tokens')->where('phone', $user->phone)->value('token');

        $response = $this->get('/reset-password/'.$token);

        $response->assertStatus(200);
    }

    public function test_reset_screen_redirects_away_for_an_invalid_token(): void
    {
        $response = $this->get('/reset-password/not-a-real-token');

        $response->assertRedirect(route('password.request'));
    }

    public function test_password_can_be_reset_with_a_valid_code_and_token(): void
    {
        $user = User::factory()->create();
        $this->post('/forgot-password', ['phone' => $user->phone]);
        $user->refresh();
        $token = DB::table('password_reset_tokens')->where('phone', $user->phone)->value('token');

        $response = $this->post('/reset-password', [
            'token' => $token,
            'code' => $user->verification_code,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_password_reset_fails_with_the_wrong_code(): void
    {
        $user = User::factory()->create();
        $this->post('/forgot-password', ['phone' => $user->phone]);
        $token = DB::table('password_reset_tokens')->where('phone', $user->phone)->value('token');
        $originalPassword = $user->password;

        $response = $this->from('/reset-password/'.$token)->post('/reset-password', [
            'token' => $token,
            'code' => '000000',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function test_password_reset_token_is_consumed_after_successful_reset(): void
    {
        $user = User::factory()->create();
        $this->post('/forgot-password', ['phone' => $user->phone]);
        $user->refresh();
        $token = DB::table('password_reset_tokens')->where('phone', $user->phone)->value('token');

        $this->post('/reset-password', [
            'token' => $token,
            'code' => $user->verification_code,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $this->assertDatabaseMissing('password_reset_tokens', ['phone' => $user->phone]);
    }

    /**
     * Regression guard (test-writing session 11): sendCode() used to hardcode
     * now()->addMinutes(2) regardless of the RESET_CODE_EXPIRE_MINUTES env key. It now reads
     * auth.reset_code_expire_minutes, so overriding that config at runtime should be reflected
     * in the persisted expiry timestamp.
     */
    public function test_reset_code_expiry_respects_the_configured_expire_minutes(): void
    {
        config(['auth.reset_code_expire_minutes' => 20]);
        $user = User::factory()->create();

        $this->post('/forgot-password', ['phone' => $user->phone]);

        $expiresAt = $user->fresh()->verification_code_expire_at;
        $this->assertNotNull($expiresAt);
        $this->assertEqualsWithDelta(
            now()->addMinutes(20)->timestamp,
            $expiresAt->timestamp,
            5
        );
    }
}
