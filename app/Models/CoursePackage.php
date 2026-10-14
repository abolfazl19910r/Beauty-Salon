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
        'salon_id', 'service_id', 'extra_service_ids', 'specialist_id', 'name', 'description', 'sessions_count', 'price', 'min_prepayment_percent',
        'interval_days', 'min_interval_days', 'validity_days', 'is_active', 'is_public', 'sort',
    ];

    protected $casts = [
        'sessions_count' => 'integer',
        'extra_service_ids' => 'array',
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

    /**
     * خدمت‌های هر جلسه: خدمت اصلی + خدمت‌های همراه (بسته‌ی چندخدمتی، ادامه ۷ — هر جلسه همه با هم در یک نوبت).
     *
     * @return \Illuminate\Support\Collection<int, BeautyService>
     */
    public function sessionServices(): \Illuminate\Support\Collection
    {
        return self::servicesFor((int) $this->service_id, (array) ($this->extra_service_ids ?? []), (int) $this->salon_id);
    }

    /** @return \Illuminate\Support\Collection<int, BeautyService> به ترتیب: اصلی، بعد همراه‌ها */
    public static function servicesFor(int $serviceId, array $extraIds, int $salonId): \Illuminate\Support\Collection
    {
        $ids = array_values(array_unique(array_merge([$serviceId], array_map('intval', $extraIds))));
        $services = BeautyService::withoutGlobalScopes()->where('salon_id', $salonId)->whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)->map(fn ($id) => $services->get($id))->filter()->values();
    }

    public function isMultiService(): bool
    {
        return ! empty($this->extra_service_ids);
    }

    /** قیمت همین جلسه‌ها به‌صورت تکی */
    public function listPrice(): float
    {
        return (float) $this->sessionServices()->sum(fn ($s) => (float) $s->price) * $this->sessions_count;
    }

    /** مدت هر جلسه: جمع مدت خدمت‌ها */
    public function sessionDuration(): int
    {
        return (int) $this->sessionServices()->sum(fn ($s) => (int) ($s->duration ?: 30));
    }

    /** صرفه‌جویی نسبت به جلسه‌های تکی (۰ اگر نیست) */
    public function savingAmount(): float
    {
        return max(0.0, $this->listPrice() - (float) $this->price);
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
