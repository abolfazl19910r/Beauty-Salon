<?php

namespace Tests\Feature\User;

use App\Models\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * App\Http\Controllers\User\AnnouncementController (JSON API) is genuinely live —
 * resources/js/Components/Announcement/AnnouncementBanner.jsx really fetches
 * '/api/announcements/active', unlike the sibling GalleryController JSON endpoints
 * (index/store/update/destroy/reorder) which have zero consumers anywhere in
 * resources/views or resources/js and are pure orphaned dead code superseded by
 * Admin\Gallery\AdminGalleryController's Blade panel — see R-AdminAnnouncement-Gallery
 * in Rasta_unified_prompt.md. This file only covers the live controller.
 */
class AnnouncementControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_returns_only_currently_active_published_unexpired_announcements(): void
    {
        $active = Announcement::factory()->create([
            'is_active' => true,
            'published_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);
        Announcement::factory()->create(['is_active' => false]);
        Announcement::factory()->create(['published_at' => now()->addDay()]);
        Announcement::factory()->create(['expires_at' => now()->subDay()]);

        $response = $this->getJson('/api/announcements/active');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['id' => $active->id]);
    }

    public function test_active_treats_a_null_expiry_as_never_expiring(): void
    {
        $announcement = Announcement::factory()->create([
            'is_active' => true,
            'published_at' => now()->subDay(),
            'expires_at' => null,
        ]);

        $response = $this->getJson('/api/announcements/active');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $announcement->id]);
    }

    public function test_active_orders_by_priority_then_recency(): void
    {
        $low = Announcement::factory()->create(['priority' => 1, 'published_at' => now()->subDays(2)]);
        $high = Announcement::factory()->create(['priority' => 10, 'published_at' => now()->subDay()]);

        $response = $this->getJson('/api/announcements/active');

        $ids = collect($response->json())->pluck('id')->all();
        $this->assertSame([$high->id, $low->id], $ids);
    }

    public function test_top_returns_a_single_highest_priority_announcement(): void
    {
        Announcement::factory()->create(['priority' => 3]);
        $top = Announcement::factory()->create(['priority' => 9]);

        $response = $this->getJson('/api/announcements/top');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $top->id]);
    }

    public function test_top_returns_an_empty_object_when_nothing_is_active(): void
    {
        // Symfony's JsonResponse normalizes a null payload to an empty ArrayObject
        // (`$data ??= new \ArrayObject()` in JsonResponse::__construct), so
        // response()->json(null) always serializes as `{}`, not the JSON literal `null`.
        // This is standard framework behavior, not project-specific — documented here
        // because a naive frontend truthiness check (`if (announcement)`) would treat
        // `{}` as present. In practice this endpoint has no current consumer.
        Announcement::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/announcements/top');

        $response->assertOk();
        $this->assertSame([], $response->json());
    }

    public function test_index_paginates_active_announcements(): void
    {
        Announcement::factory()->count(3)->create();

        $response = $this->getJson('/api/announcements');

        $response->assertOk();
        $response->assertJsonStructure(['data', 'current_page', 'total']);
    }

    public function test_show_returns_a_single_active_announcement(): void
    {
        $announcement = Announcement::factory()->create();

        $response = $this->getJson("/api/announcements/{$announcement->id}");

        $response->assertOk();
        $response->assertJsonFragment(['id' => $announcement->id]);
    }

    public function test_show_404s_for_an_inactive_announcement(): void
    {
        $announcement = Announcement::factory()->create(['is_active' => false]);

        $response = $this->getJson("/api/announcements/{$announcement->id}");

        $response->assertNotFound();
    }

    public function test_endpoints_are_publicly_accessible_without_authentication(): void
    {
        Announcement::factory()->create();

        $this->getJson('/api/announcements/active')->assertOk();
        $this->getJson('/api/announcements/top')->assertOk();
    }
}
