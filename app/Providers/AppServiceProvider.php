<?php

namespace App\Providers;

use App\Channels\SmsChannel;
use App\Channels\TelegramChannel;
use App\Models\Booking;
use App\Models\Salon;
use App\Models\DiscountCode;
use App\Observers\Booking\BookingObserver;
use App\Observers\DiscountCodeObserver;
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
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;


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

                        $payload['user_id'] = $notifiable->id;

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
                fn () => Salon::query()->oldest('id')->value('slug')
            );

            if ($defaultSalonSlug) {
                URL::defaults(['salon_slug' => $defaultSalonSlug]);
            }
        }
    }
}
