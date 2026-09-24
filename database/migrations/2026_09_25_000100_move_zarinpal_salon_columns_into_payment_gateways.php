<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ مرحله‌ی ۳ چند درگاه (۲۰۲۶-۰۹-۲۵): کد پذیرنده و توکن Payout زرین‌پال از جدول salons به ردیف zarinpal
 * جدول salon_payment_gateways (credentials رمزشده) منتقل می‌شن و دو ستون salons حذف می‌شن.
 * - ردیف zarinpal موجود منبع حقیقته: merchant_id اون عوض نمی‌شه؛ فقط payout_api_key اگه نداره اضافه می‌شه.
 * - سالنی که کد پذیرنده داره ولی ردیف نه → ردیف فعال ساخته می‌شه (همون کاری که Salon::booted می‌کرد).
 * - توکن Payout بدون کد پذیرنده بی‌استفاده بوده و منتقل نمی‌شه.
 * با DB و Crypt (نه مدل‌ها) تا migration به تغییرات بعدی مدل‌ها وابسته نباشه. credentials با همون
 * قالب cast 'encrypted:array' نوشته می‌شه: encryptString(json_encode(...)).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('salons', 'zarinpal_merchant_id')) {
            return;
        }

        $salons = DB::table('salons')->select('id', 'zarinpal_merchant_id', 'zarinpal_payout_api_key')->get();

        foreach ($salons as $salon) {
            $merchant = trim((string) $salon->zarinpal_merchant_id);
            $payoutKey = $this->decrypt($salon->zarinpal_payout_api_key);
            $row = DB::table('salon_payment_gateways')->where('salon_id', $salon->id)->where('driver', 'zarinpal')->first();

            if ($row) {
                $credentials = json_decode(Crypt::decryptString($row->credentials), true) ?: [];
                if (filled($payoutKey) && ! filled($credentials['payout_api_key'] ?? null)) {
                    $credentials['payout_api_key'] = $payoutKey;
                    DB::table('salon_payment_gateways')->where('id', $row->id)->update([
                        'credentials' => Crypt::encryptString(json_encode($credentials)),
                        'updated_at' => now(),
                    ]);
                }

                continue;
            }

            if ($merchant === '') {
                continue;
            }

            $credentials = ['merchant_id' => $merchant] + (filled($payoutKey) ? ['payout_api_key' => $payoutKey] : []);
            DB::table('salon_payment_gateways')->insert([
                'salon_id' => $salon->id,
                'driver' => 'zarinpal',
                'credentials' => Crypt::encryptString(json_encode($credentials)),
                'is_active' => true,
                'priority' => ((int) DB::table('salon_payment_gateways')->where('salon_id', $salon->id)->max('priority')) + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('salons', function (Blueprint $table) {
            $table->dropColumn(['zarinpal_merchant_id', 'zarinpal_payout_api_key']);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('salons', 'zarinpal_merchant_id')) {
            return;
        }

        Schema::table('salons', function (Blueprint $table) {
            $table->string('zarinpal_merchant_id')->nullable();
            $table->text('zarinpal_payout_api_key')->nullable();
        });

        foreach (DB::table('salon_payment_gateways')->where('driver', 'zarinpal')->get() as $row) {
            $credentials = json_decode(Crypt::decryptString($row->credentials), true) ?: [];
            DB::table('salons')->where('id', $row->salon_id)->update([
                'zarinpal_merchant_id' => $credentials['merchant_id'] ?? null,
                'zarinpal_payout_api_key' => filled($credentials['payout_api_key'] ?? null) ? Crypt::encryptString($credentials['payout_api_key']) : null,
            ]);
        }
    }

    private function decrypt(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return null; // مقدار غیرقابل رمزگشایی (کلید عوض شده) — بهتره منتقل نشه تا این‌که تسویه با توکن خراب انجام بشه
        }
    }
};
