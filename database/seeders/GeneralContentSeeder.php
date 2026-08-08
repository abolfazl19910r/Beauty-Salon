<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\GalleryImage;
use Illuminate\Database\Seeder;

class GeneralContentSeeder extends Seeder
{
    public function run(): void
    {
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

        foreach ($announcements as $data) {
            Announcement::updateOrCreate(
                ['title' => $data['title']],
                array_merge($data, [
                    'is_active' => true,
                    'published_at' => now(),
                    'expires_at' => now()->addDays(30),
                ])
            );
        }

        $galleryImages = [
            'سالن زیبایی ما', 'فضای آرامش‌بخش اسپا', 'خدمات کوتاهی مو',
            'خدمات آرایش صورت', 'خدمات ناخن', 'تیم متخصصین ما',
            'محیط مدرن سالن', 'نمونه کارهای ما',
        ];

        foreach ($galleryImages as $index => $title) {
            GalleryImage::updateOrCreate(
                ['title' => $title],
                [
                    'description' => 'توضیحات تصویر '.$title,
                    'image_path' => 'gallery/image'.($index + 1).'.jpg',
                    'order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
