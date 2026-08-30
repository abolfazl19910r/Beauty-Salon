<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ Fix (fix/admin-booking-slot-conflict, commit 3): covers AdminBookingCustomerController,
 * which replaced the old User::all() dropdown on admin/bookings/create.blade.php with a
 * search/quick-create widget.
 */
class AdminBookingCustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_search_finds_a_customer_by_partial_phone(): void
    {
        $customer = User::factory()->create(['name' => 'زهرا احمدی', 'phone' => '09121234567']);
        User::factory()->create(['phone' => '09359999999']);

        $response = $this->actingAs($this->admin)->getJson('/admin/bookings/customers/search?phone=091212');

        $response->assertOk();
        $response->assertJsonCount(1, 'customers');
        $response->assertJsonFragment(['id' => $customer->id, 'phone' => $customer->phone]);
    }

    public function test_search_finds_a_customer_by_partial_name(): void
    {
        $customer = User::factory()->create(['name' => 'زهرا احمدی']);
        User::factory()->create(['name' => 'سارا محمدی']);

        $response = $this->actingAs($this->admin)->getJson('/admin/bookings/customers/search?phone=احمدی');

        $response->assertOk();
        $response->assertJsonCount(1, 'customers');
        $response->assertJsonFragment(['id' => $customer->id]);
    }

    public function test_search_returns_empty_for_a_query_under_three_characters(): void
    {
        User::factory()->create(['phone' => '09121234567']);

        $response = $this->actingAs($this->admin)->getJson('/admin/bookings/customers/search?phone=09');

        $response->assertOk();
        $response->assertJsonCount(0, 'customers');
    }

    public function test_search_caps_results_at_five(): void
    {
        User::factory()->count(8)->create(['phone' => fn () => '0912'.fake()->numerify('#######')]);

        $response = $this->actingAs($this->admin)->getJson('/admin/bookings/customers/search?phone=0912');

        $response->assertOk();
        $response->assertJsonCount(5, 'customers');
    }

    public function test_quick_create_makes_a_new_customer(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/admin/bookings/customers/quick-create', [
            'name' => 'مشتری تلفنی',
            'phone' => '09123334455',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('customer.name', 'مشتری تلفنی');
        $this->assertDatabaseHas('users', ['phone' => '09123334455', 'name' => 'مشتری تلفنی']);
    }

    public function test_quick_create_rejects_a_duplicate_phone(): void
    {
        User::factory()->create(['phone' => '09123334455']);

        $response = $this->actingAs($this->admin)->postJson('/admin/bookings/customers/quick-create', [
            'name' => 'یک نفر دیگر',
            'phone' => '09123334455',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('phone');
    }

    public function test_quick_create_rejects_a_malformed_phone(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/admin/bookings/customers/quick-create', [
            'name' => 'مشتری تلفنی',
            'phone' => '123456',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('phone');
    }

    public function test_non_admin_cannot_search_or_quick_create(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->getJson('/admin/bookings/customers/search?phone=091')->assertForbidden();
        $this->actingAs($user)->postJson('/admin/bookings/customers/quick-create', [
            'name' => 'X', 'phone' => '09123334455',
        ])->assertForbidden();
    }
}
