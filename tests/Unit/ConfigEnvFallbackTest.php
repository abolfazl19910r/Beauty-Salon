<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Regression guard (test-writing session 10, item 6): .env.example ships CACHE_PREFIX,
 * TELESCOPE_PATH, TELESCOPE_ENABLED and VERIFICATION_CODE_EXPIRE_MINUTES as present-but-empty
 * ("KEY="), the same shape that previously broke SECURITY_LOG_LEVEL/PAYMENTS_LOG_LEVEL (session 4)
 * and the Kavenegar sender/template keys (session 7). env('KEY', 'default') only falls back for a
 * genuinely undefined variable, not an empty string.
 *
 * TELESCOPE_ENABLED is the one case in this batch where a naive `env('KEY') ?: 'default'` fix
 * would itself be wrong: a boolean env value of `false` is also falsy, so `?:` would silently
 * discard an operator's explicit "disable Telescope" instruction and fall back to the
 * environment-based default instead. The fix used here is an explicit null/empty-string check
 * that still lets an explicit true/false win.
 *
 * VERIFICATION_CODE_EXPIRE_MINUTES was a second, independent bug on top of the blank-env shape:
 * config/auth.php never defined this key at all, so PhoneVerificationService's
 * config('auth.verification_code_expire_minutes', 2) always silently fell back to the hardcoded
 * 2, regardless of what was set in .env.
 */
class ConfigEnvFallbackTest extends TestCase
{
    private array $keys = [
        'CACHE_PREFIX',
        'TELESCOPE_PATH',
        'TELESCOPE_ENABLED',
        'VERIFICATION_CODE_EXPIRE_MINUTES',
        // APP_NAME/APP_ENV are also mutated by individual test methods below (to exercise the
        // app-name-slug fallback and the environment-based Telescope default) — they must be
        // tracked and restored here too, or a test that changes APP_ENV to 'local'/'production'
        // would otherwise permanently leak that value into every later test in the same
        // PHPUnit process (since Laravel's testing environment relies on phpunit.xml's
        // APP_ENV=testing being intact for the SQLite in-memory DB config to keep applying).
        'APP_NAME',
        'APP_ENV',
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

    /**
     * .env in this sandbox may already define these keys (blank or not), and Laravel's Env
     * repository caches from $_SERVER/$_ENV rather than getenv()/putenv() once booted. To
     * reliably simulate "key present but blank" regardless of what this environment's real
     * .env contains, we write directly to $_SERVER/$_ENV and force the repository to rebuild.
     */
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

    public function test_cache_prefix_falls_back_to_app_name_slug_when_blank(): void
    {
        $this->setKey('CACHE_PREFIX', '');
        $this->setKey('APP_NAME', 'Rasta');

        $cache = require base_path('config/cache.php');

        $this->assertSame('rasta_cache_', $cache['prefix']);
    }

    public function test_cache_prefix_respects_an_explicit_value(): void
    {
        $this->setKey('CACHE_PREFIX', 'custom_prefix_');

        $cache = require base_path('config/cache.php');

        $this->assertSame('custom_prefix_', $cache['prefix']);
    }

    public function test_telescope_path_falls_back_to_telescope_when_blank(): void
    {
        $this->setKey('TELESCOPE_PATH', '');

        $telescope = require base_path('config/telescope.php');

        $this->assertSame('telescope', $telescope['path']);
    }

    public function test_telescope_path_respects_an_explicit_value(): void
    {
        $this->setKey('TELESCOPE_PATH', 'inspector');

        $telescope = require base_path('config/telescope.php');

        $this->assertSame('inspector', $telescope['path']);
    }

    public function test_telescope_enabled_falls_back_to_environment_default_when_blank(): void
    {
        $this->setKey('TELESCOPE_ENABLED', '');
        $this->setKey('APP_ENV', 'local');

        $telescope = require base_path('config/telescope.php');

        $this->assertTrue($telescope['enabled']);
    }

    public function test_telescope_enabled_respects_an_explicit_false_even_in_local(): void
    {
        $this->setKey('TELESCOPE_ENABLED', 'false');
        $this->setKey('APP_ENV', 'local');

        $telescope = require base_path('config/telescope.php');

        $this->assertFalse($telescope['enabled']);
    }

    public function test_telescope_enabled_respects_an_explicit_true(): void
    {
        $this->setKey('TELESCOPE_ENABLED', 'true');
        $this->setKey('APP_ENV', 'production');

        $telescope = require base_path('config/telescope.php');

        $this->assertTrue($telescope['enabled']);
    }

    public function test_verification_code_expire_minutes_key_exists_and_falls_back_to_two_when_blank(): void
    {
        $this->setKey('VERIFICATION_CODE_EXPIRE_MINUTES', '');

        $auth = require base_path('config/auth.php');

        $this->assertArrayHasKey('verification_code_expire_minutes', $auth);
        $this->assertSame(2, $auth['verification_code_expire_minutes']);
    }

    public function test_verification_code_expire_minutes_respects_an_explicit_value(): void
    {
        $this->setKey('VERIFICATION_CODE_EXPIRE_MINUTES', '5');

        $auth = require base_path('config/auth.php');

        $this->assertSame(5, $auth['verification_code_expire_minutes']);
    }
}
