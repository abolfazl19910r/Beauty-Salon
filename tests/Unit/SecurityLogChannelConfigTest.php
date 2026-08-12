<?php

namespace Tests\Unit;

use Tests\TestCase;

class SecurityLogChannelConfigTest extends TestCase
{
    /**
     * Regression guard: .env ships SECURITY_LOG_LEVEL= and PAYMENTS_LOG_LEVEL= as present-but-empty
     * values. env('X', 'default') only falls back for a genuinely unset variable, not an empty
     * string, so config/logging.php previously resolved these channels' 'level' key to '' — which
     * is not a valid PSR-3 level. Laravel's LogManager::get() catches that failure internally and
     * silently falls back to an "emergency" logger (storage/logs/laravel.log at 'debug' level)
     * rather than crashing the request, which is why this doesn't surface as a 500 in practice —
     * but it does mean every write to these channels silently lands in the wrong file, at the
     * wrong level, defeating the dedicated security/payments audit trail entirely. This asserts
     * the config actually resolves to a valid level with the project's real (empty) .env values.
     */
    public function test_security_channel_level_falls_back_to_warning_not_an_empty_string(): void
    {
        $level = config('logging.channels.security.level');

        $this->assertNotSame('', $level);
        $this->assertContains($level, ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency']);
    }

    public function test_payments_channel_level_falls_back_to_info_not_an_empty_string(): void
    {
        $level = config('logging.channels.payments.level');

        $this->assertNotSame('', $level);
        $this->assertContains($level, ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency']);
    }
}
