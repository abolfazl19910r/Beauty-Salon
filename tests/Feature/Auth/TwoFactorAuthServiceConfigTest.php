<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression guard (test-writing session 11): TwoFactorAuthService::generateCode() used to
 * hardcode a fixed 6-digit code (random_int(100000, 999999)) and a fixed 2-minute expiry,
 * completely ignoring the TWO_FACTOR_CODE_LENGTH/TWO_FACTOR_TIMEOUT keys .env.example shipped
 * (and that were never actually read anywhere in the app). It now reads
 * services.two_factor.code_length / services.two_factor.timeout_minutes.
 */
class TwoFactorAuthServiceConfigTest extends TestCase
{
    use RefreshDatabase;

    private TwoFactorAuthService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TwoFactorAuthService::class);
        $this->user = User::factory()->create();
    }

    public function test_generated_code_has_six_digits_by_default(): void
    {
        $code = $this->service->generateCode($this->user);

        $this->assertSame(6, strlen($code));
        $this->assertMatchesRegularExpression('/^[1-9][0-9]{5}$/', $code);
    }

    public function test_generated_code_length_respects_a_configured_override(): void
    {
        config(['services.two_factor.code_length' => 4]);

        $code = $this->service->generateCode($this->user);

        $this->assertSame(4, strlen($code));
        $this->assertMatchesRegularExpression('/^[1-9][0-9]{3}$/', $code);
    }

    public function test_generated_code_length_is_never_forced_below_four_digits(): void
    {
        // A misconfigured TWO_FACTOR_CODE_LENGTH=1 should not produce a trivially guessable
        // 1-digit code; the service clamps to a 4-digit minimum.
        config(['services.two_factor.code_length' => 1]);

        $code = $this->service->generateCode($this->user);

        $this->assertSame(4, strlen($code));
    }

    public function test_code_expiry_respects_the_default_two_minute_timeout(): void
    {
        $this->service->generateCode($this->user);

        $expiresAt = $this->user->fresh()->two_factor_code_expires_at;
        $this->assertEqualsWithDelta(now()->addMinutes(2)->timestamp, $expiresAt->timestamp, 5);
    }

    public function test_code_expiry_respects_a_configured_timeout_override(): void
    {
        config(['services.two_factor.timeout_minutes' => 10]);

        $this->service->generateCode($this->user);

        $expiresAt = $this->user->fresh()->two_factor_code_expires_at;
        $this->assertEqualsWithDelta(now()->addMinutes(10)->timestamp, $expiresAt->timestamp, 5);
    }

    public function test_a_code_generated_with_a_longer_configured_length_still_verifies_correctly(): void
    {
        config(['services.two_factor.code_length' => 8]);
        $code = $this->service->generateCode($this->user);

        $this->assertTrue($this->service->verify($this->user, $code));
    }
}
