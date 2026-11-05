<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Handler\FilterHandler;
use Monolog\Level;

/**
 * tap کانال‌های نگهداری‌شده (security ‏۱۹۰ روز، payments ‏۳۶۵ روز): هر خط از سطح داده‌شده به بالا (پیش‌فرض warning) در کانال
 * پیش‌فرض (laravel.log، `docker logs`) هم نوشته می‌شود — مشکل پرداخت یا امنیت از جایی که مدیر سرور نگاه می‌کند پنهان نمی‌ماند،
 * و نسخه‌ی کامل و خام همچنان در فایل نگهداری‌شده‌ی خودش است. نسخه‌ی کانال پیش‌فرض از همان ماسک داده‌ی شخصی رد می‌شود.
 */
final class MirrorToDefaultChannel
{
    public function __invoke(Logger $logger, string $level = 'warning'): void
    {
        $default = config('logging.default');
        if (! is_string($default) || $default === '' || in_array($default, ['security', 'payments', 'csp', 'null'], true)) {
            return;
        }

        $min = Level::fromName($level);
        foreach (app('log')->channel($default)->getLogger()->getHandlers() as $handler) {
            $logger->getLogger()->pushHandler(new FilterHandler($handler, $min));
        }
    }
}
