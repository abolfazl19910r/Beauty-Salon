<?php

namespace App\Services\Payment;

use App\Models\Salon;
use App\Models\SalonPaymentGateway;
use App\Payments\GatewayCatalog;
use App\Support\ZarinpalMerchant;
use Illuminate\Support\Facades\DB;

/**
 * ⭐ مدیریت درگاه‌های پرداخت یک سالن (مرحله‌ی ۱ چند درگاه، ۲۰۲۶-۰۹-۲۵) — جایگزین فیلد تکی «کد پذیرنده‌ی
 * زرین‌پال» در صفحه‌ی «اطلاعات سالن». هر سالن از هر نوع درگاه حداکثر یکی داره؛ ترتیب (priority) همون
 * ترتیب جایگزینی خودکاره و درگاه اول پیش‌فرض صفحه‌ی پرداخت مشتری.
 *
 * هم‌گام‌سازی با salons.zarinpal_merchant_id: فرم ثبت‌نام/سوپرادمین هنوز این ستون رو می‌نویسن (و
 * Salon::booted ردیف zarinpal رو می‌سازه) و تسویه‌ی خودکار متخصص‌ها (ZarinpalPayoutService) هنوز از
 * همین ستون می‌خونه. پس هر تغییر ردیف zarinpal از این صفحه با updateQuietly به ستون برمی‌گرده — بدون
 * رویداد، تا Salon::booted دوباره ردیف رو بازنویسی نکنه.
 */
class SalonGatewayService
{
    public function create(Salon $salon, array $data): SalonPaymentGateway
    {
        return DB::transaction(function () use ($salon, $data) {
            $gateway = $salon->paymentGateways()->create([
                'driver' => $data['driver'],
                'label' => $data['label'] ?? null,
                'credentials' => $this->credentials($data['driver'], (array) ($data['credentials'] ?? []), []),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'priority' => ((int) $salon->paymentGateways()->max('priority')) + 1,
                'fee_percent' => $data['fee_percent'] ?? 0,
                'fee_fixed_toman' => $data['fee_fixed_toman'] ?? 0,
            ]);

            $this->syncZarinpalColumn($salon);

            return $gateway;
        });
    }

    public function update(SalonPaymentGateway $gateway, array $data): SalonPaymentGateway
    {
        $gateway->update([
            'label' => $data['label'] ?? null,
            'credentials' => $this->credentials($gateway->driver, (array) ($data['credentials'] ?? []), (array) $gateway->credentials),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'fee_percent' => $data['fee_percent'] ?? 0,
            'fee_fixed_toman' => $data['fee_fixed_toman'] ?? 0,
        ]);

        $this->syncZarinpalColumn($gateway->salon);

        return $gateway;
    }

    public function delete(SalonPaymentGateway $gateway): void
    {
        $salon = $gateway->salon;

        DB::transaction(function () use ($gateway, $salon) {
            $gateway->delete();
            $this->resequence($salon);
        });

        $this->syncZarinpalColumn($salon);
    }

    /** یک پله بالا/پایین بردن درگاه در ترتیب. */
    public function move(SalonPaymentGateway $gateway, string $direction): void
    {
        DB::transaction(function () use ($gateway, $direction) {
            $ordered = $this->resequence($gateway->salon);
            $index = $ordered->search(fn (SalonPaymentGateway $g) => $g->id === $gateway->id);
            $swapWith = $direction === 'up' ? $index - 1 : $index + 1;

            if ($index === false || ! isset($ordered[$swapWith])) {
                return;
            }

            $other = $ordered[$swapWith];
            [$a, $b] = [$ordered[$index]->priority, $other->priority];
            $ordered[$index]->update(['priority' => $b]);
            $other->update(['priority' => $a]);
        });
    }

    /** priorityها رو ۱..n پشت‌سرهم می‌کنه و درگاه‌ها رو به همون ترتیب برمی‌گردونه. */
    private function resequence(Salon $salon)
    {
        $ordered = $salon->paymentGateways()->orderBy('priority')->orderBy('id')->get()->values();

        foreach ($ordered as $i => $gateway) {
            if ($gateway->priority !== $i + 1) {
                $gateway->update(['priority' => $i + 1]);
            }
        }

        return $ordered;
    }

    /** فیلدهای مخفی (رمز/کلید) خالی = مقدار قبلی بمونه. */
    private function credentials(string $driver, array $input, array $current): array
    {
        $result = [];

        foreach (GatewayCatalog::fields($driver) as $name => $field) {
            $value = trim((string) ($input[$name] ?? ''));

            if ($value === '' && $field['secret']) {
                $value = (string) ($current[$name] ?? '');
            }

            if ($driver === 'zarinpal' && $name === 'merchant_id') {
                $value = (string) ZarinpalMerchant::normalize($value);
            }

            $result[$name] = $value;
        }

        return $result;
    }

    private function syncZarinpalColumn(Salon $salon): void
    {
        $merchant = $salon->paymentGateways()->where('driver', 'zarinpal')->first()?->credentials['merchant_id'] ?? null;

        if ($salon->zarinpal_merchant_id !== ($merchant ?: null)) {
            $salon->forceFill(['zarinpal_merchant_id' => $merchant ?: null])->saveQuietly();
        }
    }
}
