<?php

namespace Tests\Feature\User;

use App\Models\BeautyService;
use App\Models\Category;
use App\Models\User;
use App\Support\SalonNavCategories;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بسته‌ی «ح»، ۱۱ (۲۰۲۶-۱۰-۱۹): دسته‌بندی خدمات در هدر سایت سالن و ردیف یک‌خطی صفحه‌ی خدمات.
 */
class ServiceCategoriesMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_categories_with_services_are_offered_in_the_salon_order(): void
    {
        $hair = Category::factory()->create(['name' => 'رنگ مو', 'order' => 2, 'is_active' => true]);
        $nail = Category::factory()->create(['name' => 'ناخن', 'order' => 1, 'is_active' => true]);
        Category::factory()->create(['name' => 'دسته‌ی خالی', 'is_active' => true]);
        $hidden = Category::factory()->create(['name' => 'دسته‌ی غیرفعال', 'is_active' => false]);
        BeautyService::factory()->count(2)->create(['category_id' => $hair->id]);
        BeautyService::factory()->create(['category_id' => $nail->id]);
        BeautyService::factory()->create(['category_id' => $hidden->id]);

        $categories = SalonNavCategories::get();

        $this->assertSame(['ناخن', 'رنگ مو'], $categories->pluck('name')->all());
        $this->assertSame(2, (int) $categories->firstWhere('name', 'رنگ مو')->services_count);
    }

    public function test_header_menu_and_services_page_link_each_category(): void
    {
        $hair = Category::factory()->create(['name' => 'رنگ مو', 'is_active' => true]);
        BeautyService::factory()->create(['category_id' => $hair->id]);
        Category::factory()->create(['name' => 'دسته‌ی خالی', 'is_active' => true]);
        $customer = User::factory()->create(['user_type' => 'customer']);

        $this->actingAs($customer)->get(route('services.index', ['category' => $hair->id]))->assertOk()
            ->assertSee('data-nav-categories-panel>', false)
            ->assertSee('data-category-row', false)
            ->assertSee(route('services.index', ['category' => $hair->id]), false)
            ->assertDontSee('دسته‌ی خالی');

        $this->get(route('home'))->assertOk()->assertSee('data-nav-categories-btn>', false);
    }

    public function test_without_categories_the_header_keeps_a_plain_services_link(): void
    {
        $this->get(route('home'))->assertOk()->assertDontSee('data-nav-categories-btn>', false)->assertSee(route('services.index'), false);
    }

    public function test_pagination_is_persian(): void
    {
        // resources/lang هست، پس لاراول lang/ را نمی‌خواند: «pagination.next» خام و «Showing … results» انگلیسی بود
        BeautyService::factory()->count(15)->create();

        $this->get(route('services.index'))->assertOk()
            ->assertSee('بعدی')->assertSee('نمایش')
            ->assertDontSee('pagination.next')->assertDontSee('Showing');
    }
}
