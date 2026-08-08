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
            $randomServices = $services->random(rand(3, min(7, $services->count())));

            $serviceIds = $randomServices->pluck('id')->toArray();
            $specialist->services()->sync($serviceIds);
        }

        $manager = Specialist::where('email', 'specialist@example.com')->first();
        if ($manager) {
            $manager->services()->sync($services->pluck('id'));
        }
    }
}
