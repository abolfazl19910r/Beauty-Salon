<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $mainCategories = [
            'مو' => ['رنگ مو', 'کوتاهی مو', 'شینیون', 'براشینگ', 'صاف کردن مو'],
            'ناخن' => ['مانیکور', 'پدیکور', 'کاشت ناخن', 'ترمیم ناخن'],
            'صورت' => ['پاکسازی پوست', 'میکرودرم', 'لیزر', 'اصلاح صورت'],
            'ابرو' => ['اصلاح ابرو', 'میکروبلیدینگ', 'تاتو ابرو', 'هاشور ابرو']
        ];

        $orderCounter = 1;

        foreach ($mainCategories as $main => $subCategories) {
            $mainCategory = Category::firstOrCreate(
                ['slug' => Str::slug($main)],
                [
                    'name' => $main,
                    'description' => 'دسته‌بندی خدمات ' . $main,
                    'is_active' => true,
                    'order' => $orderCounter++,
                    'icon' => 'icon-' . Str::slug($main),
                ]
            );

            foreach ($subCategories as $index => $subCategory) {
                Category::firstOrCreate(
                    ['slug' => Str::slug($subCategory)],
                    [
                        'name' => $subCategory,
                        'description' => 'زیر دسته ' . $main,
                        'parent_id' => $mainCategory->id,
                        'is_active' => true,
                        'order' => $index + 1,
                    ]
                );
            }
        }

        Category::factory(5)->create();
        Category::factory(5)->withParent()->create();
    }
}
