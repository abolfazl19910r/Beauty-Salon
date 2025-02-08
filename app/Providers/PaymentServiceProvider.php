<?php

namespace App\Providers;

use App\Services\PaymentService;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(PaymentService::class, function ($app) {
            return new PaymentService(
                config('services.zarinpal.merchant_id'),
                config('services.zarinpal.sandbox')
            );
        });
    }

    public function boot()
    {
        //
    }
}
