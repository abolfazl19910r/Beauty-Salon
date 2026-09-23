<?php

namespace Tests\Feature\SalonSignup;

use App\Models\Role;
use App\Models\Salon;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\SalonContactPayload;
use Tests\TestCase;

/**
 * ⭐ لوگوی اختصاصی هر سالن (۲۰۲۶-۰۹-۲۴).
 */
class SalonLogoTest extends TestCase
{
    use RefreshDatabase;
    use SalonContactPayload;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function signup(array $overrides = [])
    {
        return $this->post(route('salon-signup.store'), array_merge([
            'name' => 'سالن رز',
            'slug' => 'rose-salon',
            'owner_name' => 'شیما کریمی',
            'owner_phone' => '09128889900',
            'owner_password' => 'Str0ng!Passw0rd',
            'owner_password_confirmation' => 'Str0ng!Passw0rd',
        ], $this->salonContactPayload(), $overrides));
    }

    private function superAdmin(): User
    {
        $role = Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']);
        $user = User::factory()->create(['is_admin' => true]);
        $user->roles()->attach($role);

        return $user;
    }

    public function test_signup_stores_the_logo_in_the_salons_own_folder(): void
    {
        $this->signup(['logo' => UploadedFile::fake()->image('logo.png', 256, 256)])
            ->assertRedirect(route('salon-signup.verify'));

        $salon = Salon::where('slug', 'rose-salon')->firstOrFail();
        $this->assertNotNull($salon->logo_path);
        $this->assertStringStartsWith("salons/{$salon->id}/branding/", $salon->logo_path);
        Storage::disk('public')->assertExists($salon->logo_path);
    }

    public function test_logo_is_optional(): void
    {
        $this->signup()->assertRedirect(route('salon-signup.verify'));

        $this->assertNull(Salon::where('slug', 'rose-salon')->firstOrFail()->logo_path);
    }

    public function test_svg_and_non_images_and_oversized_files_are_rejected(): void
    {
        $this->signup(['logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml')])->assertSessionHasErrors('logo');
        $this->signup(['logo' => UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf')])->assertSessionHasErrors('logo');
        $this->signup(['logo' => UploadedFile::fake()->image('big.png', 800, 800)->size(3000)])->assertSessionHasErrors('logo');
        $this->signup(['logo' => UploadedFile::fake()->image('tiny.png', 20, 20)])->assertSessionHasErrors('logo');

        $this->assertDatabaseMissing('salons', ['slug' => 'rose-salon']);
    }

    public function test_customer_site_shows_only_its_own_salons_logo(): void
    {
        $current = app(CurrentSalon::class)->get();
        $current->update(['logo_path' => "salons/{$current->id}/branding/mine.png"]);
        Salon::factory()->create(['slug' => 'other', 'logo_path' => 'salons/999/branding/theirs.png']);

        $this->get('/s/'.$current->slug)
            ->assertOk()
            ->assertSee("salons/{$current->id}/branding/mine.png", false)
            ->assertDontSee('theirs.png', false);
    }

    public function test_salon_without_logo_keeps_the_default_icon(): void
    {
        $current = app(CurrentSalon::class)->get();
        $current->update(['logo_path' => null]);

        $this->get('/s/'.$current->slug)
            ->assertOk()
            ->assertDontSee('branding/', false)
            ->assertSee('favicon.svg', false);
    }

    public function test_super_admin_can_replace_and_remove_a_logo_and_old_files_are_deleted(): void
    {
        $admin = $this->superAdmin();
        $salon = Salon::factory()->create();
        $base = ['name' => $salon->name, 'max_specialists_count' => $salon->max_specialists_count];

        $this->actingAs($admin)->put("/superadmin/salons/{$salon->id}", $base + [
            'logo' => UploadedFile::fake()->image('a.png', 128, 128),
        ])->assertRedirect(route('superadmin.salons.index'));
        $first = $salon->fresh()->logo_path;
        Storage::disk('public')->assertExists($first);

        $this->actingAs($admin)->put("/superadmin/salons/{$salon->id}", $base + [
            'logo' => UploadedFile::fake()->image('b.webp', 128, 128),
        ]);
        $second = $salon->fresh()->logo_path;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);

        $this->actingAs($admin)->put("/superadmin/salons/{$salon->id}", $base + ['remove_logo' => '1']);
        $this->assertNull($salon->fresh()->logo_path);
        Storage::disk('public')->assertMissing($second);
    }

    public function test_editing_without_a_new_logo_keeps_the_existing_one(): void
    {
        $admin = $this->superAdmin();
        $salon = Salon::factory()->create(['logo_path' => 'salons/1/branding/keep.png']);

        $this->actingAs($admin)->put("/superadmin/salons/{$salon->id}", [
            'name' => $salon->name, 'max_specialists_count' => $salon->max_specialists_count,
        ]);

        $this->assertSame('salons/1/branding/keep.png', $salon->fresh()->logo_path);
    }
}
