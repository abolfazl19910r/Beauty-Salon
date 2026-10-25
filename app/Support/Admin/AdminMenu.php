<?php

namespace App\Support\Admin;

use App\Models\Salon;
use App\Models\User;
use App\Support\Features\Feature;

/**
 * ⭐ بسته‌ی «و»: منوی پنل مدیر سالن در یک جا. هر ۴ قالب پنل مدیر (منوی کناری، ریل آیکونی، منوی بالایی، فشرده) همین را
 * می‌کشند، پس هیچ صفحه یا قاعده‌ی دسترسی در یک قالب جا نمی‌ماند (AdminTemplatesTest همه را در هر ۴ قالب بررسی می‌کند).
 * آیتم‌ها و شرط‌ها دقیقاً همان منوی کناری پیش از این بسته‌اند، به‌علاوه‌ی «ظاهر سالن» برای مالک.
 */
final class AdminMenu
{
    /**
     * @return list<array{label: string, items: list<array{key: string, label: string, url: string, active: bool, icon: string, lock: bool, badge: ?string}>}>
     */
    public static function groups(?User $user, ?Salon $salon): array
    {
        $can = fn (?string $permission) => $permission === null || ($user !== null && $user->hasPermission($permission));
        $locked = fn (Feature $feature) => ! ($salon?->hasFeature($feature) ?? true);
        $isOwner = $user !== null && ($user->hasRole('super-admin')
            || ($salon !== null && $salon->admins()->wherePivot('user_id', $user->id)->wherePivot('role', 'owner')->exists()));

        $groups = [
            ['داشبورد', [
                self::item('dashboard', 'داشبورد', 'admin.dashboard', 'admin.dashboard', 'grid'),
                self::item('salon-qr', 'QR و پوستر سالن', 'admin.salon-qr.index', 'admin.salon-qr.*', 'qr'),
                $can('view-reports') ? self::item('reports', 'گزارشات', 'admin.reports.index', 'admin.reports*', 'pie') : null,
            ]],
            ['مدیریت', [
                $can('view-services') ? self::item('services', 'خدمات', 'admin.services.index', 'admin.services*', 'help') : null,
                $can('view-specialists') ? self::item('specialists', 'متخصصین', 'admin.specialists.index', 'admin.specialists*', 'user') : null,
                $can('view-specialists') ? self::item('leaves', 'مرخصی‌ها', 'admin.leaves.index', 'admin.leaves*', 'calendar') : null,
                $can('view-bookings') ? self::item('bookings', 'نوبت‌ها', 'admin.bookings.index', 'admin.bookings*', 'calendar') : null,
                $can('view-bookings') ? self::item('customers', 'مشتری‌ها', 'admin.customers.index', 'admin.customers*', 'users') : null,
                $can('view-bookings') ? self::item('courses', 'دوره‌های درمانی', 'admin.courses.index', 'admin.courses*', 'check', $locked(Feature::TreatmentCourses)) : null,
                $can('view-reviews') ? self::item('reviews', 'نظرات و ارزیابی‌ها', 'admin.reviews.index', 'admin.reviews*', 'star') : null,
                $can('view-categories') ? self::item('categories', 'دسته‌بندی‌ها', 'admin.categories.index', 'admin.categories*', 'layers') : null,
            ]],
            ['سیستم‌ها', [
                self::item('notifications', 'اعلانات', 'admin.notifications.index', 'admin.notifications*', 'bell', false, 'sidebar-notification-count'),
                $can('view-loyalty') ? self::item('loyalty', 'امتیازات', 'admin.loyalty.index', 'admin.loyalty*', 'heart', $locked(Feature::Loyalty)) : null,
                $can('view-loyalty') ? self::item('winback', 'پیگیری مشتریان غایب', 'admin.winback.index', 'admin.winback*', 'undo', $locked(Feature::Winback)) : null,
                $can('view-blog') ? self::item('blog', 'وبلاگ', 'admin.blog.index', 'admin.blog*', 'book') : null,
                $can('view-gallery') ? self::item('gallery', 'گالری', 'admin.gallery.index', 'admin.gallery*', 'image') : null,
                $can('view-announcements') ? self::item('announcements', 'اطلاعیه', 'admin.announcements.index', 'admin.announcements*', 'file') : null,
                $can('view-discount-codes') ? self::item('discount-codes', 'کدهای تخفیف', 'admin.discount-codes.index', 'admin.discount-codes*', 'tag') : null,
                $can('view-security-logs') ? self::item('security', 'امنیت', 'admin.security.logs', 'admin.security*', 'shield') : null,
                $can('view-security-logs') ? self::item('notification-settings', 'تنظیمات اطلاع‌رسانی', 'admin.notification-settings.index', 'admin.notification-settings*', 'bell') : null,
            ]],
            // «اطلاعات سالن» و «ظاهر سالن» فقط برای مالک (همان قاعده‌ی EnsureSalonOwner)
            ['سالن', $salon !== null && $isOwner ? [
                self::item('salon-settings', 'اطلاعات سالن', 'admin.salon-settings.edit', 'admin.salon-settings.*', 'home'),
                // بسته‌ی مجوز 5د
                self::item('verification', 'احراز سالن', 'admin.verification.show', 'admin.verification.*', 'shield'),
                self::item('appearance', 'ظاهر سالن', 'admin.appearance.edit', 'admin.appearance.*', 'palette'),
                self::item('payment-gateways', 'درگاه‌های پرداخت', 'admin.payment-gateways.index', 'admin.payment-gateways.*', 'card'),
            ] : []],
            ['مدیریت کاربران', [
                $can('view-users') ? self::item('users', 'کاربران', 'admin.users.index', 'admin.users*', 'users') : null,
                $can('manage-roles') ? self::item('permissions', 'دسترسی‌ها', 'admin.permissions.index', 'admin.permissions*', 'lock') : null,
                $can('manage-roles') ? self::item('roles', 'نقش‌ها', 'admin.roles.index', 'admin.roles*', 'badge') : null,
            ]],
            ['امور مالی', [
                // اشتراک و صورتحساب (۲۰۲۶-۱۰-۱۷): در منوی بسته‌ی «و» جا مانده بود؛ صفحه فقط با redirect انقضا یا آدرس مستقیم باز می‌شد
                self::item('billing', 'اشتراک و صورتحساب', 'admin.billing.index', 'admin.billing.*', 'receipt'),
                self::item('wallets', 'کیف پول‌ها', 'admin.wallet.index', 'admin.wallet.index', 'wallet'),
                self::item('withdrawals', 'درخواست‌های برداشت', 'admin.wallet.withdrawals', 'admin.wallet.withdrawals*', 'cash'),
                self::item('wallet-settings', 'تنظیمات مالی', 'admin.wallet.settings', 'admin.wallet.settings', 'coin'),
            ]],
            ['تنظیمات', [
                self::item('support', 'پشتیبانی', 'admin.support-tickets.index', 'admin.support-tickets*', 'chat'),
                self::item('profile', 'پروفایل', 'admin.profile.edit', 'admin.profile*', 'user'),
            ]],
        ];

        $out = [];
        foreach ($groups as [$label, $items]) {
            $items = array_values(array_filter($items));
            if ($items !== []) {
                $out[] = ['label' => $label, 'items' => $items];
            }
        }

        return $out;
    }

    /** @return array{key: string, label: string, url: string, active: bool, icon: string, lock: bool, badge: ?string} */
    private static function item(string $key, string $label, string $route, string $pattern, string $icon, bool $lock = false, ?string $badge = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'url' => route($route),
            'active' => request()->routeIs($pattern),
            'icon' => self::ICONS[$icon],
            'lock' => $lock,
            'badge' => $badge,
        ];
    }

    /** محتوای داخلی SVG (viewBox 0 0 24 24، خطی) — همان آیکون‌های منوی قبلی */
    public const ICONS = [
        'qr' => '<rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect><path d="M14 14h3v3h-3zM20 14v.01M14 20h.01M17 17h3v3"></path>',
        'grid' => '<rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect>',
        'pie' => '<path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path>',
        'help' => '<circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line>',
        'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
        'check' => '<path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>',
        'star' => '<path d="M12 2.5l2.94 5.96 6.56.95-4.75 4.63 1.12 6.54L12 17.5l-5.87 3.08 1.12-6.54L2.5 9.41l6.56-.95z"></path>',
        'layers' => '<polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline>',
        'bell' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path>',
        'heart' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>',
        'undo' => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"></path><path d="M3 3v5h5"></path>',
        'book' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>',
        'image' => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline>',
        'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line>',
        'tag' => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>',
        'home' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline>',
        'palette' => '<path d="M12 22a10 10 0 1 1 10-10c0 2.8-2.2 4-4.5 4H15a2 2 0 0 0-1.6 3.2A1.7 1.7 0 0 1 12 22z"></path><circle cx="7.5" cy="10.5" r="1"></circle><circle cx="12" cy="7" r="1"></circle><circle cx="16.5" cy="10.5" r="1"></circle>',
        'receipt' => '<path d="M6 2h12v20l-3-2-3 2-3-2-3 2z"></path><line x1="9" y1="7" x2="15" y2="7"></line><line x1="9" y1="11" x2="15" y2="11"></line><line x1="9" y1="15" x2="13" y2="15"></line>',
        'card' => '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line>',
        'lock' => '<rect x="4" y="11" width="16" height="10" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path>',
        'badge' => '<path d="M9 11h6m-3-3v6M17 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h10z"></path>',
        'wallet' => '<path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 0 0 3-3V8a3 3 0 0 0-3-3H6a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3z"></path>',
        'cash' => '<path d="M17 9V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2m2 4h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2zm7-5a2 2 0 1 1-4 0 2 2 0 0 1 4 0z"></path>',
        'coin' => '<circle cx="12" cy="12" r="9"></circle><path d="M14.5 9.5c-.5-.9-1.4-1.5-2.5-1.5-1.7 0-3 .9-3 2s1.3 2 3 2 3 .9 3 2-1.3 2-3 2c-1.1 0-2-.6-2.5-1.5M12 6v2m0 8v2"></path>',
        'chat' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line>',
        'search' => '<circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>',
    ];
}
