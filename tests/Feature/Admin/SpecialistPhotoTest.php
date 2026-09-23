<?php

namespace Tests\Feature\Admin;

use App\Models\BeautyService;
use App\Models\Specialist;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * ⭐ عکس پروفایل متخصص (۲۰۲۶-۰۹-۲۴).
 */
class SpecialistPhotoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_uploads_a_photo_when_creating_a_specialist(): void
    {
        $service = BeautyService::factory()->create();
        $salonId = app(CurrentSalon::class)->id();

        $this->actingAs($this->admin)->post('/admin/specialists', [
            'name' => 'نگار احمدی',
            'phone' => '09121234567',
            'email' => 'negar@example.com',
            'services' => [$service->id],
            'photo' => UploadedFile::fake()->image('face.jpg', 300, 300),
        ])->assertRedirect(route('admin.specialists.index'));

        $specialist = Specialist::where('phone', '09121234567')->firstOrFail();
        $this->assertStringStartsWith("salons/{$salonId}/specialists/", $specialist->photo_path);
        Storage::disk('public')->assertExists($specialist->photo_path);

        $this->actingAs($this->admin)->get('/admin/specialists')->assertSee($specialist->photo_path, false);
    }

    public function test_admin_replaces_and_removes_a_photo(): void
    {
        $specialist = Specialist::factory()->create();
        $service = BeautyService::factory()->create();
        $base = ['name' => $specialist->name, 'phone' => $specialist->phone, 'email' => $specialist->email ?? 'sp@example.com', 'services' => [$service->id]];

        $this->actingAs($this->admin)->put("/admin/specialists/{$specialist->id}", $base + [
            'photo' => UploadedFile::fake()->image('a.png', 200, 200),
        ])->assertRedirect(route('admin.specialists.index'));
        $first = $specialist->fresh()->photo_path;
        Storage::disk('public')->assertExists($first);

        $this->actingAs($this->admin)->put("/admin/specialists/{$specialist->id}", $base + [
            'photo' => UploadedFile::fake()->image('b.png', 200, 200),
        ]);
        Storage::disk('public')->assertMissing($first);

        $second = $specialist->fresh()->photo_path;
        $this->actingAs($this->admin)->put("/admin/specialists/{$specialist->id}", $base + ['remove_photo' => '1']);
        $this->assertNull($specialist->fresh()->photo_path);
        Storage::disk('public')->assertMissing($second);
    }

    public function test_invalid_photo_is_rejected(): void
    {
        $specialist = Specialist::factory()->create();

        $this->actingAs($this->admin)->put("/admin/specialists/{$specialist->id}", [
            'name' => $specialist->name, 'phone' => $specialist->phone,
            'photo' => UploadedFile::fake()->create('x.svg', 5, 'image/svg+xml'),
        ])->assertSessionHasErrors('photo');
    }

    public function test_specialist_can_set_their_own_photo(): void
    {
        $user = User::factory()->create(['phone' => '09127776655']);
        $specialist = Specialist::factory()->create(['phone' => '09127776655', 'user_id' => $user->id]);

        $this->actingAs($user)->put(route('specialist.profile.update'), [
            'name' => $user->name,
            'phone' => $user->phone,
            'photo' => UploadedFile::fake()->image('me.webp', 200, 200),
        ])->assertRedirect(route('specialist.profile.show'))->assertSessionHasNoErrors();

        $this->assertNotNull($specialist->fresh()->photo_path);
        $this->assertSame('09127776655', $user->fresh()->phone);
    }

    public function test_home_page_shows_the_photo_and_no_longer_the_personal_phone(): void
    {
        $specialist = Specialist::factory()->create(['phone' => '09124443322', 'photo_path' => 'salons/1/specialists/p.jpg']);
        Cache::flush();

        $this->get('/s/'.app(CurrentSalon::class)->get()->slug)
            ->assertOk()
            ->assertSee('salons/1/specialists/p.jpg', false)
            ->assertDontSee('09124443322');
    }

    public function test_photo_change_clears_the_home_page_cache(): void
    {
        $salonId = app(CurrentSalon::class)->id();
        Cache::put("home_specialists:{$salonId}", collect(), 1800);
        $specialist = Specialist::factory()->create();

        app(\App\Services\Salon\SpecialistPhotoService::class)->replace($specialist, UploadedFile::fake()->image('c.png', 100, 100));

        $this->assertFalse(Cache::has("home_specialists:{$salonId}"));
    }

    public function test_booking_api_returns_the_photo_url(): void
    {
        $service = BeautyService::factory()->create();
        $specialist = Specialist::factory()->create(['photo_path' => 'salons/1/specialists/q.jpg']);
        $specialist->services()->attach($service->id);

        $customer = User::factory()->create();

        $this->actingAs($customer)->getJson(route('bookings.service-specialists', ['salon_slug' => app(CurrentSalon::class)->get()->slug, 'service' => $service->id]))
            ->assertOk()
            ->assertJsonFragment(['id' => $specialist->id])
            ->assertJsonPath('0.photo_url', fn ($url) => str_contains((string) $url, 'salons/1/specialists/q.jpg'));
    }
}
