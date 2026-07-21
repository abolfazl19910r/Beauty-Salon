<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\Event\EventServiceProvider::class,
    App\Providers\RouteServiceProvider::class,
    App\Providers\TelescopeServiceProvider::class,
    Kavenegar\Laravel\ServiceProvider::class,
    Maatwebsite\Excel\ExcelServiceProvider::class,
    Barryvdh\DomPDF\ServiceProvider::class,
    Hekmatinasser\Verta\Laravel\VertaServiceProvider::class,
];
