<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Regression guard (test-writing session 11, closing the last open item of the test-writing
 * phase): six keys shipped in .env.example were never read anywhere in the app — the values
 * they were meant to control were hardcoded constants instead:
 *
 *   TWO_FACTOR_TIMEOUT           -> TwoFactorAuthService (was: protected const CODE_EXPIRY_MINUTES = 2)
 *   TWO_FACTOR_CODE_LENGTH       -> TwoFactorAuthService (was: fixed random_int(100000, 999999), 6 digits)
 *   MAX_LOGIN_ATTEMPTS           -> RouteServiceProvider 'auth' rate limiter (was: Limit::perMinute(5))
 *   LOGIN_THROTTLE_MINUTES       -> RouteServiceProvider 'auth' rate limiter (was: 1-minute decay, implicit)
 *   RESET_CODE_EXPIRE_MINUTES    -> PasswordResetController::sendCode() (was: now()->addMinutes(2))
 *   PAYMENT_EXPIRY_MINUTES       -> SecurePaymentService (was: protected const EXPIRY_MINUTES = 15)
 *
 * All six are now read from config (services.two_factor.*, services.secure_payment.*,
 * auth.max_login_attempts, auth.login_throttle_minutes, auth.reset_code_expire_minutes), each
 * with the exact same effective default as the previous hardcoded value, using the
 * env('KEY') ?: default pattern established for SECURITY_LOG_LEVEL/PAYMENTS_LOG_LEVEL and the
 * Kavenegar keys, since .env.example ships every one of these six blank ("KEY=") rather than
 * undefined.
 */
class SessionElevenEnvKeysConfigTest extends TestCase
{
    private array $keys = [
        'TWO_FACTOR_TIMEOUT',
        'TWO_FACTOR_CODE_LENGTH',
        'MAX_LOGIN_ATTEMPTS',
        'LOGIN_THROTTLE_MINUTES',
        'RESET_CODE_EXPIRE_MINUTES',
        'PAYMENT_EXPIRY_MINUTES',
    ];

    private array $originalServerValues = [];

    private array $originalEnvValues = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ($this->keys as $key) {
            $this->originalServerValues[$key] = $_SERVER[$key] ?? null;
            $this->originalEnvValues[$key] = $_ENV[$key] ?? null;
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->keys as $key) {
            $this->restoreKey($key, $this->originalServerValues[$key], $this->originalEnvValues[$key]);
        }

        \Illuminate\Support\Env::getRepository();

        parent::tearDown();
    }

    private function setKey(string $key, ?string $value): void
    {
        if ($value === null) {
            unset($_SERVER[$key], $_ENV[$key]);
            putenv($key);
        } else {
            $_SERVER[$key] = $value;
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }

        \Illuminate\Support\Env::getRepository();
    }

    private function restoreKey(string $key, $serverValue, $envValue): void
    {
        if ($serverValue === null) {
            unset($_SERVER[$key]);
        } else {
            $_SERVER[$key] = $serverValue;
        }

        if ($envValue === null) {
            unset($_ENV[$key]);
        } else {
            $_ENV[$key] = $envValue;
        }

        putenv($key);
    }

    public function test_two_factor_timeout_falls_back_to_two_minutes_when_blank(): void
    {
        $this->setKey('TWO_FACTOR_TIMEOUT', '');

        $services = require base_path('config/services.php');

        $this->assertSame(2, $services['two_factor']['timeout_minutes']);
    }

    public function test_two_factor_timeout_respects_an_explicit_value(): void
    {
        $this->setKey('TWO_FACTOR_TIMEOUT', '5');

        $services = require base_path('config/services.php');

        $this->assertSame(5, $services['two_factor']['timeout_minutes']);
    }

    public function test_two_factor_code_length_falls_back_to_six_when_blank(): void
    {
        $this->setKey('TWO_FACTOR_CODE_LENGTH', '');

        $services = require base_path('config/services.php');

        $this->assertSame(6, $services['two_factor']['code_length']);
    }

    public function test_two_factor_code_length_respects_an_explicit_value(): void
    {
        $this->setKey('TWO_FACTOR_CODE_LENGTH', '4');

        $services = require base_path('config/services.php');

        $this->assertSame(4, $services['two_factor']['code_length']);
    }

    public function test_max_login_attempts_falls_back_to_five_when_blank(): void
    {
        $this->setKey('MAX_LOGIN_ATTEMPTS', '');

        $auth = require base_path('config/auth.php');

        $this->assertSame(5, $auth['max_login_attempts']);
    }

    public function test_max_login_attempts_respects_an_explicit_value(): void
    {
        $this->setKey('MAX_LOGIN_ATTEMPTS', '10');

        $auth = require base_path('config/auth.php');

        $this->assertSame(10, $auth['max_login_attempts']);
    }

    public function test_login_throttle_minutes_falls_back_to_one_when_blank(): void
    {
        $this->setKey('LOGIN_THROTTLE_MINUTES', '');

        $auth = require base_path('config/auth.php');

        $this->assertSame(1, $auth['login_throttle_minutes']);
    }

    public function test_login_throttle_minutes_respects_an_explicit_value(): void
    {
        $this->setKey('LOGIN_THROTTLE_MINUTES', '15');

        $auth = require base_path('config/auth.php');

        $this->assertSame(15, $auth['login_throttle_minutes']);
    }

    public function test_reset_code_expire_minutes_falls_back_to_two_when_blank(): void
    {
        $this->setKey('RESET_CODE_EXPIRE_MINUTES', '');

        $auth = require base_path('config/auth.php');

        $this->assertSame(2, $auth['reset_code_expire_minutes']);
    }

    public function test_reset_code_expire_minutes_respects_an_explicit_value(): void
    {
        $this->setKey('RESET_CODE_EXPIRE_MINUTES', '10');

        $auth = require base_path('config/auth.php');

        $this->assertSame(10, $auth['reset_code_expire_minutes']);
    }

    public function test_payment_expiry_minutes_falls_back_to_fifteen_when_blank(): void
    {
        $this->setKey('PAYMENT_EXPIRY_MINUTES', '');

        $services = require base_path('config/services.php');

        $this->assertSame(15, $services['secure_payment']['expiry_minutes']);
    }

    public function test_payment_expiry_minutes_respects_an_explicit_value(): void
    {
        $this->setKey('PAYMENT_EXPIRY_MINUTES', '30');

        $services = require base_path('config/services.php');

        $this->assertSame(30, $services['secure_payment']['expiry_minutes']);
    }
}
