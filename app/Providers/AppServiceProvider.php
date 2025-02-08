<?php

namespace App\Providers;

use App\Services\SecurePaymentService;
use App\Services\TwoFactorAuthService;
use App\View\Composers\ViewComposer;
use Illuminate\Support\Facades\Blade;
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
    }
}
