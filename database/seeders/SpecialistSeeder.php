<?php

namespace Database\Seeders;

use App\Models\BeautyService;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\Specialist;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;

class SpecialistSeeder extends Seeder
{
    public function run(): void
    {
        $specialists = [
            [
                'name' => 'آرزو حسینی',
                'phone' => '09123456789',
                'email' => 'arezoo@example.com',
                'services' => ['رنگ کامل مو', 'کوتاهی ساده'],
            ],
            [
                'name' => 'شیما کریمی',
                'phone' => '09123456790',
                'email' => 'shima@example.com',
                'services' => ['مانیکور ساده', 'پدیکور ساده'],
            ],
            [
                'name' => 'مریم رضایی',
                'phone' => '09123456791',
                'email' => 'maryam@example.com',
                'services' => ['پاکسازی ساده', 'پاکسازی عمیق'],
            ],
            [
                'name' => 'سارا محمدی',
                'phone' => '09123456792',
                'email' => 'sara@example.com',
                'services' => ['اصلاح ساده ابرو', 'اصلاح تخصصی ابرو'],
            ],
            [
                'name' => 'لیلا اکبری',
                'phone' => '09123456793',
                'email' => 'leila@example.com',
                'services' => ['کوتاهی مدل‌دار', 'کوتاهی تخصصی'],
            ]
        ];

        foreach ($specialists as $data) {
            $specialist = Specialist::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
            ]);

            WorkSchedule::create([
                'specialist_id' => $specialist->id,
                'work_days' => [0, 1, 2, 3, 4],
                'start_time' => '09:00',
                'end_time' => '17:00',
                'is_active' => true,
            ]);

            $usedDates = [];
            for ($i = 0; $i < 2; $i++) {
                $attempts = 0;
                do {
                    $randomDate = now()->addDays(rand(10, 60))->format('Y-m-d');
                    $attempts++;

                    if ($attempts > 50) {
                        break;
                    }
                } while (in_array($randomDate, $usedDates) ||
                Holiday::where('specialist_id', $specialist->id)
                    ->where('date', $randomDate)
                    ->exists());

                if (!in_array($randomDate, $usedDates) && $attempts <= 50) {
                    $usedDates[] = $randomDate;

                    Holiday::create([
                        'specialist_id' => $specialist->id,
                        'date' => $randomDate,
                        'description' => 'تعطیلی شخصی',
                    ]);
                }
            }

            foreach ($data['services'] as $serviceName) {
                $service = BeautyService::where('name', $serviceName)->first();
                if ($service) {
                    $specialist->services()->attach($service->id);
                }
            }

            if (rand(0, 1)) {
                $startDate = now()->addDays(rand(15, 30));
                $endDate = $startDate->copy()->addDays(rand(1, 10));

                Leave::create([
                    'specialist_id' => $specialist->id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => rand(0, 1) ? 'pending' : 'approved',
                    'reason' => 'مرخصی شخصی',
                ]);
            }
        }

        Specialist::factory(5)
            ->create()
            ->each(function ($specialist) {
                WorkSchedule::create([
                    'specialist_id' => $specialist->id,
                    'work_days' => [0, 1, 2, 3, 4],
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                    'is_active' => true,
                ]);

                $services = BeautyService::inRandomOrder()->limit(rand(2, 4))->get();
                $specialist->services()->attach($services->pluck('id'));

                $generatedDates = [];
                $holidayCount = rand(1, 3);

                for ($i = 0; $i < $holidayCount; $i++) {
                    $attempts = 0;
                    do {
                        $randomDate = now()->addDays(rand(10, 60))->format('Y-m-d');
                        $attempts++;

                        if ($attempts > 50) {
                            break;
                        }
                    } while (in_array($randomDate, $generatedDates) ||
                    Holiday::where('specialist_id', $specialist->id)
                        ->where('date', $randomDate)
                        ->exists());

                    if (!in_array($randomDate, $generatedDates) && $attempts <= 50) {
                        $generatedDates[] = $randomDate;

                        Holiday::create([
                            'specialist_id' => $specialist->id,
                            'date' => $randomDate,
                            'description' => fake()->optional()->sentence() ?: 'تعطیلی',
                        ]);
                    }
                }
            });
    }
}
