<?php

namespace App\Providers;

use App\Services\SMSService;
use Illuminate\Support\ServiceProvider;

class SMSServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(SMSService::class, function ($app) {
            return new SMSService(
                config('services.sms.api_key'),
                config('services.sms.line_number'),
                config('services.sms.base_url')
            );
        });
    }

    public function boot()
    {
        //
    }
}
