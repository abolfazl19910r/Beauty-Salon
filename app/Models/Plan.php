<?php

namespace App\Models;

use App\Support\Billing\SubscriptionPricing;
use App\Support\Features\Feature;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * پلن اشتراک سالن (بسته‌ی الف، ۲۰۲۶-۱۰-۱۴): قیمت هر بازه، متخصص شامل، سهمیه‌ی پیامک و امکانات. سطح پلتفرم.
 * is_active=false: به سالن تازه فروخته نمی‌شود، ولی سالنی که رویش است همان را تمدید می‌کند و سوپرادمین هنوز می‌تواند تخصیصش دهد.
 * is_public: در صفحه‌ی فروش و صفحه‌ی صورتحساب سالن‌ها دیده می‌شود.
 */
class Plan extends Model
{
    public const TRIAL = 'trial';

    public const FULL = 'full';

    protected $fillable = [
        'key', 'name', 'description', 'is_trial', 'is_public', 'is_active', 'sort',
        'price_1m', 'price_3m', 'price_6m', 'price_12m',
        'included_specialists', 'extra_specialist_price_per_month', 'sms_quota_per_month', 'trial_days',
        'theme_site_count', 'theme_site_count_6m', 'theme_site_count_12m', 'theme_admin_count',
    ];

    protected $casts = [
        'is_trial' => 'boolean',
        'theme_site_count' => 'integer',
        'theme_site_count_6m' => 'integer',
        'theme_site_count_12m' => 'integer',
        'theme_admin_count' => 'integer',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'sort' => 'integer',
        'price_1m' => 'integer',
        'price_3m' => 'integer',
        'price_6m' => 'integer',
        'price_12m' => 'integer',
        'included_specialists' => 'integer',
        'extra_specialist_price_per_month' => 'integer',
        'sms_quota_per_month' => 'integer',
        'trial_days' => 'integer',
    ];

    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    public function salons(): HasMany
    {
        return $this->hasMany(Salon::class);
    }

    public static function trial(): ?self
    {
        return static::query()->where('is_trial', true)->orderBy('id')->first();
    }

    public static function byKey(string $key): ?self
    {
        return static::query()->where('key', $key)->first();
    }

    /** پلن‌های فعال و عمومی (لندینگ، صورتحساب) به ترتیب sort */
    public function scopeOffered(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_public', true)->where('is_trial', false)->orderBy('sort')->orderBy('id');
    }

    /** قیمت پایه‌ی این بازه (تومان، بدون متخصص اضافه)؛ null = فروخته نمی‌شود */
    public function priceFor(string $period): ?int
    {
        if (! isset(SubscriptionPricing::PLAN_MONTHS[$period])) {
            return null;
        }

        $price = $this->getAttribute('price_'.$period);

        return $price === null ? null : (int) $price;
    }

    /** @return list<string> بازه‌هایی که فروخته می‌شوند */
    public function periods(): array
    {
        return array_values(array_filter(array_keys(SubscriptionPricing::PLAN_MONTHS), fn (string $p) => $this->priceFor($p) !== null));
    }

    public function isSellable(): bool
    {
        return ! $this->is_trial && $this->periods() !== [];
    }

    /** @return list<string> */
    public function featureKeys(): array
    {
        $keys = $this->relationLoaded('features') ? $this->features->pluck('feature') : $this->features()->pluck('feature');

        return array_values(array_intersect(Feature::keys(), $keys->all()));
    }

    public function hasFeature(Feature $feature): bool
    {
        return in_array($feature->value, $this->featureKeys(), true);
    }

    /**
     * امکانات پلن را دقیقاً برابر این فهرست می‌کند (کلید ناشناخته نادیده گرفته می‌شود).
     *
     * @param  iterable<string|Feature>  $features
     */
    public function syncFeatures(iterable $features): void
    {
        $wanted = collect($features)->map(fn ($f) => $f instanceof Feature ? $f->value : (string) $f)
            ->intersect(Feature::keys())->unique()->values();

        $this->features()->whereNotIn('feature', $wanted->all())->delete();
        $existing = $this->features()->pluck('feature');
        foreach ($wanted->diff($existing) as $key) {
            $this->features()->create(['feature' => $key]);
        }
        $this->unsetRelation('features');
        app(\App\Services\Plans\FeatureResolver::class)->forgetPlan((int) $this->id);
    }

    /** ثبت در فاکتور تا تغییرات بعدی پلن فاکتورهای قدیمی را عوض نکند */
    public function snapshot(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'prices' => collect(SubscriptionPricing::PLAN_MONTHS)->keys()->mapWithKeys(fn ($p) => [$p => $this->priceFor($p)])->all(),
            'included_specialists' => (int) $this->included_specialists,
            'extra_specialist_price_per_month' => (int) $this->extra_specialist_price_per_month,
            'sms_quota_per_month' => (int) $this->sms_quota_per_month,
            'features' => $this->featureKeys(),
        ];
    }

    /**
     * چند قالب سایت/پنل مشتری/اپ (علاوه بر «طلایی ماهرو») برای این پلن در این بازه‌ی اشتراک باز است (۲۰۲۶-۱۰-۱۵).
     * ۶ و ۱۲ ماهه هرگز کمتر از ۱ و ۳ ماهه نیستند؛ سقف config('themes.site_total').
     */
    public function siteThemeAllowance(?string $period): int
    {
        $base = (int) $this->theme_site_count;
        $count = match ($period) {
            '12m' => max($base, (int) $this->theme_site_count_12m),
            '6m' => max($base, (int) $this->theme_site_count_6m),
            default => $base,
        };

        return min($count, (int) config('themes.site_total', 12));
    }

    /** چند قالب پنل مدیر (علاوه بر منوی کناری پیش‌فرض) — به بازه بستگی ندارد */
    public function adminThemeAllowance(): int
    {
        return min((int) $this->theme_admin_count, (int) config('themes.admin_total', 3));
    }
}
