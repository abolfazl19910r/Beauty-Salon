<?php

namespace App\Support\Themes;

use App\Models\Plan;
use App\Models\Salon;

/**
 * ⭐ ظاهر مؤثر یک سالن (بسته‌ی «و»): قالبی که مدیر انتخاب کرده (salons.site_theme / admin_theme) فقط وقتی اعمال می‌شود
 * که پلن و بازه‌ی فعلی آن را باز کرده باشد؛ وگرنه پیش‌فرض. انتخاب هرگز پاک نمی‌شود — با تمدید یا ارتقا برمی‌گردد.
 *
 * - اشتراک تمام‌شده یا تعلیق‌شده ← فقط پیش‌فرض.
 * - دوره‌ی آزمایشی ← عدد پایه‌ی پلن آزمایشی (بی بازه).
 * - سالن پیش از پلن‌ها (plan_id خالی) ← همان دسترسی پلن «کامل» (همان قاعده‌ی امکانات).
 * - بازه = salons.subscription_type (۱m/۳m/۶m/۱۲m).
 */
final class SalonAppearance
{
    /** @var array<string, self> */
    private static array $memo = [];

    /**
     * @param  list<string>  $allowedSite  قالب‌های سایتِ باز (بی پیش‌فرض)
     * @param  list<string>  $allowedAdmin  قالب‌های پنل مدیرِ باز (با پیش‌فرض)
     */
    private function __construct(
        public readonly string $site,
        public readonly string $layout,
        public readonly ?string $palette,
        public readonly string $admin,
        public readonly ?string $chosenSite,
        public readonly ?string $chosenAdmin,
        public readonly array $allowedSite,
        public readonly array $allowedAdmin,
        public readonly ?string $period,
    ) {}

    public static function default(): self
    {
        return new self(ThemeCatalog::DEFAULT_SITE, ThemeCatalog::DEFAULT_SITE, null, ThemeCatalog::DEFAULT_ADMIN, null, null, [], [ThemeCatalog::DEFAULT_ADMIN], null);
    }

    public static function for(?Salon $salon, ?string $previewSite = null): self
    {
        if ($salon === null) {
            return self::default();
        }

        $plan = $salon->effectivePlan() ?? Plan::byKey(Plan::FULL);
        $key = implode('|', [
            $salon->id, $salon->site_theme, $salon->admin_theme, $plan?->id, $plan?->updated_at?->timestamp,
            $salon->subscription_type, $salon->subscription_ends_at?->timestamp, (int) $salon->is_suspended,
            implode(',', ThemeCatalog::layoutOrder()), implode(',', ThemeCatalog::paletteOrder()), $previewSite,
        ]);

        return self::$memo[$key] ??= self::resolve($salon, $plan, $previewSite);
    }

    public static function flush(): void
    {
        self::$memo = [];
    }

    public function isDefaultSite(): bool
    {
        return $this->site === ThemeCatalog::DEFAULT_SITE;
    }

    /** انتخاب مدیر الان اعمال نمی‌شود (پلن/بازه/اشتراک اجازه نمی‌دهد) */
    public function siteChoiceSuspended(): bool
    {
        return $this->chosenSite !== null && $this->chosenSite !== $this->site;
    }

    public function adminChoiceSuspended(): bool
    {
        return $this->chosenAdmin !== null && $this->chosenAdmin !== $this->admin;
    }

    public function siteAllowed(string $key): bool
    {
        return $key === ThemeCatalog::DEFAULT_SITE || in_array($key, $this->allowedSite, true);
    }

    public function adminAllowed(string $key): bool
    {
        return in_array($key, $this->allowedAdmin, true);
    }

    /** نمای صفحه‌ی خانه‌ی سالن */
    public function homeView(): string
    {
        return $this->isDefaultSite() ? 'home' : 'salon-home.'.$this->layout;
    }

    /**
     * برای API اپ‌ها: کلید، چیدمان، پالت و رنگ‌های هگز تیره/روشن.
     *
     * @return array<string, mixed>
     */
    public function toApi(): array
    {
        $tokens = ThemeCatalog::sitePaletteTokens($this->palette);

        return [
            'key' => $this->site,
            'layout' => $this->layout,
            'palette' => $this->palette ?? 'mahru',
            'name' => ThemeCatalog::siteName($this->site),
            'colors' => [
                'dark' => ThemeCatalog::toHex($tokens['dark']),
                'light' => ThemeCatalog::toHex($tokens['light']),
            ],
        ];
    }

    private static function resolve(Salon $salon, ?Plan $plan, ?string $previewSite): self
    {
        $allowedSite = [];
        $allowedAdmin = [ThemeCatalog::DEFAULT_ADMIN];
        $period = null;

        if ($plan !== null && $salon->hasActiveSubscription()) {
            $period = $plan->is_trial ? null : ($salon->subscription_type ?: null);
            [$layouts, $palettes] = $plan->siteThemeGrid($period);
            $allowedSite = ThemeCatalog::allowedSiteKeys($layouts, $palettes);
            $allowedAdmin = ThemeCatalog::allowedAdminKeys($plan->adminThemeAllowance());
        }

        $chosenSite = ThemeCatalog::isSiteKey($salon->site_theme) ? $salon->site_theme : null;
        $chosenAdmin = ThemeCatalog::isAdminKey($salon->admin_theme) ? $salon->admin_theme : null;

        $site = $chosenSite !== null && ($chosenSite === ThemeCatalog::DEFAULT_SITE || in_array($chosenSite, $allowedSite, true))
            ? $chosenSite : ThemeCatalog::DEFAULT_SITE;
        // پیش‌نمایش «ظاهر سالن» (فقط برای مدیر همین سالن، کنترلر بررسی می‌کند) — قفل‌شده‌ها هم دیده می‌شوند
        if ($previewSite !== null && ThemeCatalog::isSiteKey($previewSite)) {
            $site = $previewSite;
        }
        $admin = $chosenAdmin !== null && in_array($chosenAdmin, $allowedAdmin, true) ? $chosenAdmin : ThemeCatalog::DEFAULT_ADMIN;
        [$layout, $palette] = ThemeCatalog::splitSite($site);

        return new self($site, $layout, $palette, $admin, $chosenSite, $chosenAdmin, $allowedSite, $allowedAdmin, $period);
    }
}
