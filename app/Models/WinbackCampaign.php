<?php

namespace App\Models;

use App\Traits\BelongsToSalon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * کمپین دستی «ارسال به مشتری‌های قدیمی» (بسته‌ی ج، ۲۰۲۶-۱۰-۱۴). فیلدهای تخفیف همان نام‌های WinbackSetting را دارند تا ساخت کد
 * تخفیف شخصی مشترک باشد (WinbackService::createCode).
 */
class WinbackCampaign extends Model
{
    use BelongsToSalon;

    public const STATUSES = [
        'scheduled' => 'زمان‌بندی‌شده',
        'sending' => 'در حال ارسال',
        'paused' => 'متوقف',
        'done' => 'تمام‌شده',
        'cancelled' => 'لغوشده',
    ];

    /** وضعیت‌هایی که «کمپین فعال» حساب می‌شوند (فقط یکی در هر سالن) */
    public const ACTIVE = ['scheduled', 'sending', 'paused'];

    public const PAUSE_REASONS = [
        'manual' => 'توقف دستی',
        'quota' => 'سهمیه‌ی پیامک به ۲۰٪ رسید؛ بسته‌ی پیامک بخرید یا ماه بعد ادامه دهید',
        'send_failed' => 'ارسال پیامک ناموفق بود',
        'feature' => 'امکان «ارسال دستی» در پلن سالن نیست',
        'subscription' => 'اشتراک سالن فعال نیست',
    ];

    protected $fillable = [
        'salon_id', 'created_by', 'name', 'last_visit_from_days', 'last_visit_to_days', 'service_ids', 'min_visits',
        'discount_type', 'discount_amount', 'discount_max_amount', 'discount_valid_days', 'message', 'status', 'pause_reason',
        'scheduled_at', 'max_recipients', 'targeted', 'sent', 'skipped', 'failed', 'parts_used', 'started_at', 'finished_at', 'cancelled_at',
    ];

    protected $casts = [
        'service_ids' => 'array',
        'last_visit_from_days' => 'integer',
        'last_visit_to_days' => 'integer',
        'min_visits' => 'integer',
        'discount_amount' => 'decimal:2',
        'discount_max_amount' => 'decimal:2',
        'discount_valid_days' => 'integer',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'max_recipients' => 'integer',
        'targeted' => 'integer',
        'sent' => 'integer',
        'skipped' => 'integer',
        'failed' => 'integer',
        'parts_used' => 'integer',
    ];

    protected $attributes = ['discount_valid_days' => 14, 'status' => 'scheduled'];

    public function recipients(): HasMany
    {
        return $this->hasMany(WinbackCampaignRecipient::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE, true);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function pauseReasonLabel(): ?string
    {
        return $this->pause_reason ? (self::PAUSE_REASONS[$this->pause_reason] ?? $this->pause_reason) : null;
    }

    public function hasDiscount(): bool
    {
        return in_array($this->discount_type, ['fixed', 'percentage'], true) && (float) $this->discount_amount > 0;
    }

    public function discountLabel(): string
    {
        return $this->discount_type === 'percentage'
            ? rtrim(rtrim(number_format((float) $this->discount_amount, 2, '.', ''), '0'), '.').'٪'
            : number_format((float) $this->discount_amount).' تومان';
    }
}
