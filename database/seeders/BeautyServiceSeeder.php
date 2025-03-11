<?php

namespace Database\Seeders;

use App\Models\BeautyService;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BeautyServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            'رنگ مو' => [
                'رنگ کامل مو' => [200000, 120],
                'هایلایت' => [300000, 180],
                'بالیاژ' => [350000, 210],
                'اصلاح رنگ' => [250000, 150],
            ],
            'کوتاهی مو' => [
                'کوتاهی ساده' => [100000, 60],
                'کوتاهی مدل‌دار' => [150000, 90],
                'کوتاهی تخصصی' => [200000, 120],
            ],
            'مانیکور' => [
                'مانیکور ساده' => [80000, 45],
                'مانیکور ژل' => [120000, 60],
                'مانیکور اکلیلی' => [150000, 75],
            ],
            'پدیکور' => [
                'پدیکور ساده' => [100000, 60],
                'پدیکور ژل' => [150000, 90],
                'پدیکور اسپا' => [200000, 120],
            ],
            'پاکسازی پوست' => [
                'پاکسازی ساده' => [250000, 90],
                'پاکسازی عمیق' => [350000, 120],
                'پاکسازی تخصصی' => [450000, 150],
            ],
            'اصلاح ابرو' => [
                'اصلاح ساده ابرو' => [80000, 30],
                'اصلاح تخصصی ابرو' => [120000, 45],
                'اصلاح و رنگ ابرو' => [150000, 60],
            ]
        ];

        foreach ($services as $categoryName => $items) {
            $category = Category::where('name', $categoryName)->first();

            if (!$category) {
                continue;
            }

            foreach ($items as $serviceName => $details) {
                BeautyService::create([
                    'name' => $serviceName,
                    'slug' => Str::slug($serviceName),
                    'description' => 'توضیحات خدمت ' . $serviceName,
                    'price' => $details[0],
                    'duration' => $details[1],
                    'category_id' => $category->id,
                ]);
            }
        }
    }
}
