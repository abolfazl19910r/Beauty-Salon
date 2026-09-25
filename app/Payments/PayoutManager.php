<?php

namespace App\Payments;

use App\Models\Salon;
use App\Models\SalonPaymentGateway;
use App\Payments\Contracts\PayoutDriver;
use App\Payments\Drivers\VandarPayoutDriver;
use App\Payments\Drivers\ZarinpalPayoutDriver;
use App\Payments\Drivers\ZibalPayoutDriver;

/**
 * ⭐ مرحله‌ی ۳ چند درگاه (۲۰۲۶-۰۹-۲۵) — تسویه‌ی خودکار متخصص روی لایه‌ی درگاه‌ها.
 *
 * درگاه تسویه‌ی سالن = اولین ردیف (به ترتیب priority) که driverش در GatewayCatalog تسویه پشتیبانی می‌کنه
 * و اطلاعات تسویه‌اش کامله. عمداً مستقل از is_active: مالک ممکنه یک درگاه رو برای پرداخت مشتری خاموش
 * کرده باشه ولی هنوز بخواد تسویه‌ی متخصص‌ها از همون حساب انجام بشه.
 * driver تسویه: زرین‌پال، زیبال و وندار (۲۰۲۶-۰۹-۲۶ — تومان طبق نمونه‌ی رسمی، توکن ۵ روزه با تمدید خودکار).
 */
class PayoutManager
{
    public function gatewayFor(?Salon $salon): ?SalonPaymentGateway
    {
        if (! $salon?->exists) {
            return null;
        }

        return $salon->paymentGateways()->orderBy('priority')->orderBy('id')->get()
            ->first(fn (SalonPaymentGateway $g) => GatewayCatalog::supportsPayout($g->driver) && $this->isConfigured($g));
    }

    public function driverFor(?Salon $salon): ?PayoutDriver
    {
        $gateway = $this->gatewayFor($salon);

        return $gateway ? $this->driver($gateway) : null;
    }

    public function isConfigured(SalonPaymentGateway $gateway): bool
    {
        return match ($gateway->driver) {
            'zarinpal' => ZarinpalPayoutDriver::isConfigured((array) $gateway->credentials),
            'zibal' => ZibalPayoutDriver::isConfigured((array) $gateway->credentials),
            'vandar' => VandarPayoutDriver::isConfigured((array) $gateway->credentials),
            default => false,
        };
    }

    public function driver(SalonPaymentGateway $gateway): PayoutDriver
    {
        return match ($gateway->driver) {
            'zarinpal' => new ZarinpalPayoutDriver((array) $gateway->credentials, (bool) config('services.zarinpal.payout.sandbox', true)),
            'zibal' => new ZibalPayoutDriver((array) $gateway->credentials),
            'vandar' => new VandarPayoutDriver((array) $gateway->credentials, $gateway),
            default => throw new \InvalidArgumentException("Gateway [{$gateway->driver}] has no payout driver."),
        };
    }
}
