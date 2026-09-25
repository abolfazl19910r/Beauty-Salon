<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ⭐ درگاه پرداخت یک سالن (لایه‌ی چند درگاه، ۲۰۲۶-۰۹-۲۵). عمداً BelongsToSalon نداره: همیشه از طریق
 * رابطه‌ی $salon->paymentGateways() خونده می‌شه (از جمله در Jobها و callbackها که CurrentSalon ندارن).
 */
class SalonPaymentGateway extends Model
{
    /** @deprecated از App\Payments\GatewayCatalog استفاده کنید؛ برای سازگاری با کد قبلی نگه داشته شده. */
    public const DRIVER_LABELS = [
        'zarinpal' => 'زرین‌پال',
        'zibal' => 'زیبال',
        'asanpardakht' => 'آسان پرداخت',
        'vandar' => 'وندار',
        'saman' => 'بانک سامان (سپ)',
        'mellat' => 'بانک ملت (به‌پرداخت)',
        'parsian' => 'بانک پارسیان (تاپ)',
    ];

    /** سقف کارمزدی که سالن می‌تونه روی مبلغ مشتری بذاره. */
    public const MAX_FEE_PERCENT = 10;

    public const MAX_FEE_FIXED_TOMAN = 100000;

    protected $fillable = ['salon_id', 'driver', 'label', 'credentials', 'is_active', 'priority', 'fee_percent', 'fee_fixed_toman'];

    protected $hidden = ['credentials'];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'fee_percent' => 'decimal:2',
        'fee_fixed_toman' => 'integer',
    ];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    /**
     * ⭐ کارمزد درگاه که به مبلغ مشتری اضافه می‌شه (تصمیم ابوالفضل، ۲۰۲۶-۰۹-۲۵): درصد از مبلغ پایه +
     * مبلغ ثابت، رو به بالا گرد به تومان کامل (هیچ‌وقت کسر ریال به درگاه نمی‌ره). هر درگاه کارمزد خودش
     * رو داره، پس در جایگزینی خودکار مبلغ نهایی با درگاه جدید دوباره حساب می‌شه.
     */
    public function feeRialFor(int $baseRial): int
    {
        if ($baseRial <= 0) {
            return 0;
        }

        $percentToman = (float) $this->fee_percent * $baseRial / 1000; // (درصد/۱۰۰) × (ریال/۱۰)
        $feeToman = (int) ceil(round($percentToman, 4)) + (int) $this->fee_fixed_toman;

        return max(0, $feeToman) * 10;
    }

    public function feeTomanFor(float|int $baseToman): int
    {
        return intdiv($this->feeRialFor((int) round((float) $baseToman * 10)), 10);
    }

    public function hasFee(): bool
    {
        return (float) $this->fee_percent > 0 || (int) $this->fee_fixed_toman > 0;
    }

    public function displayName(): string
    {
        return $this->label ?: \App\Payments\GatewayCatalog::label($this->driver);
    }
}
