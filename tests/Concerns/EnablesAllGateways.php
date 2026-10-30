<?php

namespace Tests\Concerns;

use App\Payments\GatewayCatalog;

/**
 * بسته‌ی 5ز (۲۰۲۶-۱۰-۳۰): در پلتفرم فقط درگاه‌های `payments.enabled_drivers` (پیش‌فرض فقط زرین‌پال) دیده و استفاده می‌شوند.
 * کد و تست زیبال، وندار، آسان‌پرداخت و درگاه‌های مستقیم بانکی می‌ماند؛ تست‌هایشان با این trait همه‌ی درگاه‌ها را روشن می‌کنند.
 */
trait EnablesAllGateways
{
    protected function setUpEnablesAllGateways(): void
    {
        config(['payments.enabled_drivers' => GatewayCatalog::keys()]);
    }
}
