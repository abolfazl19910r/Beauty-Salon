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

    public function test_non_admin_cannot_access_search(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/search?q=test')->assertStatus(403);
    }
}
