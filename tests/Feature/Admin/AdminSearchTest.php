<?php

namespace Tests\Feature\Admin;

use App\Models\BeautyService;
use App\Models\BlogPost;
use App\Models\Booking;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_index_groups_results_by_persian_labelled_category(): void
    {
        Specialist::factory()->create(['name' => 'سارا رضایی']);
        BeautyService::factory()->create(['name' => 'رنگ مو رضایی']);

        $response = $this->actingAs($this->admin)->get('/admin/search?q=رضایی');

        $response->assertOk();
        $results = $response->viewData('results');
        $this->assertArrayHasKey('متخصصین', $results);
        $this->assertArrayHasKey('خدمات', $results);
        $this->assertArrayNotHasKey('کاربران', $results);
    }

    public function test_index_with_no_query_returns_empty_results(): void
    {
        Specialist::factory()->create();

        $response = $this->actingAs($this->admin)->get('/admin/search');

        $response->assertOk();
        $this->assertEmpty($response->viewData('results'));
    }

    public function test_api_search_requires_at_least_2_characters(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/admin/search/api?q=a');

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_api_search_returns_grouped_typed_results_with_urls(): void
    {
        $user = User::factory()->create(['name' => 'محمد احمدی', 'phone' => '09121234567']);
        $service = BeautyService::factory()->create(['name' => 'اصلاح مو احمدی']);
        $specialist = Specialist::factory()->create(['name' => 'احمدی متخصص']);
        $post = BlogPost::factory()->create(['title' => 'مقاله احمدی']);
        $booking = Booking::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->admin)->getJson('/admin/search/api?q=احمدی');

        $response->assertOk();
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertSame($data['total'], count($data['results']['users']) + count($data['results']['services'])
            + count($data['results']['specialists']) + count($data['results']['blog_posts']) + count($data['results']['bookings']));

        $this->assertNotEmpty($data['results']['users']);
        $this->assertSame(route('admin.users.show', $user->id), $data['results']['users'][0]['url']);
        $this->assertNotEmpty($data['results']['services']);
        $this->assertNotEmpty($data['results']['specialists']);
        $this->assertNotEmpty($data['results']['blog_posts']);
    }

    public function test_api_search_finds_bookings_by_customer_phone(): void
    {
        $user = User::factory()->create(['phone' => '09129998877']);
        Booking::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($this->admin)->getJson('/admin/search/api?q=09129998877');

        $response->assertOk();
        $this->assertNotEmpty($response->json('results.bookings'));
    }

    /**
     * ⭐ (۲۰۲۶-۰۹-۲۶) users ستون email نداره (ورود با موبایل). searchUsers روی email فیلتر می‌کرد:
     * - MySQL/MariaDB (production): خطای 1054 → کل API جست‌وجو ۵۰۰ (دو تست بالا روی MariaDB شکست می‌خوردن)؛
     * - SQLite: "email"ی ناشناخته رشته‌ی ثابت 'email' حساب می‌شه → 'email' LIKE '%mail%' برای همه‌ی کاربرها درسته و جست‌وجوی
     *   «mail» مشتری‌های بی‌ربط رو برمی‌گردوند. این تست همین نشانه رو روی هر دو پایگاه می‌گیره.
     */
    public function test_user_search_does_not_match_on_a_column_users_do_not_have(): void
    {
        User::factory()->create(['name' => 'زهرا کریمی', 'phone' => '09121112233']);

        $response = $this->actingAs($this->admin)->getJson('/admin/search/api?q=mail');

        $response->assertOk();
        $this->assertEmpty($response->json('results.users'), 'هیچ کاربری «mail» در نام یا موبایل نداره');
    }

    public function test_specialist_search_never_returns_another_salons_specialists(): void
    {
        // searchSpecialists زنجیره‌ی where/orWhere بی‌گروه داره؛ scope سراسری سالن باید همچنان اعمال بشه
        $other = \App\Models\Salon::factory()->create(['slug' => 'other-search']);
        app(\App\Support\CurrentSalon::class)->set($other);
        \App\Models\Specialist::factory()->create(['name' => 'متخصص سالن دیگر', 'phone' => '09125556677', 'email' => 'other@example.com']);
        app(\App\Support\CurrentSalon::class)->clear();

        foreach (['متخصص سالن دیگر', '09125556677', 'other@example.com'] as $q) {
            $this->assertEmpty($this->actingAs($this->admin)->getJson('/admin/search/api?q='.urlencode($q))->assertOk()->json('results.specialists'), $q);
        }
    }

    public function test_non_admin_cannot_access_search(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/search?q=test')->assertStatus(403);
    }
}
