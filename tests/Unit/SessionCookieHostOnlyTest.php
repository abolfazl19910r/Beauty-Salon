<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * بررسی امنیتی ۲۰۲۶-۱۱-۰۵، M5: کوکی session فقط مال همان میزبان (هیچ‌وقت «.arisheh.ir» مشترک) و روی HTTPS با پیشوند __Host-
 * — زیردامنه‌ی یک سالن نمی‌تواند session دیگری برای دامنه‌ی اصلی یا سالن دیگر جا بیندازد.
 */
class SessionCookieHostOnlyTest extends TestCase
{
    /** @param  array<string, string|null>  $env */
    private function sessionConfigWith(array $env): array
    {
        $backup = [];
        foreach ($env as $key => $value) {
            $backup[$key] = [getenv($key), $_ENV[$key] ?? null, $_SERVER[$key] ?? null];
            if ($value === null) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                putenv("$key=$value");
                $_ENV[$key] = $_SERVER[$key] = $value;
            }
        }
        try {
            return require config_path('session.php');
        } finally {
            foreach ($backup as $key => [$g, $e, $s]) {
                $g === false ? putenv($key) : putenv("$key=$g");
                if ($e === null) {
                    unset($_ENV[$key]);
                } else {
                    $_ENV[$key] = $e;
                }
                if ($s === null) {
                    unset($_SERVER[$key]);
                } else {
                    $_SERVER[$key] = $s;
                }
            }
        }
    }

    public function test_shared_session_domain_from_env_is_ignored(): void
    {
        $config = $this->sessionConfigWith(['SESSION_DOMAIN' => '.arisheh.ir', 'SESSION_SECURE_COOKIE' => 'true', 'SESSION_COOKIE' => null]);

        $this->assertNull($config['domain']);
    }

    public function test_https_cookie_gets_the_host_prefix(): void
    {
        $config = $this->sessionConfigWith(['SESSION_SECURE_COOKIE' => 'true', 'SESSION_COOKIE' => 'mahru_session', 'SESSION_PATH' => null]);

        $this->assertSame('__Host-mahru_session', $config['cookie']);
        $this->assertSame('/', $config['path']);
        $this->assertTrue($config['secure']);
    }

    public function test_plain_http_keeps_the_old_name(): void
    {
        $config = $this->sessionConfigWith(['SESSION_SECURE_COOKIE' => 'false', 'SESSION_COOKIE' => 'mahru_session']);

        $this->assertSame('mahru_session', $config['cookie']);
    }

    public function test_an_already_prefixed_name_is_not_doubled(): void
    {
        $config = $this->sessionConfigWith(['SESSION_SECURE_COOKIE' => 'true', 'SESSION_COOKIE' => '__Host-x']);

        $this->assertSame('__Host-x', $config['cookie']);
    }
}
