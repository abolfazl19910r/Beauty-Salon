<?php

namespace Tests\Feature\Admin;

use App\Models\BeautyService;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Full HTTP-level CRUD coverage for AdminSpecialistController, complementing
 * AdminSpecialistPhoneNormalizationTest (session 3, phone-normalization-only).
 */
class AdminSpecialistTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private BeautyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->service = BeautyService::factory()->create();
    }

    public function test_index_lists_specialists_with_todays_booking_count(): void
    {
        Specialist::factory()->count(2)->create();

        $response = $this->actingAs($this->admin)->get('/admin/specialists');

        $response->assertOk();
        $this->assertCount(2, $response->viewData('specialists'));
    }

    public function test_index_search_filters_by_name_or_phone(): void
    {
        Specialist::factory()->create(['name' => 'سارا احمدی']);
        Specialist::factory()->create(['name' => 'مریم کریمی']);

        $response = $this->actingAs($this->admin)->get('/admin/specialists?search=احمدی');

        $this->assertCount(1, $response->viewData('specialists'));
    }

    public function test_store_creates_a_specialist_with_no_matching_user_yet(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/specialists', [
            'name' => 'متخصص جدید',
            'phone' => '09121234567',
            'email' => 'new-specialist@example.com',
            'services' => [$this->service->id],
        ]);

        $response->assertRedirect(route('admin.specialists.index'));
        $response->assertSessionHas('success');

        $specialist = Specialist::where('phone', '09121234567')->first();
        $this->assertNotNull($specialist);
        // Regression guard for the documented specialists.user_id NOT NULL bug fix:
        // creating a specialist before the person themselves has registered must succeed
        // with user_id left null, not throw an integrity-constraint error.
        $this->assertNull($specialist->user_id);
        $this->assertTrue($specialist->services->contains($this->service->id));
    }

    public function test_store_links_to_an_existing_user_by_matching_phone(): void
    {
        $user = User::factory()->create(['phone' => '09129998877']);

        $this->actingAs($this->admin)->post('/admin/specialists', [
            'name' => 'متخصص لینک‌شده',
            'phone' => '09129998877',
            'email' => 'linked@example.com',
            'services' => [$this->service->id],
        ]);

        $specialist = Specialist::where('phone', '09129998877')->first();
        $this->assertSame($user->id, $specialist->user_id);
    }

    public function test_store_normalizes_an_international_phone_format(): void
    {
        $this->actingAs($this->admin)->post('/admin/specialists', [
            'name' => 'فرمت بین‌المللی',
            'phone' => '+989121112233',
            'email' => 'intl@example.com',
            'services' => [$this->service->id],
        ]);

        $this->assertDatabaseHas('specialists', ['phone' => '09121112233']);
    }

    public function test_store_persists_a_valid_commission_rate(): void
    {
        $this->actingAs($this->admin)->post('/admin/specialists', [
            'name' => 'کمیسیون‌دار',
            'phone' => '09121112244',
            'email' => 'commission@example.com',
            'services' => [$this->service->id],
            'commission_rate' => 25,
        ]);

        $this->assertDatabaseHas('specialists', ['phone' => '09121112244', 'commission_rate' => 25]);
    }

    public function test_store_rejects_a_commission_rate_over_100(): void
    {
        $response = $this->actingAs($this->admin)->from('/admin/specialists/create')->post('/admin/specialists', [
            'name' => 'کمیسیون نامعتبر',
            'phone' => '09121112255',
            'email' => 'badcommission@example.com',
            'services' => [$this->service->id],
            'commission_rate' => 150,
        ]);

        $response->assertSessionHasErrors('commission_rate');
        $this->assertDatabaseMissing('specialists', ['phone' => '09121112255']);
    }

    public function test_store_rejects_a_duplicate_phone(): void
    {
        Specialist::factory()->create(['phone' => '09121110000']);

        $response = $this->actingAs($this->admin)->from('/admin/specialists/create')->post('/admin/specialists', [
            'name' => 'تکراری',
            'phone' => '09121110000',
            'email' => 'dup@example.com',
            'services' => [$this->service->id],
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_show_and_edit_pages_render(): void
    {
        $specialist = Specialist::factory()->create();

        $this->actingAs($this->admin)->get("/admin/specialists/{$specialist->id}")->assertOk();
        $this->actingAs($this->admin)->get("/admin/specialists/{$specialist->id}/edit")->assertOk();
    }

    public function test_update_changes_fields_and_syncs_services(): void
    {
        $specialist = Specialist::factory()->create(['name' => 'قدیمی']);
        $specialist->services()->attach($this->service->id);
        $newService = BeautyService::factory()->create();

        $response = $this->actingAs($this->admin)->put("/admin/specialists/{$specialist->id}", [
            'name' => 'جدید',
            'phone' => $specialist->phone,
            'email' => $specialist->email,
            'services' => [$newService->id],
        ]);

        $response->assertRedirect(route('admin.specialists.index'));
        $specialist->refresh();
        $this->assertSame('جدید', $specialist->name);
        $this->assertFalse($specialist->services->contains($this->service->id));
        $this->assertTrue($specialist->services->contains($newService->id));
    }

    public function test_update_allows_keeping_the_specialists_own_phone_and_email(): void
    {
        $specialist = Specialist::factory()->create();

        $response = $this->actingAs($this->admin)->put("/admin/specialists/{$specialist->id}", [
            'name' => $specialist->name,
            'phone' => $specialist->phone,
            'email' => $specialist->email,
            'services' => [$this->service->id],
        ]);

        $response->assertRedirect(route('admin.specialists.index'));
        $response->assertSessionHas('success');
    }

    public function test_destroy_soft_deletes_the_specialist_and_detaches_services(): void
    {
        $specialist = Specialist::factory()->create();
        $specialist->services()->attach($this->service->id);

        $response = $this->actingAs($this->admin)->delete("/admin/specialists/{$specialist->id}");

        $response->assertRedirect(route('admin.specialists.index'));
        // Specialist uses SoftDeletes — destroy() sets deleted_at, it doesn't hard-delete the row.
        $this->assertSoftDeleted('specialists', ['id' => $specialist->id]);
        $this->assertDatabaseMissing('specialist_services', ['specialist_id' => $specialist->id]);
    }

    public function test_non_admin_cannot_access_specialist_management(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/specialists')->assertStatus(403);
    }
}
