<?php

namespace Database\Seeders;

use App\Models\Specialist;
use App\Models\SpecialistSchedule;
use App\Models\SpecialistLeave;
use Illuminate\Database\Seeder;

class SpecialistSeeder extends Seeder
{
    public function run(): void
    {
        $manager = Specialist::factory()->create([
            'name' => 'متخصص اصلی (تایید خودکار)',
            'email' => 'specialist@example.com',
            'auto_confirm_bookings' => true,
        ]);

        SpecialistSchedule::factory(5)->create([
            'specialist_id' => $manager->id,
            'day_of_week' => fake()->unique()->numberBetween(0, 6),
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);

        SpecialistLeave::factory()->approved()->create([
            'specialist_id' => $manager->id,
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(12),
        ]);

        Specialist::factory(5)->manualConfirm()->create()->each(function (Specialist $specialist) {
            SpecialistSchedule::factory(3)->create([
                'specialist_id' => $specialist->id,
            ]);
            SpecialistLeave::factory()->create([
                'specialist_id' => $specialist->id,
                'status' => 'pending'
            ]);
        });

        Specialist::factory(3)->autoConfirm()->create()->each(function (Specialist $specialist) {
            SpecialistSchedule::factory(5)->create([
                'specialist_id' => $specialist->id,
            ]);
        });
    }
}
