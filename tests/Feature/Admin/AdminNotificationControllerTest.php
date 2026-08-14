<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_index_paginates_the_admins_own_notifications(): void
    {
        UserNotification::factory()->count(3)->create(['user_id' => $this->admin->id, 'notifiable_id' => $this->admin->id]);
        UserNotification::factory()->count(2)->create(); // belongs to a different user

        $response = $this->actingAs($this->admin)->get('/admin/notifications');

        $response->assertOk();
        $this->assertCount(3, $response->viewData('notifications'));
    }

    public function test_show_marks_an_unread_notification_as_read(): void
    {
        $notification = UserNotification::factory()->create([
            'user_id' => $this->admin->id,
            'notifiable_id' => $this->admin->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/notifications/{$notification->id}");

        $response->assertOk();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_as_read_marks_and_returns_success(): void
    {
        $notification = UserNotification::factory()->create([
            'user_id' => $this->admin->id,
            'notifiable_id' => $this->admin->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/notifications/{$notification->id}/read");

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_as_read_on_a_nonexistent_notification_still_returns_success_with_a_message(): void
    {
        // The 'id' route parameter is globally constrained to a hex/uuid-shaped pattern
        // (RouteServiceProvider::Route::pattern('id', '[0-9a-f-]+')), so a well-formed-but-unknown
        // uuid is required here to actually reach the controller rather than 404 at the router.
        $response = $this->actingAs($this->admin)->post('/admin/notifications/'.\Illuminate\Support\Str::uuid().'/read');

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_toggle_read_flips_between_read_and_unread(): void
    {
        $notification = UserNotification::factory()->create([
            'user_id' => $this->admin->id,
            'notifiable_id' => $this->admin->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/notifications/{$notification->id}/toggle");
        $response->assertJson(['status' => 'read']);
        $this->assertNotNull($notification->fresh()->read_at);

        $response = $this->actingAs($this->admin)->post("/admin/notifications/{$notification->id}/toggle");
        $response->assertJson(['status' => 'unread']);
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_delete_removes_a_single_notification(): void
    {
        $notification = UserNotification::factory()->create(['user_id' => $this->admin->id, 'notifiable_id' => $this->admin->id]);

        $response = $this->actingAs($this->admin)->delete("/admin/notifications/{$notification->id}/delete");

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('user_notifications', ['id' => $notification->id]);
    }

    public function test_delete_returns_an_error_for_a_notification_belonging_to_another_user(): void
    {
        $other = User::factory()->create();
        $notification = UserNotification::factory()->create(['user_id' => $other->id, 'notifiable_id' => $other->id]);

        $response = $this->actingAs($this->admin)->delete("/admin/notifications/{$notification->id}/delete");

        $response->assertStatus(404);
        $this->assertDatabaseHas('user_notifications', ['id' => $notification->id]);
    }

    public function test_mark_all_as_read_marks_every_unread_notification(): void
    {
        UserNotification::factory()->count(3)->create(['user_id' => $this->admin->id, 'notifiable_id' => $this->admin->id, 'read_at' => null]);

        $response = $this->actingAs($this->admin)->post('/admin/notifications/read-all');

        $response->assertRedirect(route('admin.notifications.index'));
        $this->assertSame(0, UserNotification::where('user_id', $this->admin->id)->whereNull('read_at')->count());
    }

    public function test_delete_all_removes_every_notification_for_the_admin_only(): void
    {
        UserNotification::factory()->count(2)->create(['user_id' => $this->admin->id, 'notifiable_id' => $this->admin->id]);
        $otherAdmin = User::factory()->create(['is_admin' => true]);
        UserNotification::factory()->create(['user_id' => $otherAdmin->id, 'notifiable_id' => $otherAdmin->id]);

        $response = $this->actingAs($this->admin)->delete('/admin/notifications/delete-all');

        $response->assertRedirect(route('admin.notifications.index'));
        $this->assertSame(0, UserNotification::where('user_id', $this->admin->id)->count());
        $this->assertSame(1, UserNotification::where('user_id', $otherAdmin->id)->count());
    }

    public function test_unread_count_returns_the_correct_number(): void
    {
        UserNotification::factory()->count(2)->create(['user_id' => $this->admin->id, 'notifiable_id' => $this->admin->id, 'read_at' => null]);
        UserNotification::factory()->create(['user_id' => $this->admin->id, 'notifiable_id' => $this->admin->id, 'read_at' => now()]);

        $response = $this->actingAs($this->admin)->get('/admin/notifications/count');

        $response->assertOk();
        $response->assertJson(['count' => 2]);
    }

    public function test_latest_only_returns_notifications_with_a_message_key(): void
    {
        UserNotification::factory()->create([
            'user_id' => $this->admin->id,
            'notifiable_id' => $this->admin->id,
            'data' => ['message' => 'یک اعلان معتبر'],
            'read_at' => null,
        ]);
        UserNotification::factory()->create([
            'user_id' => $this->admin->id,
            'notifiable_id' => $this->admin->id,
            'data' => ['no_message_key' => true],
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/notifications/latest');

        $response->assertOk();
        $this->assertCount(1, $response->json('notifications'));
    }

    public function test_non_admin_cannot_access_admin_notifications(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/notifications')->assertStatus(403);
    }
}
