<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Support\Collection;

/**
 * دسته‌بندی‌های خدمات برای منوی هدر و صفحه‌ی خدمات سایت سالن (بسته‌ی «ح»، ۱۱، ۲۰۲۶-۱۰-۱۹).
 *
 * فقط دسته‌ی فعالی که دست‌کم یک خدمت دارد، به ترتیب خود سالن (پیش‌تر صفحه‌ی خدمات همه‌ی دسته‌ها، حتی خالی و غیرفعال را
 * نشان می‌داد). یک کوئری در هر درخواست (نگه‌داشته در attributes همان درخواست)؛ بیرون از سالن (پنل‌ها، دامنه‌ی اصلی) خالی.
 *
 * @return Collection<int, Category> هر کدام با services_count
 */
final class SalonNavCategories
{
    public static function get(): Collection
    {
        $salon = app(CurrentSalon::class)->get();
        if (! $salon) {
            return collect();
        }

        $key = 'salon_nav_categories.'.$salon->id;
        $attributes = request()->attributes;
        if (! $attributes->has($key)) {
            $attributes->set($key, Category::query()
                ->where('is_active', true)
                ->whereHas('services')
                ->withCount('services')
                ->orderBy('order')
                ->orderBy('name')
                ->get(['id', 'name', 'order']));
        }

        return $attributes->get($key);
    }
}
