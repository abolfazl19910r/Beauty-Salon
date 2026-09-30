<?php

namespace Tests\Feature\Routing;

use App\Models\Announcement;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * سالن جاری باید پیش از route model binding ست شود تا scope سراسری BelongsToSalon خودِ binding را محدود کند.
 * این تست route موقتی می‌سازد که عمداً هیچ چک مالکیتی ندارد: اگر ترتیب middleware درست باشد، رکورد سالن دیگر
 * اصلاً پیدا نمی‌شود (۴۰۴) — یعنی اکشنی که روزی چک مالکیت را فراموش کند، باز هم نشت نمی‌دهد.
 */
class SalonScopedRouteBindingTest extends TestCase
{
    use RefreshDatabase;

    private Salon $otherSalon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->otherSalon = Salon::factory()->create(['slug' => 'binding-order-other']);

        Route::middleware(['web', 'auth', 'permission:access_admin_panel', 'salon.active'])
            ->get('/admin/__binding-probe/{announcement}', fn (Announcement $announcement) => 'seen:'.$announcement->id);

        Route::middleware(['web', 'auth', 'verified', 'salon.specialist'])
            ->get('/specialist/__binding-probe/{announcement}', fn (Announcement $announcement) => 'seen:'.$announcement->id);
    }

    private function announcementIn(?Salon $salon): Announcement
    {
        $current = app(CurrentSalon::class);
        $previous = $current->get();
        $salon ? $current->set($salon) : null;
        $announcement = Announcement::factory()->create();
        $previous ? $current->set($previous) : $current->clear();

        return $announcement;
    }

    public function test_an_admin_route_without_an_ownership_check_cannot_bind_another_salons_record(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $foreign = $this->announcementIn($this->otherSalon);
        app(CurrentSalon::class)->clear(); // درخواست واقعی بدون سالن شروع می‌شود

        $this->actingAs($admin)->get("/admin/__binding-probe/{$foreign->id}")->assertNotFound();
    }

    public function test_an_admin_route_still_binds_its_own_salons_record(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $own = $this->announcementIn(null);
        app(CurrentSalon::class)->clear();

        $this->actingAs($admin)->get("/admin/__binding-probe/{$own->id}")->assertOk()->assertSee('seen:'.$own->id);
    }

    public function test_a_specialist_route_without_an_ownership_check_cannot_bind_another_salons_record(): void
    {
        $user = User::factory()->create(['phone' => '09121234567', 'phone_verified_at' => now()]);
        Specialist::factory()->create(['phone' => '09121234567', 'user_id' => $user->id]);
        $foreign = $this->announcementIn($this->otherSalon);
        app(CurrentSalon::class)->clear();

        $this->actingAs($user)->get("/specialist/__binding-probe/{$foreign->id}")->assertNotFound();
    }
}
