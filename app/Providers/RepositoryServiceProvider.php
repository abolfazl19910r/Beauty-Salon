<?php

namespace App\Providers;

use App\Repositories\Contracts\BeautyServiceRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\HolidayRepositoryInterface;
use App\Repositories\Contracts\LeaveRepositoryInterface;
use App\Repositories\Contracts\SpecialistRepositoryInterface;
use App\Repositories\Contracts\SpecialistScheduleRepositoryInterface;
use App\Repositories\Eloquent\BeautyServiceRepository;
use App\Repositories\Eloquent\CategoryRepository;
use App\Repositories\Eloquent\HolidayRepository;
use App\Repositories\Eloquent\LeaveRepository;
use App\Repositories\Eloquent\SpecialistRepository;
use App\Repositories\Eloquent\SpecialistScheduleRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    protected array $repositories = [
        CategoryRepositoryInterface::class => CategoryRepository::class,
        BeautyServiceRepositoryInterface::class => BeautyServiceRepository::class,
        SpecialistRepositoryInterface::class => SpecialistRepository::class,
        SpecialistScheduleRepositoryInterface::class => SpecialistScheduleRepository::class,
        LeaveRepositoryInterface::class => LeaveRepository::class,
        HolidayRepositoryInterface::class => HolidayRepository::class,
    ];

    public function register(): void
    {
        foreach ($this->repositories as $interface => $concrete) {
            $this->app->bind($interface, $concrete);
        }
    }
}
