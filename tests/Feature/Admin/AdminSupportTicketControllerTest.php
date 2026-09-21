<?php

namespace Tests\Feature\Admin;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSupportTicketControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_can_create_a_support_ticket(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/support-tickets', [
            'title' => 'مشکل در رزرو نوبت',
            'description' => 'هنگام ثبت نوبت خطا می‌گیرم.',
            'category' => 'booking',
            'priority' => 'high',
        ]);

        $ticket = SupportTicket::first();

        $response->assertRedirect(route('admin.support-tickets.show', $ticket));
        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $this->admin->id,
            'title' => 'مشکل در رزرو نوبت',
            'category' => 'booking',
            'priority' => 'high',
            'status' => 'open',
        ]);
    }

    public function test_admin_only_sees_their_own_tickets(): void
    {
        $otherAdmin = User::factory()->create(['is_admin' => true]);
        SupportTicket::factory()->create(['user_id' => $this->admin->id, 'title' => 'تیکت من']);
        SupportTicket::factory()->create(['user_id' => $otherAdmin->id, 'title' => 'تیکت دیگری']);

        $response = $this->actingAs($this->admin)->get('/admin/support-tickets');

        $response->assertOk();
        $response->assertSee('تیکت من');
        $response->assertDontSee('تیکت دیگری');
    }

    public function test_admin_cannot_view_another_admins_ticket(): void
    {
        $otherAdmin = User::factory()->create(['is_admin' => true]);
        $ticket = SupportTicket::factory()->create(['user_id' => $otherAdmin->id]);

        $response = $this->actingAs($this->admin)->get(route('admin.support-tickets.show', $ticket));

        $response->assertForbidden();
    }

    public function test_admin_can_reply_to_their_own_ticket(): void
    {
        $ticket = SupportTicket::factory()->create(['user_id' => $this->admin->id]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.support-tickets.reply', $ticket), [
                'message' => 'اطلاعات بیشتر: مرورگر کروم استفاده می‌کنم.',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('support_ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $this->admin->id,
            'message' => 'اطلاعات بیشتر: مرورگر کروم استفاده می‌کنم.',
            'is_staff_reply' => false,
        ]);
    }

    public function test_replying_to_a_resolved_ticket_reopens_it(): void
    {
        $ticket = SupportTicket::factory()->resolved()->create(['user_id' => $this->admin->id]);

        $this->actingAs($this->admin)->post(route('admin.support-tickets.reply', $ticket), [
            'message' => 'مشکل دوباره پیش اومد.',
        ]);

        $this->assertEquals('open', $ticket->fresh()->status);
    }

    public function test_ticket_creation_requires_a_title(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/support-tickets', [
            'description' => 'توضیحات',
            'category' => 'other',
        ]);

        $response->assertSessionHasErrors('title');
    }
}
