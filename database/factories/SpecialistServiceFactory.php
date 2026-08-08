<?php

namespace Database\Factories;

use App\Models\BeautyService;
use App\Models\Specialist;
use App\Models\SpecialistService;
use Illuminate\Database\Eloquent\Factories\Factory;

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
