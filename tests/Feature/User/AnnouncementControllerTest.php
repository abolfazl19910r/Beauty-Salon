<?php

namespace Tests\Feature\User;

use App\Models\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * App\Http\Controllers\User\AnnouncementController (JSON API) is genuinely live —
 * resources/js/Components/Announcement/AnnouncementBanner.jsx really fetches
 * '/s/{slug}/announcements/active' (از ۲۰۲۶-۱۰-۱۱ مسیر وب، نه /api), unlike the sibling GalleryController JSON endpoints
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

        $response = $this->getJson(route('announcements.active'));

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

        $response = $this->getJson(route('announcements.active'));

        $response->assertOk();
        $response->assertJsonFragment(['id' => $announcement->id]);
    }

    public function test_active_orders_by_priority_then_recency(): void
    {
        $low = Announcement::factory()->create(['priority' => 1, 'published_at' => now()->subDays(2)]);
        $high = Announcement::factory()->create(['priority' => 10, 'published_at' => now()->subDay()]);

        $response = $this->getJson(route('announcements.active'));

        $ids = collect($response->json())->pluck('id')->all();
        $this->assertSame([$high->id, $low->id], $ids);
    }

    public function test_endpoints_are_publicly_accessible_without_authentication(): void
    {
        Announcement::factory()->create();

        $this->getJson(route('announcements.active'))->assertOk();
    }
}
