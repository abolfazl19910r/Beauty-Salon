<?php

namespace App\Services\SuperAdmin;

/** کلیدهای session و قاعده‌های «ورود اضطراری» ابرمدیر (۲۰۲۶-۱۰-۱۵). */
class SuperAdminImpersonation
{
    public const SESSION_KEY = 'impersonator_id';

    public const SALON_KEY = 'impersonated_salon_id';

    /** کارهایی که در حالت پشتیبانی نباید به نام مالک انجام شوند (رمز و مشخصات ورود خود مالک) */
    public const BLOCKED_ROUTES = ['admin.profile.password', 'admin.profile.update'];

    public static function active(): bool
    {
        return (bool) session(self::SESSION_KEY);
    }
}
