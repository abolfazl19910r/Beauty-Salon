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

        foreach ($mainCategories as $main => $subCategories) {
            $mainCategory = Category::create([
                'name' => $main,
                'slug' => Str::slug($main),
                'description' => 'دسته‌بندی خدمات ' . $main,
                'is_active' => true,
                'order' => 10,
            ]);

            foreach ($subCategories as $index => $subCategory) {
                Category::create([
                    'name' => $subCategory,
                    'slug' => Str::slug($subCategory),
                    'description' => 'زیر دسته ' . $main,
                    'parent_id' => $mainCategory->id,
                    'is_active' => true,
                    'order' => $index + 1,
                ]);
            }
        }
    }
}
