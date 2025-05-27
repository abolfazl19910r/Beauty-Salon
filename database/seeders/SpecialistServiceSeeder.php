<?php

namespace Database\Seeders;

use App\Models\BeautyService;
use App\Models\Specialist;
use Illuminate\Database\Seeder;

class SpecialistServiceSeeder extends Seeder
{
    public function run(): void
    {

        $specialists = Specialist::all();
        $services = BeautyService::all();

        if ($specialists->isEmpty() || $services->isEmpty()) {
            return;
        }

        foreach ($specialists as $specialist) {
            $randomServices = $services->random(rand(2, 5));

            foreach ($randomServices as $service) {
                if (!$specialist->services()->where('beauty_service_id', $service->id)->exists()) {
                    $specialist->services()->attach($service->id);
                }
            }
        }
    }
}
