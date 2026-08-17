<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Regression guard (test-writing session 7): .env.example ships KAVENEGAR_SENDER and all
 * four KAVENEGAR_TEMPLATE_* keys as present-but-empty ("KEY="), exactly the same shape that
 * previously broke SECURITY_LOG_LEVEL/PAYMENTS_LOG_LEVEL. env('KEY', 'default') only falls
 * back for a genuinely undefined variable, not an empty string — so a fresh deployment that
 * copies .env.example without filling these in would silently call the real Kavenegar API
 * with an empty sender id / empty template name instead of the intended default, rather than
 * falling back safely. This simulates that exact shape (key defined, value empty) directly
 * against getenv(), independent of whatever this environment's real .env happens to contain.
 */
class KavenegarConfigFallbackTest extends TestCase
{
    private array $keys = [
        'KAVENEGAR_SENDER',
        'KAVENEGAR_TEMPLATE_LOGIN',
        'KAVENEGAR_TEMPLATE_REGISTER',
        'KAVENEGAR_TEMPLATE_RESET',
        'KAVENEGAR_TEMPLATE_2FA',
    ];

    private array $originalValues = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ($this->keys as $key) {
            $this->originalValues[$key] = getenv($key);
            // Simulate .env.example's shape: the key is present but empty.
            putenv("{$key}=");
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->keys as $key) {
            if ($this->originalValues[$key] === false) {
                putenv($key);
            } else {
                putenv("{$key}={$this->originalValues[$key]}");
            }
        }

        parent::tearDown();
    }

    public function test_kavenegar_config_keys_never_resolve_to_an_empty_string(): void
    {
        // Re-evaluate config/services.php fresh so it reads the simulated empty getenv()
        // values above, rather than whatever Laravel already cached at boot.
        $services = require base_path('config/services.php');

        $this->assertSame('2000660110', $services['kavenegar']['sender']);
        $this->assertSame('login-verify', $services['kavenegar']['templates']['login_verify']);
        $this->assertSame('register-verify', $services['kavenegar']['templates']['register_verify']);
        $this->assertSame('reset-password', $services['kavenegar']['templates']['reset_password']);
        $this->assertSame('two-factor-auth', $services['kavenegar']['templates']['two_factor_auth']);
    }
}
