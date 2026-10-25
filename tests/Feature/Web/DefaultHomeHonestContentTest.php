<?php

namespace Tests\Feature\Web;

use App\Models\GalleryImage;
use App\Models\Review;
use App\Models\Specialist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 🐞 بسته‌ی مجوز 5د: قالب پیش‌فرض صفحه‌ی سالن (home.blade.php) وقتی داده نبود محتوای ساختگی نشان می‌داد — سه نظر با نام آدم‌های
 * واقعی‌نما («الهام صادقی»…)، آمار ثابت («۴۵۰۰ مشتری راضی»، «۱۸ متخصص»، «امتیاز ۴٫۹») و عکس‌های Unsplash با عنوان «نمونه‌ی کارهای
 * انجام‌شده در این سالن». تبلیغ فریبنده (مواد ۵۰ تا ۵۳ قانون تجارت الکترونیکی؛ تخلف سطح ۳ اینماد) و نظر ساختگی. حالا فقط داده‌ی
 * واقعی همان سالن، و بخشی که داده ندارد نمایش داده نمی‌شود.
 */
class DefaultHomeHonestContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_salon_without_data_shows_no_invented_reviews_numbers_or_photos(): void
    {
        $html = $this->get('/s/rasta')->assertOk()->getContent();

        foreach (['الهام صادقی', 'مهسا رستمی', 'نازنین قاسمی', 'زهرا احمدی', 'میکاپ عروس', 'گواهینامه‌های معتبر بین‌المللی', 'محصولات ارگانیک',
            'photo-1571019613454', 'photo-1438761681033', 'photo-1487412912498', 'photo-1500010214352', 'photo-1508158099174', 'data-target="4500"', 'data-target="18"', 'data-target="4.9"'] as $fake) {
            $this->assertStringNotContainsString($fake, $html, $fake);
        }
        $this->assertStringNotContainsString('id="testimonials"', $html, 'بخش نظرها بی نظر واقعی نیست');
        $this->assertStringNotContainsString('id="gallery"', $html, 'بخش گالری بی عکس واقعی نیست');
    }

    public function test_real_reviews_gallery_and_counts_are_shown(): void
    {
        $specialist = Specialist::factory()->create();
        Review::factory()->create(['specialist_id' => $specialist->id, 'is_approved' => true, 'overall_rating' => 4, 'comment' => 'کار تمیز و به‌موقع بود.']);
        Review::factory()->create(['specialist_id' => $specialist->id, 'is_approved' => false, 'comment' => 'تأییدنشده نباید بیاید']);
        GalleryImage::create(['title' => 'کار ما', 'image_path' => 'gallery/a.jpg', 'is_active' => true, 'order' => 1]);

        $html = $this->get('/s/rasta')->assertOk()->getContent();

        $this->assertStringContainsString('کار تمیز و به‌موقع بود.', $html);
        $this->assertStringNotContainsString('تأییدنشده نباید بیاید', $html);
        $this->assertStringContainsString('storage/gallery/a.jpg', $html);
        $this->assertStringContainsString('data-target="4"', $html, 'میانگین امتیاز واقعی');
    }
}
