<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\RepositoryServiceProvider::class,
    App\Providers\Event\EventServiceProvider::class,
    App\Providers\RouteServiceProvider::class,
    Kavenegar\Laravel\ServiceProvider::class,
    Maatwebsite\Excel\ExcelServiceProvider::class,
    Barryvdh\DomPDF\ServiceProvider::class,
    Hekmatinasser\Verta\Laravel\VertaServiceProvider::class,
];
