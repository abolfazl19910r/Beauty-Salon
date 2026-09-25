<?php

namespace App\Payments;

use App\Models\Salon;
use App\Models\SalonPaymentGateway;
use App\Payments\Contracts\PaymentGatewayDriver;
use App\Payments\Drivers\AsanPardakhtDriver;
use App\Payments\Drivers\MellatDriver;
use App\Payments\Drivers\ParsianDriver;
use App\Payments\Drivers\SamanDriver;
use App\Payments\Drivers\VandarDriver;
use App\Payments\Drivers\ZarinpalDriver;
use App\Payments\Drivers\ZibalDriver;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * ⭐ مدیر درگاه‌های پرداخت (لایه‌ی چند درگاه — مرحله‌ی ۰ و ۱، ۲۰۲۶-۰۹-۲۵).
 *
 * - gatewaysFor(): درگاه‌های فعال سالن به ترتیب priority.
 * - start(): اگه مشتری درگاهی انتخاب کرده، اول همون؛ بعد بقیه به ترتیب. فقط وقتی یک درگاه
 *   «در دسترس نبود» (retryable) سراغ بعدی می‌ره؛ خطای منطقی (مثلاً مرچنت نامعتبر) رو برمی‌گردونه.
 *   کارمزد هر درگاه جدا به مبلغ اضافه می‌شه (مرحله‌ی ۱) و همراه نتیجه برمی‌گرده.
 * - verify() با همون ردیف درگاهی که پرداخت باهاش شروع شد.
 * - test(): «تست اتصال» صفحه‌ی مدیریت درگاه‌ها — یک درخواست پرداخت واقعی بدون انتقال مشتری (پولی جابه‌جا
 *   نمی‌شه و جلسه‌ی پرداخت خودش منقضی می‌شه) تا اعتبار مرچنت/کلید و دسترسی سرور به درگاه معلوم بشه.
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
        $credentials = (array) $gateway->credentials;

        return match ($gateway->driver) {
            'zarinpal' => new ZarinpalDriver($credentials, (bool) config('services.zarinpal.sandbox', true)),
            'zibal' => new ZibalDriver($credentials),
            'vandar' => new VandarDriver($credentials),
            'asanpardakht' => new AsanPardakhtDriver($credentials),
            'saman' => new SamanDriver($credentials),
            'mellat' => new MellatDriver($credentials),
            'parsian' => new ParsianDriver($credentials),
            default => throw new InvalidArgumentException("Unknown payment gateway driver [{$gateway->driver}]."),
        };
    }

    /**
     * @return array{0: GatewayStartResult, 1: ?SalonPaymentGateway, 2: int} نتیجه، درگاه، کارمزد (ریال)
     */
    public function start(?Salon $salon, GatewayStartRequest $request, ?int $preferredGatewayId = null): array
    {
        $gateways = $this->gatewaysFor($salon);

        if ($preferredGatewayId !== null) {
            $gateways = $gateways->sortBy(fn (SalonPaymentGateway $g) => $g->id === $preferredGatewayId ? 0 : 1)->values();
        }

        $last = GatewayStartResult::failed(\App\Support\ZarinpalMerchant::CUSTOMER_MESSAGE);
        $lastGateway = null;
        $lastFee = 0;

        foreach ($gateways as $gateway) {
            $fee = $gateway->feeRialFor($request->amountRial);
            $result = $this->driver($gateway)->start($request->with(amountRial: $request->amountRial + $fee));

            if ($result->success || ! $result->retryable) {
                return [$result, $gateway, $fee];
            }

            [$last, $lastGateway, $lastFee] = [$result, $gateway, $fee];
        }

        return [$last, $lastGateway, $lastFee];
    }

    public function verify(SalonPaymentGateway $gateway, GatewayVerifyRequest $request): GatewayVerifyResult
    {
        return $this->driver($gateway)->verify($request);
    }

    public function test(SalonPaymentGateway $gateway, string $callbackUrl): GatewayStartResult
    {
        try {
            return $this->driver($gateway)->start(new GatewayStartRequest(
                10000,
                $callbackUrl,
                'تست اتصال درگاه — بدون پرداخت',
                transactionId: (int) (microtime(true) * 1000),
            ));
        } catch (\Throwable $e) {
            return GatewayStartResult::failed('خطا: '.$e->getMessage());
        }
    }
}
