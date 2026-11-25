<?php

namespace App\Support\Customer;

use App\Models\CustomerCourse;
use App\Models\User;
use App\Services\Customer\CustomerBookingListFilter;
use App\Support\CurrentSalon;
use App\Support\Features\Feature;

/**
 * منوی حساب مشتری در سایت سالن (بسته‌ی ۴ ظاهری، ۲۰۲۶-۱۱-۲۵) — یک منبع برای منوی حساب ردیف اول سربرگ، تب‌های حساب
 * (ردیف دوم، فقط در صفحه‌های حساب) و منوی پایین موبایل (همه‌ی صفحه‌ها برای مشتری واردشده؛ تصمیم «الف»).
 */
final class CustomerMenu
{
    /** @var array<string, array{route: string, patterns: list<string>, label: string, icon: string}> */
    public const TABS = [
        'dashboard' => [
            'route' => 'dashboard',
            'patterns' => ['dashboard'],
            'label' => 'داشبورد',
            'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        ],
        'bookings' => [
            'route' => 'bookings.index',
            'patterns' => ['bookings.index', 'bookings.show', 'bookings.reschedule'],
            'label' => 'نوبت‌های من',
            'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        ],
        'wallet' => [
            'route' => 'wallet.index',
            'patterns' => ['wallet.*'],
            'label' => 'کیف پول',
            'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        ],
        'loyalty' => [
            'route' => 'loyalty.index',
            'patterns' => ['loyalty.*'],
            'label' => 'باشگاه مشتریان',
            'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        'courses' => [
            'route' => 'my-courses.index',
            'patterns' => ['my-courses.*'],
            'label' => 'دوره‌های من',
            'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
        ],
        'profile' => [
            'route' => 'profile.show',
            'patterns' => ['profile.*', 'security.*', 'complaints.*'],
            'label' => 'پروفایل',
            'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        ],
    ];

    /** منوی پایین موبایل: «خانه / نوبت‌ها / [+ رزرو] / کیف پول / پروفایل» */
    public const BOTTOM = ['dashboard' => 'خانه', 'bookings' => 'نوبت‌ها', 'book' => 'رزرو', 'wallet' => 'کیف پول', 'profile' => 'پروفایل'];

    /** مشتری سالن (نه کادر سالن که در سایت «پنل کاری» دارد) */
    public static function isCustomer(?User $user): bool
    {
        return $user !== null && ! $user->isSpecialistAccount() && app(CurrentSalon::class)->get() !== null;
    }

    /**
     * @return list<array{key: string, url: string, label: string, icon: string, active: bool, badge: int}>
     */
    public static function tabs(User $user): array
    {
        $salon = app(CurrentSalon::class)->get();
        $loyaltyOn = $salon?->hasFeature(Feature::Loyalty) ?? false;
        $coursesOn = ($salon?->hasFeature(Feature::TreatmentCourses) ?? false)
            || CustomerCourse::query()->where('user_id', $user->id)->where('status', '!=', 'cancelled')->exists();
        $upcoming = CustomerBookingListFilter::upcomingCount($user->id);

        $tabs = [];
        foreach (self::TABS as $key => $tab) {
            if (($key === 'loyalty' && ! $loyaltyOn) || ($key === 'courses' && ! $coursesOn)) {
                continue;
            }
            $tabs[] = [
                'key' => $key,
                'url' => route($tab['route']),
                'label' => $tab['label'],
                'icon' => $tab['icon'],
                'active' => request()->routeIs(...$tab['patterns']),
                'badge' => $key === 'bookings' ? $upcoming : 0,
            ];
        }

        return $tabs;
    }

    /** صفحه‌ی فعلی یکی از صفحه‌های حساب است (ردیف دوم سربرگ فقط همین‌جا؛ تصمیم «الف») */
    public static function isAccountPage(): bool
    {
        foreach (self::TABS as $tab) {
            if (request()->routeIs(...$tab['patterns'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array{key: string}>  $tabs
     * @return list<array{key: string, url: string, label: string, icon: string, active: bool, badge: int}>
     */
    public static function bottom(array $tabs): array
    {
        $byKey = array_column($tabs, null, 'key');
        $items = [];
        foreach (self::BOTTOM as $key => $label) {
            if ($key === 'book') {
                $items[] = ['key' => 'book', 'url' => route('bookings.create'), 'label' => $label, 'icon' => 'M12 5v14m-7-7h14', 'active' => request()->routeIs('bookings.create', 'bookings.confirm'), 'badge' => 0];

                continue;
            }
            $items[] = ['label' => $label] + $byKey[$key];
        }

        return $items;
    }
}
