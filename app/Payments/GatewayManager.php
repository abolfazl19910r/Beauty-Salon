<?php

namespace App\Payments;

use App\Models\Salon;
use App\Models\SalonPaymentGateway;
use App\Payments\Contracts\PaymentGatewayDriver;
use App\Payments\Drivers\ZarinpalDriver;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * ⭐ مدیر درگاه‌های پرداخت (لایه‌ی چند درگاه — مرحله‌ی ۰، ۲۰۲۶-۰۹-۲۵).
 *
 * - gatewaysFor(): درگاه‌های فعال سالن به ترتیب priority.
 * - start(): اگه مشتری درگاهی انتخاب کرده، اول همون؛ بعد بقیه به ترتیب. فقط وقتی یک درگاه
 *   «در دسترس نبود» (retryable) سراغ بعدی می‌ره؛ خطای منطقی (مثلاً مرچنت نامعتبر) رو برمی‌گردونه.
 *   (تصمیم ابوالفضل: انتخاب مشتری + جایگزینی خودکار.)
 * - verify() با همون ردیف درگاهی که پرداخت باهاش شروع شد.
 */
class GatewayManager
{
    /** @return Collection<int, SalonPaymentGateway> */
    public function gatewaysFor(?Salon $salon): Collection
    {
        if (! $salon) {
            return collect();
        }

        return $salon->paymentGateways()->where('is_active', true)->orderBy('priority')->orderBy('id')->get();
    }

    public function driver(SalonPaymentGateway $gateway): PaymentGatewayDriver
    {
        $sandbox = (bool) config('services.zarinpal.sandbox', true);

        return match ($gateway->driver) {
            'zarinpal' => new ZarinpalDriver((array) $gateway->credentials, $sandbox),
            default => throw new InvalidArgumentException("Unknown payment gateway driver [{$gateway->driver}]."),
        };
    }

    /**
     * @return array{0: GatewayStartResult, 1: ?SalonPaymentGateway}
     */
    public function start(?Salon $salon, GatewayStartRequest $request, ?int $preferredGatewayId = null): array
    {
        $gateways = $this->gatewaysFor($salon);

        if ($preferredGatewayId !== null) {
            $gateways = $gateways->sortBy(fn (SalonPaymentGateway $g) => $g->id === $preferredGatewayId ? 0 : 1)->values();
        }

        $last = GatewayStartResult::failed(\App\Support\ZarinpalMerchant::CUSTOMER_MESSAGE);
        $lastGateway = null;

        foreach ($gateways as $gateway) {
            $result = $this->driver($gateway)->start($request);

            if ($result->success || ! $result->retryable) {
                return [$result, $gateway];
            }

            [$last, $lastGateway] = [$result, $gateway];
        }

        return [$last, $lastGateway];
    }

    public function verify(SalonPaymentGateway $gateway, GatewayVerifyRequest $request): GatewayVerifyResult
    {
        return $this->driver($gateway)->verify($request);
    }
}
