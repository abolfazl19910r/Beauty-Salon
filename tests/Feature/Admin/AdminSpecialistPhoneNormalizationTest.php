<?php

namespace Tests\Feature\Admin;

use App\Models\BeautyService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSpecialistPhoneNormalizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    private function baseData(array $overrides = []): array
    {
        $service = BeautyService::factory()->create();

        return array_merge([
            'name' => 'متخصص تست',
            'email' => 'specialist-'.uniqid().'@example.com',
            'services' => [$service->id],
        ], $overrides);
    }

    public function test_a_plain_11_digit_phone_number_is_accepted_as_is(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/specialists', $this->baseData([
            'phone' => '09121234567',
        ]));

        $response->assertRedirect(route('admin.specialists.index'));
        $this->assertDatabaseHas('specialists', ['phone' => '09121234567']);
    }

    public function test_an_international_format_phone_number_is_normalized_before_validation(): void
    {
        // Regression guard for the documented bug: normalization must happen in
        // prepareForValidation() (before the max:11 rule runs), not after — otherwise every
        // +98/0098-prefixed number (naturally longer than 11 chars) is rejected before it ever
        // reaches the normalization logic.
        $response = $this->actingAs($this->admin)->post('/admin/specialists', $this->baseData([
            'phone' => '+98 912 123 4567',
        ]));

        $response->assertRedirect(route('admin.specialists.index'));
        $this->assertDatabaseHas('specialists', ['phone' => '09121234567']);
    }

    public function test_a_0098_prefixed_phone_number_is_normalized(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/specialists', $this->baseData([
            'phone' => '00989121234567',
        ]));

        $response->assertRedirect(route('admin.specialists.index'));
        $this->assertDatabaseHas('specialists', ['phone' => '09121234567']);
    }

    public function test_specialist_is_linked_to_an_existing_user_by_normalized_phone(): void
    {
        $existingUser = User::factory()->create(['phone' => '09121234567']);

        $this->actingAs($this->admin)->post('/admin/specialists', $this->baseData([
            'phone' => '+98 912 123 4567',
        ]));

        $this->assertDatabaseHas('specialists', [
            'phone' => '09121234567',
            'user_id' => $existingUser->id,
        ]);
    }

    public function test_specialist_created_before_the_matching_user_registers_has_a_null_user_id(): void
    {
        // Documented behavior: creating a specialist ahead of the person's own registration must
        // not fail the NOT NULL constraint (fixed via the nullable user_id migration) — user_id
        // should link automatically later when the real user registers with a matching phone.
        $response = $this->actingAs($this->admin)->post('/admin/specialists', $this->baseData([
            'phone' => '09121234567',
        ]));

        $response->assertRedirect(route('admin.specialists.index'));
        $this->assertDatabaseHas('specialists', ['phone' => '09121234567', 'user_id' => null]);
    }
}
