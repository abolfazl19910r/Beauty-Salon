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
 * مرحله‌ی ۳ (۲۰۲۶-۰۹-۲۵): ستون‌های salons.zarinpal_* حذف شدن و Salon مستقیم از ردیف zarinpal می‌خونه
 * (ویژگی‌های مجازی zarinpal_merchant_id / zarinpal_payout_api_key)، پس هیچ هم‌گام‌سازی‌ای لازم نیست.
 * فیلدهای اختیاری (مثل توکن Payout زرین‌پال) با clear[نام] حذف می‌شن.
 */
class SalonGatewayService
{
    public function create(Salon $salon, array $data): SalonPaymentGateway
    {
        return DB::transaction(function () use ($salon, $data) {
            $gateway = $salon->paymentGateways()->create([
                'driver' => $data['driver'],
                'label' => $data['label'] ?? null,
                'credentials' => $this->credentials($data['driver'], (array) ($data['credentials'] ?? []), [], []),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'priority' => ((int) $salon->paymentGateways()->max('priority')) + 1,
                'fee_percent' => $data['fee_percent'] ?? 0,
                'fee_fixed_toman' => $data['fee_fixed_toman'] ?? 0,
            ]);

            return $gateway;
        });
    }

    public function update(SalonPaymentGateway $gateway, array $data): SalonPaymentGateway
    {
        $gateway->update([
            'label' => $data['label'] ?? null,
            'credentials' => $this->credentials($gateway->driver, (array) ($data['credentials'] ?? []), (array) $gateway->credentials, (array) ($data['clear'] ?? [])),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'fee_percent' => $data['fee_percent'] ?? 0,
            'fee_fixed_toman' => $data['fee_fixed_toman'] ?? 0,
        ]);

        return $gateway;
    }

    public function delete(SalonPaymentGateway $gateway): void
    {
        $salon = $gateway->salon;

        DB::transaction(function () use ($gateway, $salon) {
            $gateway->delete();
            $this->resequence($salon);
        });
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

    /**
     * فیلدهای مخفی (رمز/کلید) خالی = مقدار قبلی بمونه؛ clear[نام] = حذف (فقط فیلدهای اختیاری).
     * فیلد اختیاری خالی که قبلاً مقداری نداشته اصلاً ذخیره نمی‌شه.
     */
    private function credentials(string $driver, array $input, array $current, array $clear): array
    {
        $result = [];

        foreach (GatewayCatalog::fields($driver) as $name => $field) {
            $value = trim((string) ($input[$name] ?? ''));
            $optional = ! empty($field['optional']);

            if ($optional && ! empty($clear[$name])) {
                continue;
            }

            if ($value === '' && $field['secret']) {
                $value = (string) ($current[$name] ?? '');
            }

            if ($driver === 'zarinpal' && $name === 'merchant_id') {
                $value = (string) ZarinpalMerchant::normalize($value);
            }

            if ($optional && $value === '') {
                continue;
            }

            $result[$name] = $value;
        }

        return $result;
    }
}
