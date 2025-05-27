<?php

namespace Database\Factories;

use App\Models\BeautyService;
use App\Models\Specialist;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\SpecialistService;

class SpecialistServiceFactory extends Factory
{
    protected $model = SpecialistService::class;

    public function definition(): array
    {
        return [
            'specialist_id' => Specialist::factory(),
            'beauty_service_id' => BeautyService::factory(),
        ];
    }
}
