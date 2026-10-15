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
        'theme_layouts', 'theme_palettes', 'theme_layouts_6m', 'theme_palettes_6m', 'theme_layouts_12m', 'theme_palettes_12m',
        'theme_admin_count',
    ];

    /** ستون‌های دسترسی قالب‌ها (بسته‌ی «و»): چند چیدمان × چند پالت در هر بازه + قالب پنل مدیر */
    public const THEME_FIELDS = [
        'theme_layouts', 'theme_palettes', 'theme_layouts_6m', 'theme_palettes_6m', 'theme_layouts_12m', 'theme_palettes_12m',
        'theme_admin_count',
    ];

    protected $casts = [
        'is_trial' => 'boolean',
        'theme_layouts' => 'integer',
        'theme_palettes' => 'integer',
        'theme_layouts_6m' => 'integer',
        'theme_palettes_6m' => 'integer',
        'theme_layouts_12m' => 'integer',
        'theme_palettes_12m' => 'integer',
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
     * چند چیدمان و چند پالت (بسته‌ی «و»، ۲۰۲۶-۱۰-۱۵) برای این پلن در این بازه‌ی اشتراک باز است — قالب‌های باز = حاصل‌ضرب، به ترتیبی
     * که ابرمدیر چیده (ThemeCatalog). ۶ و ۱۲ ماهه هرگز کمتر از بازه‌ی کوتاه‌تر نیستند؛ سقف تعداد چیدمان‌ها و پالت‌های config.
     *
     * @return array{0: int, 1: int}
     */
    public function siteThemeGrid(?string $period): array
    {
        $layouts = (int) $this->theme_layouts;
        $palettes = (int) $this->theme_palettes;
        if ($period === '6m' || $period === '12m') {
            $layouts = max($layouts, (int) $this->theme_layouts_6m);
            $palettes = max($palettes, (int) $this->theme_palettes_6m);
        }
        if ($period === '12m') {
            $layouts = max($layouts, (int) $this->theme_layouts_12m);
            $palettes = max($palettes, (int) $this->theme_palettes_12m);
        }
        if ($layouts === 0 || $palettes === 0) {
            return [0, 0];
        }

        return [
            min($layouts, \App\Support\Themes\ThemeCatalog::layoutCount()),
            min($palettes, \App\Support\Themes\ThemeCatalog::paletteCount()),
        ];
    }

    /** چند قالب سایت/پنل مشتری/اپ (علاوه بر «طلایی ماهرو») در این بازه باز است */
    public function siteThemeAllowance(?string $period): int
    {
        [$layouts, $palettes] = $this->siteThemeGrid($period);

        return $layouts * $palettes;
    }

    /** چند قالب پنل مدیر (علاوه بر منوی کناری پیش‌فرض) — به بازه بستگی ندارد */
    public function adminThemeAllowance(): int
    {
        return min((int) $this->theme_admin_count, \App\Support\Themes\ThemeCatalog::adminTotal());
    }
}
