<?php

namespace Database\Seeders;

use App\Models\BeautyService;
use App\Models\Booking;
use App\Models\ServiceCategory;
use App\Models\Specialist;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'مدیر',
            'phone' => '09399717435',
            'password' => bcrypt('admin'),
            'is_admin' => true
        ]);

        User::factory()->create([
            'name' => 'کاربر تست',
            'phone' => '09111111111',
            'password' => bcrypt('password'),
            'is_admin' => false
        ]);

        $categories = [
            'مو' => ['کوتاهی', 'رنگ', 'شینیون'],
            'ناخن' => ['مانیکور', 'پدیکور', 'کاشت'],
            'پوست' => ['پاکسازی', 'میکرودرم', 'لیزر'],
            'ابرو' => ['اصلاح', 'میکروبلیدینگ', 'تاتو']
        ];

        foreach ($categories as $category => $services) {
            $cat = ServiceCategory::create([
                'name' => $category,
                'slug' => Str::slug($category)
            ]);

            foreach ($services as $service) {
                BeautyService::create([
                    'name' => $service,
                    'slug' => Str::slug($service),
                    'category_id' => $cat->id,
                    'price' => fake()->numberBetween(100000, 1000000),
                    'duration' => fake()->randomElement([30, 60, 90])
                ]);
            }
        }

        Specialist::factory(5)->create()->each(function ($specialist) {
            $services = BeautyService::inRandomOrder()->limit(3)->get();
            $specialist->services()->attach($services);

            WorkSchedule::create([
                'specialist_id' => $specialist->id,
                'work_days' => [0,1,2,3,4],
                'start_time' => '09:00',
                'end_time' => '17:00',
                'is_active' => true
            ]);
        });

        Booking::factory(20)->create();
    }
}
