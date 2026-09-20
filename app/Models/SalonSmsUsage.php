<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ⭐ فیچر «سقف/قطع پیامک ماهانه» — یک ردیف به‌ازای هر (سالن، ماه تقویمی 'Y-m'). عمداً از
 * BelongsToSalon استفاده نمی‌کنه: این جدول خودش ابزار محاسبه‌ی سقفِ سالن‌هاست، نه یک مدل
 * salon-owned معمولی — و چون SmsQuotaService همیشه salon_id رو صریح پاس می‌ده (نه از
 * CurrentSalon، که در کانتکست queued job اصلاً ست نیست)، نیازی به global scope نداره.
 */
class SalonSmsUsage extends Model
{
    protected $fillable = [
        'salon_id',
        'period',
        'used_count',
        'notified_at',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
    ];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }
}
