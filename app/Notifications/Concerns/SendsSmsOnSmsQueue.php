<?php

namespace App\Notifications\Concerns;

use App\Support\Queues;

/**
 * کانال پیامک اعلان صف‌دار روی صف sms (تصمیم ۲۰۲۶-۰۹-۳۰)؛ بقیه‌ی کانال‌ها روی صف پیش‌فرض.
 */
trait SendsSmsOnSmsQueue
{
    public function viaQueues(): array
    {
        return ['sms' => Queues::SMS];
    }
}
