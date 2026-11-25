<?php

namespace Tests\Feature\User;

use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * سربرگ سایت سالن برای مشتری (بسته‌ی ۴ ظاهری، ۲۰۲۶-۱۱-۲۵؛ تصمیم «الف»): ردیف اول منو + منوی حساب (خروج داخلش) + دکمه‌ی
 * «رزرو نوبت»؛ ردیف دوم تب‌های حساب فقط در صفحه‌های حساب با شمار نوبت‌های پیش رو؛ منوی پایین موبایل در همه‌ی صفحه‌ها.
 */
class CustomerMenuTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['user_type' => 'customer', 'salon_id' => app(CurrentSalon::class)->id()]);
        $make = fn (string $status, int $days, int $hour) => Booking::factory()->create([
            'user_id' => $this->user->id, 'status' => $status, 'booking_time' => now()->addDays($days)->setTime($hour, 0),
        ]);
        $make('confirmed', 1, 10);
        $make('pending_payment', 2, 11);
        $make('pending', 3, 12);
        $make('completed', -3, 10);
        $make('cancelled', 4, 13);
    }

    public function test_account_page_has_a_sidebar_with_the_upcoming_count_and_one_logout_inside_the_menu(): void
    {
        $html = $this->actingAs($this->user)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('data-account-sidebar', $html);
        preg_match_all('/data-account-tab="([a-z]+)"/', $html, $m);
        $this->assertSame('dashboard', $m[1][0]);
        $this->assertContains('bookings', $m[1]);
        $this->assertContains('wallet', $m[1]);
        $this->assertContains('profile', $m[1]);
        $this->assertMatchesRegularExpression('/data-account-tab="bookings"[^>]*>.*?data-account-badge>۳</s', $html);
        $this->assertMatchesRegularExpression('/data-account-tab="dashboard"\s+aria-current="page"/', $html);

        $this->assertMatchesRegularExpression('/<details[^>]*data-account-menu.*?خروج.*?<\/details>/s', $html);
        $this->assertSame(1, substr_count($html, '>خروج</button>') - substr_count($html, 'block w-full text-right py-2 nav-link">خروج</button>') - substr_count($html, 'data-account-logout>خروج</button>'));
        // نوار کناری حساب (آزمون ویندوز بسته‌ی ۴): کارت مشتری با موجودی کیف پول، منو با آیکون، رزرو و خروج
        $this->assertStringContainsString('data-account-card', $html);
        $this->assertStringContainsString('data-account-wallet', $html);
        $this->assertSame(1, substr_count($html, 'data-account-logout'));
        $this->assertStringContainsString('data-header-book', $html);
        $this->assertStringNotContainsString('امتیازات من', $html);
    }

    public function test_public_pages_have_no_tabs_but_keep_the_mobile_bottom_menu(): void
    {
        $html = $this->actingAs($this->user)->get(route('services.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('data-account-sidebar', $html);
        $this->assertSame(1, preg_match('/<nav[^>]*data-customer-bottom[^>]*>(.*?)<\/nav>/s', $html, $bottom));
        foreach (['خانه', 'نوبت‌ها', 'رزرو', 'کیف پول', 'پروفایل'] as $label) {
            $this->assertStringContainsString($label, $bottom[1]);
        }
        $this->assertStringContainsString('href="'.route('bookings.create').'"', $bottom[1]);
    }

    public function test_guests_get_no_customer_menus(): void
    {
        $this->get(route('services.index'))->assertOk()
            ->assertDontSee('data-customer-bottom', false)->assertDontSee('<details class="relative" data-account-menu', false)
            ->assertSee('data-header-book', false)->assertSee('ورود');
    }

    public function test_staff_get_the_work_panel_link_and_no_customer_menus(): void
    {
        $specialist = Specialist::factory()->create();
        $this->actingAs(User::find($specialist->user_id))->get(route('services.index'))->assertOk()
            ->assertDontSee('data-customer-bottom', false)->assertDontSee('data-account-sidebar', false)
            ->assertSee('data-account-menu', false)->assertSee('پنل کاری');
    }

    public function test_loyalty_tab_follows_the_salon_feature(): void
    {
        $salon = app(CurrentSalon::class)->get();
        $html = $this->actingAs($this->user)->get(route('dashboard'))->getContent();
        $this->assertSame($salon->hasFeature(\App\Support\Features\Feature::Loyalty), str_contains($html, 'data-account-tab="loyalty"'));
    }
}
