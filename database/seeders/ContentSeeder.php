<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\GalleryImage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $blogCategories = [
            'مراقبت از پوست' => 'نکاتی برای مراقبت از پوست و زیبایی بیشتر',
            'مراقبت از مو' => 'آموزش‌ها و نکات مربوط به نگهداری از مو',
            'آرایش و زیبایی' => 'آموزش‌ها و ترفندهای آرایشی',
            'سلامتی' => 'نکاتی برای سلامت بهتر و زیبایی طبیعی',
            'اخبار و رویدادها' => 'جدیدترین اخبار و رویدادهای سالن',
        ];

        $categories = [];

        foreach ($blogCategories as $name => $description) {
            $categories[] = BlogCategory::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => $description,
                'order' => array_search($name, array_keys($blogCategories)) + 1,
            ]);
        }

        $blogPosts = [
            [
                'title' => 'چگونه از پوست خود در تابستان مراقبت کنیم',
                'content' => fake()->paragraphs(5, true),
                'category' => 'مراقبت از پوست',
            ],
            [
                'title' => 'بهترین روش‌های مراقبت از موهای رنگ شده',
                'content' => fake()->paragraphs(5, true),
                'category' => 'مراقبت از مو',
            ],
            [
                'title' => 'ترندهای آرایشی سال ۱۴۰۲',
                'content' => fake()->paragraphs(5, true),
                'category' => 'آرایش و زیبایی',
            ],
            [
                'title' => 'تغذیه مناسب برای داشتن پوستی شاداب',
                'content' => fake()->paragraphs(5, true),
                'category' => 'سلامتی',
            ],
            [
                'title' => 'افتتاح بخش جدید اسپا در سالن ما',
                'content' => fake()->paragraphs(5, true),
                'category' => 'اخبار و رویدادها',
            ],
        ];

        $admin = User::where('is_admin', true)->first();
        if (!$admin) {
            $admin = User::first();
        }

        foreach ($blogPosts as $post) {
            $category = BlogCategory::where('name', $post['category'])->first();

            if ($category) {
                BlogPost::create([
                    'title' => $post['title'],
                    'slug' => Str::slug($post['title']),
                    'content' => $post['content'],
                    'excerpt' => Str::words($post['content'], 20),
                    'category_id' => $category->id,
                    'author_id' => $admin->id,
                    'is_published' => true,
                    'published_at' => now()->subDays(rand(1, 30)),
                ]);
            }
        }

        BlogPost::create([
            'title' => 'جدیدترین تکنیک‌های کراتینه کردن مو',
            'slug' => Str::slug('جدیدترین تکنیک‌های کراتینه کردن مو'),
            'content' => fake()->paragraphs(5, true),
            'excerpt' => fake()->paragraph(),
            'category_id' => BlogCategory::where('name', 'مراقبت از مو')->first()->id,
            'author_id' => $admin->id,
            'is_published' => false,
            'published_at' => now()->addDays(5),
        ]);

        $announcements = [
            [
                'title' => 'ساعات کاری جدید',
                'content' => 'سالن ما از تاریخ ۱ مرداد با ساعت کاری جدید از ۸ صبح تا ۸ شب پذیرای شما عزیزان خواهد بود.',
                'type' => 'general',
                'priority' => 5,
            ],
            [
                'title' => 'تعطیلی موقت بخش اسپا',
                'content' => 'به دلیل تعمیرات، بخش اسپای سالن از تاریخ ۱۰ تا ۱۵ مرداد تعطیل خواهد بود.',
                'type' => 'maintenance',
                'priority' => 8,
            ],
            [
                'title' => 'تخفیف ویژه عید',
                'content' => 'به مناسبت عید سعید فطر، کلیه خدمات سالن با ۲۰٪ تخفیف ارائه می‌شود.',
                'type' => 'promotion',
                'priority' => 10,
            ],
        ];

        foreach ($announcements as $announcement) {
            Announcement::create([
                'title' => $announcement['title'],
                'content' => $announcement['content'],
                'type' => $announcement['type'],
                'priority' => $announcement['priority'],
                'is_active' => true,
                'published_at' => now(),
                'expires_at' => now()->addDays(30),
            ]);
        }

        $galleryImages = [
            'سالن زیبایی ما',
            'فضای آرامش‌بخش اسپا',
            'خدمات کوتاهی مو',
            'خدمات آرایش صورت',
            'خدمات ناخن',
            'تیم متخصصین ما',
            'محیط مدرن سالن',
            'نمونه کارهای ما',
        ];

        foreach ($galleryImages as $index => $title) {
            GalleryImage::create([
                'title' => $title,
                'description' => 'توضیحات تصویر ' . $title,
                'image_path' => 'gallery/image' . ($index + 1) . '.jpg',
                'order' => $index + 1,
                'is_active' => true,
            ]);
        }
    }
}
