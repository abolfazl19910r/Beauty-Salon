<?php

namespace Tests\Feature\Admin;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_can_create_an_announcement(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/announcements', [
            'title' => 'اطلاعیه‌ی مهم',
            'content' => 'متن اطلاعیه',
            'type' => 'general',
            'priority' => 5,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.announcements.index'));
        $this->assertDatabaseHas('announcements', [
            'title' => 'اطلاعیه‌ی مهم',
            'is_active' => 1,
        ]);
    }

    public function test_unchecked_is_active_checkbox_stores_false_not_true(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/announcements', [
            'title' => 'اطلاعیه غیرفعال',
            'content' => 'متن',
            'type' => 'general',
            'priority' => 1,
            // is_active intentionally omitted
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('announcements', ['title' => 'اطلاعیه غیرفعال', 'is_active' => 0]);
    }

    public function test_expiry_before_publish_date_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)
            ->from('/admin/announcements/create')
            ->post('/admin/announcements', [
                'title' => 'اطلاعیه',
                'content' => 'متن',
                'type' => 'general',
                'priority' => 1,
                'published_at' => '2026-08-15 10:00:00',
                'expires_at' => '2026-08-10 10:00:00',
            ]);

        $response->assertSessionHasErrors('expires_at');
        $this->assertDatabaseCount('announcements', 0);
    }

    public function test_invalid_type_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)
            ->from('/admin/announcements/create')
            ->post('/admin/announcements', [
                'title' => 'اطلاعیه',
                'content' => 'متن',
                'type' => 'not-a-real-type',
                'priority' => 1,
            ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_admin_can_update_and_delete_an_announcement(): void
    {
        $announcement = Announcement::factory()->create(['title' => 'قبل']);

        $this->actingAs($this->admin)->put("/admin/announcements/{$announcement->id}", [
            'title' => 'بعد',
            'content' => $announcement->content,
            'type' => $announcement->type,
            'priority' => $announcement->priority,
            'is_active' => '1',
        ]);
        $this->assertDatabaseHas('announcements', ['id' => $announcement->id, 'title' => 'بعد']);

        $this->actingAs($this->admin)->delete("/admin/announcements/{$announcement->id}");
        $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
    }

    public function test_index_counts_active_pending_and_expired_correctly(): void
    {
        Announcement::factory()->create([
            'is_active' => true, 'published_at' => now()->subDay(), 'expires_at' => now()->addDay(),
        ]);
        Announcement::factory()->create([
            'is_active' => true, 'published_at' => now()->addDay(), 'expires_at' => null,
        ]);
        Announcement::factory()->create([
            'is_active' => true, 'published_at' => now()->subWeek(), 'expires_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/announcements');

        $response->assertOk();
        $response->assertViewHas('activeAnnouncements', 1);
        $response->assertViewHas('pendingAnnouncements', 1);
        $response->assertViewHas('expiredAnnouncements', 1);
        $response->assertViewHas('totalAnnouncements', 3);
    }

    public function test_non_admin_cannot_access_announcements(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/announcements')->assertStatus(403);
    }
}
