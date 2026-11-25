<?php

namespace Tests\Feature\Review;

use App\Models\Review;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * بسته‌ی ۵الف: صفحه‌ی عمومی «نظرها»ی سالن (فقط‌خواندنی، نام کوتاه، فیلتر کارشناس/ستاره، پیوندها و نقشه‌ی سایت).
 */
class SalonReviewsPageTest extends TestCase
{
    use RefreshDatabase;

    private Specialist $sara;

    private Specialist $neda;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Cache::flush();
        $this->sara = Specialist::factory()->create(['name' => 'سارا']);
        $this->neda = Specialist::factory()->create(['name' => 'ندا']);
    }

    private function review(Specialist $specialist, int $rating, array $extra = []): Review
    {
        return Review::factory()->create(array_merge([
            'specialist_id' => $specialist->id,
            'user_id' => User::factory()->create(['name' => 'مریم احمدی', 'user_type' => 'customer'])->id,
            'overall_rating' => $rating,
            'comment' => 'نظر '.$rating,
            'reviewed_at' => now(),
        ], $extra));
    }

    public function test_page_lists_approved_reviews_with_short_names_replies_and_stats(): void
    {
        $this->review($this->sara, 5, ['specialist_response' => 'ممنون از لطفتان']);
        $this->review($this->neda, 3);
        $this->review($this->sara, 1, ['is_approved' => false, 'comment' => 'متن-تأییدنشده-۷۷']);

        $html = $this->get(route('salon.reviews'))->assertOk()->getContent();

        $this->assertStringContainsString('مریم ا.', $html, 'نام کوچک + حرف اول');
        $this->assertStringNotContainsString('مریم احمدی', $html);
        $this->assertStringContainsString('پاسخ سارا', $html);
        $this->assertStringContainsString('ممنون از لطفتان', $html);
        $this->assertStringNotContainsString('متن-تأییدنشده-۷۷', $html, 'نظر تأییدنشده نیست');
        $this->assertStringContainsString('از ۲ نظر', $html);
        $this->assertStringContainsString('>۴</div>', $html, 'میانگین (۵+۳)/۲');
        $this->assertSame(2, substr_count($html, 'data-review-card'));
        $this->assertStringNotContainsString('name="rating"', $html, 'ثبت نظر در این صفحه نیست');
        $this->assertSame(1, preg_match('/<title>نظر مشتری‌ها \| [^|<]+<\/title>/u', $html), 'نام سالن یک بار در عنوان');
    }

    public function test_filters_by_expert_and_rating(): void
    {
        $this->review($this->sara, 5);
        $this->review($this->neda, 3);
        $this->review($this->neda, 5);

        $this->assertSame(1, substr_count($this->get(route('salon.reviews', ['specialist' => $this->sara->id]))->getContent(), 'data-review-card'));
        $this->assertSame(2, substr_count($this->get(route('salon.reviews', ['rating' => 5]))->getContent(), 'data-review-card'));
        $this->assertSame(1, substr_count($this->get(route('salon.reviews', ['rating' => 5, 'specialist' => $this->neda->id]))->getContent(), 'data-review-card'));
        $this->get(route('salon.reviews', ['rating' => 9]))->assertOk()->assertSee('data-review-card', false);
        $this->get(route('salon.reviews', ['rating' => 2]))->assertOk()->assertSee('نظری با این فیلتر نیست');
    }

    public function test_another_salons_reviews_never_show(): void
    {
        $this->review($this->sara, 5, ['comment' => 'نظر این سالن']);
        $home = app(CurrentSalon::class)->get();
        $other = Salon::factory()->create(['slug' => 'other-salon']);
        app(CurrentSalon::class)->set($other);
        $this->review(Specialist::factory()->create(), 4, ['comment' => 'نظر سالن دیگر']);
        app(CurrentSalon::class)->set($home);

        $this->get(route('salon.reviews'))->assertOk()->assertSee('نظر این سالن')->assertDontSee('نظر سالن دیگر');
    }

    public function test_links_appear_only_when_the_salon_has_approved_reviews(): void
    {
        $this->get(route('home'))->assertOk()->assertDontSee('data-footer-reviews', false);
        $this->get('/s/rasta/sitemap.xml')->assertOk()->assertDontSee('/reviews');

        $this->review($this->sara, 4);
        Cache::flush();

        $this->get(route('home'))->assertOk()->assertSee('data-footer-reviews', false)->assertSee('نظر مشتری‌ها (۱)');
        $this->get('/s/rasta/sitemap.xml')->assertOk()->assertSee('/s/rasta/reviews');
        $this->assertContains('salon.reviews', config('seo.salon_routes'), 'ایندکس‌پذیر مثل خدمات (canonical بی فیلتر)');
    }

    public function test_expert_reviews_page_shows_short_names_and_links_to_the_salon_page(): void
    {
        $this->review($this->sara, 5);
        $this->review($this->sara, 4, ['user_id' => User::factory()->create(['name' => ' ', 'user_type' => 'customer'])->id]);

        $this->get(route('reviews.specialist', $this->sara))->assertOk()
            ->assertSee('مریم ا.')->assertDontSee('مریم احمدی')
            ->assertSee('مشتری سالن')->assertDontSee('کاربر رستا')
            ->assertSee(route('salon.reviews', ['specialist' => $this->sara->id]), false);
    }
}
