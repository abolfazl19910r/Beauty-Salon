<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Handler\FormattableHandlerInterface;

/**
 * tap کانال‌های عمومی (config/logging.php: single، daily، stderr، syslog، errorlog). کانال‌های security و payments خام می‌مانند —
 * مدرک نگهداری ماده‌ی ۶۶۸ و تطبیق پرداخت‌اند.
 */
final class MaskPersonalData
{
    public function __invoke(Logger $logger): void
    {
        if (! config('logging.mask_personal_data', true)) {
            return;
        }

        foreach ($logger->getLogger()->getHandlers() as $handler) {
            if ($handler instanceof FormattableHandlerInterface && ! $handler->getFormatter() instanceof MaskingFormatter) {
                $handler->setFormatter(new MaskingFormatter($handler->getFormatter()));
            }
        }
    }
}
