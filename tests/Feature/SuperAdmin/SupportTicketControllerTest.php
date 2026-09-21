<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Role;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsSuperAdmin(): User
    {
        $superAdmin = User::factory()->create(['is_admin' => true]);
        $role = Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']);
        $superAdmin->assignRole($role);
        $this->actingAs($superAdmin);

        return $superAdmin;
    }

    public function test_super_admin_sees_tickets_from_every_user(): void
    {
        $superAdmin = $this->actingAsSuperAdmin();
        $admin1 = User::factory()->create(['is_admin' => true]);
        $admin2 = User::factory()->create(['is_admin' => true]);
        SupportTicket::factory()->create(['user_id' => $admin1->id, 'title' => 'تیکت سالن اول']);
        SupportTicket::factory()->create(['user_id' => $admin2->id, 'title' => 'تیکت سالن دوم']);

        $response = $this->get(route('superadmin.support-tickets.index'));

        $response->assertOk();
        $response->assertSee('تیکت سالن اول');
        $response->assertSee('تیکت سالن دوم');
    }

    public function test_a_regular_admin_cannot_access_the_superadmin_inbox(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('superadmin.support-tickets.index'));

        $response->assertForbidden();
    }

    public function test_status_filter_only_returns_matching_tickets(): void
    {
        $this->actingAsSuperAdmin();
        $user = User::factory()->create(['is_admin' => true]);
        SupportTicket::factory()->create(['user_id' => $user->id, 'status' => 'open', 'title' => 'باز است']);
        SupportTicket::factory()->closed()->create(['user_id' => $user->id, 'title' => 'بسته است']);

        $response = $this->get(route('superadmin.support-tickets.index', ['status' => 'open']));

        $response->assertOk();
        $response->assertSee('باز است');
        $response->assertDontSee('بسته است');
    }

    public function test_super_admin_can_reply_and_ticket_moves_to_in_progress(): void
    {
        $superAdmin = $this->actingAsSuperAdmin();
        $user = User::factory()->create(['is_admin' => true]);
        $ticket = SupportTicket::factory()->create(['user_id' => $user->id, 'status' => 'open']);

        $response = $this->post(route('superadmin.support-tickets.reply', $ticket), [
            'message' => 'در حال بررسی هستیم.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('support_ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $superAdmin->id,
            'is_staff_reply' => true,
        ]);
        $this->assertEquals('in_progress', $ticket->fresh()->status);
    }

    public function test_super_admin_can_assign_a_ticket_to_themselves(): void
    {
        $superAdmin = $this->actingAsSuperAdmin();
        $user = User::factory()->create(['is_admin' => true]);
        $ticket = SupportTicket::factory()->create(['user_id' => $user->id]);

        $this->put(route('superadmin.support-tickets.update', $ticket), [
            'priority' => 'high',
            'assigned_to' => $superAdmin->id,
        ]);

        $ticket->refresh();
        $this->assertEquals($superAdmin->id, $ticket->assigned_to);
        $this->assertEquals('high', $ticket->priority);
    }

    public function test_super_admin_can_resolve_close_and_reopen_a_ticket(): void
    {
        $this->actingAsSuperAdmin();
        $user = User::factory()->create(['is_admin' => true]);
        $ticket = SupportTicket::factory()->create(['user_id' => $user->id]);

        $this->post(route('superadmin.support-tickets.resolve', $ticket));
        $this->assertEquals('resolved', $ticket->fresh()->status);

        $this->post(route('superadmin.support-tickets.close', $ticket));
        $this->assertEquals('closed', $ticket->fresh()->status);

        $this->post(route('superadmin.support-tickets.reopen', $ticket));
        $this->assertEquals('open', $ticket->fresh()->status);
    }
}
