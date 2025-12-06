<?php

namespace App\Providers;

use App\Services\SecurePaymentService;
use App\Services\TwoFactorAuthService;
use App\View\Composers\ViewComposer;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

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
    }
}
