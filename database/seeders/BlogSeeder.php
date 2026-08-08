<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['phone' => '09399717435'],
            [
                'name' => 'مدیر سیستم',
                'password' => \Illuminate\Support\Facades\Hash::make('admin'),
            ]
        );

        $categoriesData = [
            'مراقبت از پوست' => 'نکاتی برای مراقبت از پوست و زیبایی بیشتر',
            'مراقبت از مو' => 'آموزش‌ها و نکات مربوط به نگهداری از مو',
            'آرایش و زیبایی' => 'آموزش‌ها و ترفندهای آرایشی',
            'سلامتی' => 'نکاتی برای سلامت بهتر و زیبایی طبیعی',
            'اخبار و رویدادها' => 'جدیدترین اخبار و رویدادهای سالن',
        ];

        foreach ($categoriesData as $name => $desc) {
            BlogCategory::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $desc,
                    'order' => array_search($name, array_keys($categoriesData)) + 1,
                ]
            );
        }

        $staticPosts = [
            ['title' => 'چگونه از پوست خود در تابستان مراقبت کنیم', 'category' => 'مراقبت از پوست'],
            ['title' => 'بهترین روش‌های مراقبت از موهای رنگ شده', 'category' => 'مراقبت از مو'],
            ['title' => 'ترندهای آرایشی سال ۱۴۰۲', 'category' => 'آرایش و زیبایی'],
            ['title' => 'تغذیه مناسب برای داشتن پوستی شاداب', 'category' => 'سلامتی'],
            ['title' => 'افتتاح بخش جدید اسپا در سالن ما', 'category' => 'اخبار و رویدادها'],
        ];

        foreach ($staticPosts as $postData) {
            $cat = BlogCategory::where('name', $postData['category'])->first();
            if ($cat) {
                BlogPost::firstOrCreate(
                    ['slug' => Str::slug($postData['title'])],
                    [
                        'title' => $postData['title'],
                        'content' => fake()->paragraphs(5, true),
                        'excerpt' => fake()->paragraph(),
                        'category_id' => $cat->id,
                        'author_id' => $admin->id,
                        'is_published' => true,
                        'published_at' => now()->subDays(rand(1, 30)),
                        'views' => rand(10, 500),
                    ]
                );
            }
        }

        BlogPost::factory(20)->create([
            'author_id' => $admin->id,
        ]);

        BlogPost::factory(3)->scheduled()->create(['author_id' => $admin->id]);
        BlogPost::factory(2)->unpublished()->create(['author_id' => $admin->id]);
    }
}
