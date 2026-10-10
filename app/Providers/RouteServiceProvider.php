<?php

namespace App\Providers;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/admin/dashboard';

    public const USER_HOME = '/dashboard';

    public const SPECIALIST_HOME = '/my-dashboard';

    public function boot(): void
    {
        $this->configureModelBindings();

        $this->configureRateLimiting();

        // Note: routes/web.php, routes/api.php and admin/* routes are loaded via
        // bootstrap/app.php -> withRouting(). Previously, here
        // base_path('routes/web.php') and routes/admin/reports.php
        // would be required again, which would result in duplicate entries of the same routes in the RouteCollection
        // . This block has been removed intentionally.
    }

    protected function configureModelBindings(): void
    {
        Route::bind('specialist', function ($value) {
            return Specialist::findOrFail($value);
        });

        Route::bind('service', function ($value) {
            return BeautyService::findOrFail($value);
        });

        Route::bind('booking', function ($value) {
            return Booking::findOrFail($value);
        });

        Route::bind('user', function ($value) {
            return User::findOrFail($value);
        });

        Route::pattern('id', '[0-9a-f-]+');

        Route::pattern('slug', '[a-z0-9-]+');
        Route::pattern('year', '[0-9]{4}');
        Route::pattern('month', '[0-9]{1,2}');
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // ⭐ /api/v1 (بسته‌ی ۱ اپلیکیشن) — limiterهای جدا از 'auth' وب: limiterهای نام‌دار یک بودجه‌ی مشترک
        // برای همه‌ی مسیرهای هم‌نام دارند، پس ورود اپ نباید سهمیه‌ی ورود وب را مصرف کند (و برعکس).
        // کلید «آی‌پی + شماره/challenge» حدس کد یک حساب را محدود می‌کند؛ سقف جدای آی‌پی بالاتر است چون
        // کاربران اینترنت همراه پشت CGNAT آی‌پی مشترک دارند و سقف ۵ تایی فقط با آی‌پی همه را می‌بست.
        RateLimiter::for('api-v1-login', function (Request $request) {
            $subject = (string) ($request->input('phone') ?? $request->input('challenge') ?? '');

            return [
                Limit::perMinute((int) config('api.rate_limits.login_per_subject_per_minute', 5))
                    ->by('subject:'.$request->ip().'|'.$subject),
                Limit::perMinute((int) config('api.rate_limits.login_per_ip_per_minute', 30))
                    ->by('ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('api-v1', function (Request $request) {
            return Limit::perMinute((int) config('api.rate_limits.authenticated_per_minute', 120))
                ->by($request->user()?->id ? 'user:'.$request->user()->id : 'ip:'.$request->ip());
        });

        // کارهای حساس واردشده با رمز فعلی (تغییر شبا از اپ همکار، بسته‌ی ۲): ۵ در دقیقه برای هر کاربر
        RateLimiter::for('api-v1-sensitive', function (Request $request) {
            return Limit::perMinute(5)->by('user:'.($request->user()?->id ?? $request->ip()));
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(
                (int) config('auth.max_login_attempts', 5),
                (int) config('auth.login_throttle_minutes', 1)
            )->by($request->ip());
        });

        // ⭐ Wired up (post-test-writing-phase, follow-up to throttle:auth): three independent
        // limiters — deliberately NOT sharing the 'auth' limiter above or each other, for the
        // same reason 'auth' itself was scoped to only the login flow: Laravel's named rate
        // limiter shares one cache key per limiter name + ->by() value regardless of which
        // route hit it, so sharing a bucket across unrelated flows (e.g. failed logins
        // counting against a registration attempt) would be confusing and semantically wrong.
        //
        // Hardcoded (not env-configurable, matching the existing 'sensitive' limiter's style
        // just below) rather than adding three more pairs of .env.example keys — these are new,
        // narrower-purpose limiters, not a general-purpose "auth attempts" concept like
        // MAX_LOGIN_ATTEMPTS was, and multiplying env surface area for values with no immediate
        // operational need to tune wasn't asked for.
        //
        // Both real threats these close: (1) SMS abuse — forgot-password/register-resend/
        // phone-verification-resend each trigger a real Kavenegar SMS; unthrottled, an attacker
        // could bombard a victim's phone with codes (harassment, or draining SMS credit) without
        // needing to guess anything. (2) OTP brute-force — reset-password and
        // verify-phone/verify both check a 6-digit code with a plain equality check and no
        // per-code attempt limit; within the code's short validity window, an unthrottled
        // attacker could script-guess it.
        RateLimiter::for('registration', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('phone-verification', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('sensitive', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()->id);
        });
    }
}
