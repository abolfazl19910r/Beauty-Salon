<?php

namespace App\Support\Themes;

use App\Models\Salon;
use App\Support\CurrentSalon;

/**
 * ظاهر سالن جاریِ همین درخواست (برای نماها). یک بار برای هر سالن حساب می‌شود.
 *
 * پیش‌نمایش «ظاهر سالن»: ?theme_preview=vitrin:rose فقط برای مدیر همین سالن (یا ابرمدیر) اثر دارد تا قفل‌شده‌ها را هم ببیند؛
 * برای بقیه نادیده گرفته می‌شود.
 */
class CurrentAppearance
{
    private ?string $salonKey = null;

    private ?SalonAppearance $resolved = null;

    public function __construct(private readonly CurrentSalon $currentSalon) {}

    public function get(): SalonAppearance
    {
        $salon = $this->currentSalon->get();
        // همان شیء سالن و همان انتخاب (درخواست بعدی در همان تست/پردازه شیء تازه‌ای از middleware می‌گیرد)
        $key = $salon ? spl_object_id($salon).'|'.$salon->site_theme.'|'.$salon->admin_theme.'|'.$salon->plan_id.'|'.json_encode(request()?->query('theme_preview')) : 'none';
        if ($this->resolved !== null && $this->salonKey === $key) {
            return $this->resolved;
        }

        $this->salonKey = $key;

        return $this->resolved = SalonAppearance::for($salon, $this->previewFor($salon));
    }

    public function forget(): void
    {
        $this->resolved = null;
        $this->salonKey = null;
    }

    private function previewFor(?Salon $salon): ?string
    {
        $preview = request()?->query('theme_preview');
        if (! is_string($preview) || $salon === null || ! ThemeCatalog::isSiteKey($preview)) {
            return null;
        }
        $user = auth()->user();
        if ($user === null) {
            return null;
        }
        if ($user->hasRole('super-admin') || $salon->admins()->where('users.id', $user->id)->exists()) {
            return $preview;
        }

        return null;
    }
}
