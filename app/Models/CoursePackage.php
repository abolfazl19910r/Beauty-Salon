<?php

namespace App\Models;

use App\Traits\BelongsToSalon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * بسته‌ی دوره‌ی درمانی یک سالن (بسته‌ی ب، ۲۰۲۶-۱۰-۱۴): یک خدمت × N جلسه با قیمت کل، فاصله‌ی پیشنهادی و حداقل بین جلسه‌ها،
 * مهلت استفاده و (اختیاری) حداقل درصد پیش‌پرداخت. خرید هر مشتری snapshot همین ردیف را در customer_courses نگه می‌دارد.
 */
class CoursePackage extends Model
{
    use BelongsToSalon, SoftDeletes;

    protected $fillable = [
        'salon_id', 'service_id', 'specialist_id', 'name', 'description', 'sessions_count', 'price', 'min_prepayment_percent',
        'interval_days', 'min_interval_days', 'validity_days', 'is_active', 'is_public', 'sort',
    ];

    protected $casts = [
        'sessions_count' => 'integer',
        'price' => 'float',
        'min_prepayment_percent' => 'integer',
        'interval_days' => 'integer',
        'min_interval_days' => 'integer',
        'validity_days' => 'integer',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'sort' => 'integer',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(BeautyService::class, 'service_id');
    }

    public function specialist(): BelongsTo
    {
        return $this->belongsTo(Specialist::class);
    }

    public function customerCourses(): HasMany
    {
        return $this->hasMany(CustomerCourse::class);
    }

    /** بسته‌هایی که مشتری خودش می‌خرد (وب و اپ) */
    public function scopeOnSale(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_public', true)->orderBy('sort')->orderBy('id');
    }

    /** صرفه‌جویی نسبت به جلسه‌های تکی (۰ اگر نیست) */
    public function savingAmount(): float
    {
        return max(0.0, (float) ($this->service?->price ?? 0) * $this->sessions_count - (float) $this->price);
    }

    /** حداقل مبلغ پرداخت اول برای این قیمت (تومان، رو به بالا به هزار)؛ برابر قیمت وقتی پیش‌پرداخت ندارد */
    public function minPrepaymentFor(float $price): float
    {
        if (! $this->min_prepayment_percent || $this->min_prepayment_percent >= 100) {
            return $price;
        }

        return min($price, ceil($price * $this->min_prepayment_percent / 100 / 1000) * 1000);
    }
}
