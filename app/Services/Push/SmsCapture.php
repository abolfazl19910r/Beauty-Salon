<?php

namespace App\Services\Push;

use App\Services\SMSService;
use App\Support\Features\Feature;

/**
 * متن پیامکی را که toSms() یک اعلان می‌فرستاد، بدون فرستادن می‌گیرد (بسته‌ی ۴) — تا متن‌ها و قواعد هر اعلان (SmsText، متن
 * اختصاصی سالن، دسته‌ی امکان) یک‌جا بمانند و فقط تصمیم «پوش یا پیامک» در AppMessenger گرفته شود.
 */
final class SmsCapture extends SMSService
{
    /** @var list<array{mobile: string, message: string, salon_id: ?int, feature: ?Feature}> */
    public array $captured = [];

    public function __construct() {}

    public function send(string $mobile, string $message, ?int $salonId = null, ?Feature $feature = null): bool
    {
        $this->captured[] = ['mobile' => $mobile, 'message' => $message, 'salon_id' => $salonId, 'feature' => $feature];

        return true;
    }

    /**
     * @param  callable(): mixed  $callback
     * @return list<array{mobile: string, message: string, salon_id: ?int, feature: ?Feature}>
     */
    public static function run(callable $callback): array
    {
        $app = app();
        $previous = $app->bound(SMSService::class) && $app->resolved(SMSService::class) ? $app->make(SMSService::class) : null;
        $capture = new self;
        $app->instance(SMSService::class, $capture);

        try {
            $callback();
        } finally {
            if ($previous !== null) {
                $app->instance(SMSService::class, $previous);
            } else {
                $app->forgetInstance(SMSService::class);
                $app->offsetUnset(SMSService::class);
            }
        }

        return $capture->captured;
    }
}
