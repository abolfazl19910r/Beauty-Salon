<?php

namespace App\Providers;

use App\Channels\SmsChannel;
use App\Channels\TelegramChannel;
use App\Models\Booking;
use App\Models\DiscountCode;
use App\Observers\Booking\BookingObserver;
use App\Observers\DiscountCodeObserver;
use App\Repositories\Contracts\SalonRepositoryInterface;
use App\Services\SecurePaymentService;
use App\Services\TwoFactorAuthService;
use App\Support\CurrentSalon;
use App\View\Composers\ViewComposer;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Channels\DatabaseChannel as BaseDatabaseChannel;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TwoFactorAuthService::class);
        $this->app->singleton(SecurePaymentService::class);
        // ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 2): one instance per
        // request — see CurrentSalon's own docblock for why it must never persist across requests.
        $this->app->singleton(CurrentSalon::class);

        // ⭐ laravel/telescope در require-dev است. قبلاً App\Providers\TelescopeServiceProvider در bootstrap/providers.php
        // بی‌قید ثبت بود؛ با «composer install --no-dev» (image داکر و هر نصب production استاندارد) کلاس والدش
        // وجود نداشت و برنامه اصلاً boot نمی‌شد (هر درخواست و هر دستور artisan، از جمله migrate). حالا فقط وقتی
        // پکیج نصب است ثبت می‌شه — روی XAMPP/توسعه رفتار دقیقاً مثل قبله (ضبط با TELESCOPE_ENABLED کنترل می‌شه).
        if (class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class)) {
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::component('layouts.guest', 'guest-layout');
        View::composer('*', ViewComposer::class);

        Paginator::useTailwind();

        Blade::if('role', function ($role) {
            return auth()->check() && auth()->user()->hasRole($role);
        });

        Blade::if('permission', function ($permission) {
            return auth()->check() && auth()->user()->hasPermission($permission);
        });

        Booking::observe(BookingObserver::class);
        DiscountCode::observe(DiscountCodeObserver::class);

        $this->app->extend(ChannelManager::class, function ($manager) {
            $manager->extend('database', function ($app) {
                return new class($app->make('db'), $app->make('events')) extends BaseDatabaseChannel
                {
                    protected function buildPayload($notifiable, $notification)
                    {
                        $payload = parent::buildPayload($notifiable, $notification);

                        $payload['user_id'] = \App\Models\UserNotification::ownerUserId($notifiable->getMorphClass(), $notifiable->getKey());

                        return $payload;
                    }
                };
            });

            return $manager;
        });

        Notification::extend('sms', function ($app) {
            return $app->make(SmsChannel::class);
        });

        Notification::extend('telegram', function ($app) {
            return $app->make(TelegramChannel::class);
        });
        // ⭐ Fallback سراسری: روت‌های name('home') و امثال آن زیر s/{salon_slug}
        // از صفحات عمومی (login، admin، داشبورد متخصص) قابل تولید باشند؛
        // ResolveSalonFromRoute در هر درخواست /s/{slug} آن را با اسلاگ واقعی بازنویسی می‌کند.
        //
        // ⭐ Fix (real crash, found while verifying this change): boot() runs on every single
        // request/bootstrap, including ones before the 'salons' table exists yet — a fresh
        // 'php artisan migrate' run, any environment mid-setup, or (concretely) every test in
        // this project's own suite before RefreshDatabase has actually created the schema for
        // that test. Without this guard, Salon::query()->first() threw an unhandled
        // QueryException ("no such table: salons") on literally every boot, taking the whole
        // app down — confirmed by running the full suite, which went from 940 passing to 939
        // errors the moment this line was added unguarded.
        //
        // Also cached: unlike CurrentSalon (bound fresh per request on purpose — see its own
        // docblock), the *oldest* salon by id can never change once it exists (ids are immutable
        // and never reassigned), so there's no reason to hit the database for it on every single
        // request/boot forever. rememberForever needs no invalidation logic as a result.
        if (Schema::hasTable('salons')) {
            $defaultSalonSlug = Cache::rememberForever(
                'app:default_salon_slug',
                fn () => app(SalonRepositoryInterface::class)->getOldestSlug()
            );

            if ($defaultSalonSlug) {
                URL::defaults(['salon_slug' => $defaultSalonSlug]);
            }
        }
    }
}
