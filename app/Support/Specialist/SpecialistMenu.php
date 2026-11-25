<?php

namespace App\Support\Specialist;

use App\Models\Specialist;
use App\Services\Specialist\SpecialistBookingListFilter;

/**
 * منوی پنل کارشناس (بسته‌ی ۳ ظاهری، ۲۰۲۶-۱۱-۲۵) — یک منبع برای منوی کناری، نوار بالای چیدمان‌های مجله/آرام/بنتو،
 * کشوی موبایل و منوی پایین موبایل. آیتم تازه فقط همین‌جا اضافه شود.
 */
final class SpecialistMenu
{
    /** @var array<string, array{route: string, patterns: list<string>, label: string, icon: string}> */
    public const ITEMS = [
        'dashboard' => [
            'route' => 'specialist.my-dashboard',
            'patterns' => ['specialist.my-dashboard'],
            'label' => 'داشبورد',
            'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
        ],
        'bookings' => [
            'route' => 'specialist.bookings.index',
            'patterns' => ['specialist.bookings*'],
            'label' => 'نوبت‌ها',
            'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        ],
        'schedule' => [
            'route' => 'specialist.schedule',
            'patterns' => ['specialist.schedule*'],
            'label' => 'ساعت کاری',
            'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        'leaves' => [
            'route' => 'specialist.leaves.index',
            'patterns' => ['specialist.leaves*'],
            'label' => 'مرخصی‌ها',
            'icon' => 'M3 10h18M7 3v4m10-4v4M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2zM9.5 14.5l5 3m0-3l-5 3',
        ],
        'wallet' => [
            'route' => 'specialist.wallet.index',
            'patterns' => ['specialist.wallet*'],
            'label' => 'کیف پول',
            'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
        ],
        'reviews' => [
            'route' => 'specialist.reviews.index',
            'patterns' => ['specialist.reviews*'],
            'label' => 'نظرها',
            'icon' => 'M11.48 3.5a.56.56 0 011.04 0l2.13 5.11a.56.56 0 00.47.35l5.52.44c.5.04.7.66.32.99l-4.2 3.6a.56.56 0 00-.18.56l1.28 5.38a.56.56 0 01-.84.61l-4.72-2.88a.56.56 0 00-.59 0l-4.72 2.88a.56.56 0 01-.84-.61l1.28-5.38a.56.56 0 00-.18-.56l-4.2-3.6a.56.56 0 01.32-.99l5.52-.44a.56.56 0 00.47-.35z',
        ],
        'reports' => [
            'route' => 'specialist.reports.index',
            'patterns' => ['specialist.reports*'],
            'label' => 'گزارش',
            'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        ],
        // تصمیم ۱۱-۲۵: «پروفایل» و «QR من» یک آیتم؛ صفحه‌ی QR می‌ماند و زیر همین آیتم فعال است
        'profile' => [
            'route' => 'specialist.profile.show',
            'patterns' => ['specialist.profile*', 'specialist.qr*'],
            'label' => 'پروفایل و QR',
            'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        ],
    ];

    /** منوی پایین موبایل (کلید ← برچسب کوتاه) */
    public const BOTTOM = [
        'dashboard' => 'خانه',
        'bookings' => 'نوبت‌ها',
        'wallet' => 'کیف پول',
        'profile' => 'پروفایل',
    ];

    /**
     * @return list<array{key: string, route: string, url: string, label: string, short: string, icon: string, active: bool, badge: int}>
     */
    public static function items(?Specialist $specialist): array
    {
        $waiting = $specialist ? SpecialistBookingListFilter::waitingCount($specialist) : 0;
        $items = [];

        foreach (self::ITEMS as $key => $item) {
            $items[] = [
                'key' => $key,
                'route' => $item['route'],
                'url' => route($item['route']),
                'label' => $item['label'],
                'short' => self::BOTTOM[$key] ?? $item['label'],
                'icon' => $item['icon'],
                'active' => request()->routeIs(...$item['patterns']),
                'badge' => $key === 'bookings' ? $waiting : 0,
            ];
        }

        return $items;
    }

    /** @param  list<array{key: string}>  $items */
    public static function bottom(array $items): array
    {
        $byKey = array_column($items, null, 'key');

        return array_values(array_map(fn (string $key) => $byKey[$key], array_keys(self::BOTTOM)));
    }
}
