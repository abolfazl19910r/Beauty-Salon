<?php

namespace App\Providers;

use App\Channels\SmsChannel;
use App\Channels\TelegramChannel;
use App\Models\Booking;
use App\Models\DiscountCode;
use App\Observers\Booking\BookingObserver;
use App\Observers\DiscountCodeObserver;
use App\Services\SecurePaymentService;
use App\Services\TwoFactorAuthService;
use App\View\Composers\ViewComposer;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Channels\DatabaseChannel as BaseDatabaseChannel;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Notification;
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
    }
}
