<?php

namespace Tests\Feature\Admin;

use App\Models\Leave;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLeaveTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_index_lists_leaves_across_all_specialists(): void
    {
        $specialistA = Specialist::factory()->create();
        $specialistB = Specialist::factory()->create();
        Leave::factory()->create(['specialist_id' => $specialistA->id]);
        Leave::factory()->create(['specialist_id' => $specialistB->id]);

        $response = $this->actingAs($this->admin)->get('/admin/leaves');

        $response->assertOk();
        $this->assertCount(2, $response->viewData('leaves'));
    }

    public function test_index_filters_by_status(): void
    {
        Leave::factory()->create(['status' => 'pending']);
        Leave::factory()->create(['status' => 'approved']);

        $response = $this->actingAs($this->admin)->get('/admin/leaves?status=pending');

        $this->assertCount(1, $response->viewData('leaves'));
    }

    public function test_update_approves_a_leave_via_the_global_page(): void
    {
        $leave = Leave::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($this->admin)->put("/admin/leaves/{$leave->id}", [
            'status' => 'approved',
        ]);

        $response->assertRedirect(route('admin.leaves.index'));
        $this->assertSame('approved', $leave->fresh()->status);
    }

    public function test_pending_leaves_json_endpoint_returns_only_pending_ordered_by_start_date(): void
    {
        Leave::factory()->create(['status' => 'pending', 'start_date' => now()->addDays(10)]);
        Leave::factory()->create(['status' => 'pending', 'start_date' => now()->addDays(2)]);
        Leave::factory()->create(['status' => 'approved']);

        $response = $this->actingAs($this->admin)->getJson('/admin/leaves/pending');

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(2, $data);
        $this->assertTrue($data[0]['start_date'] < $data[1]['start_date']);
    }

    public function test_non_admin_cannot_access_the_global_leaves_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/leaves')->assertStatus(403);
    }
}
