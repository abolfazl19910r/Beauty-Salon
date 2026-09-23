<?php

namespace Tests\Feature\Admin;

use App\Models\Salon;
use App\Models\User;
use App\Support\SalonWorkingHours;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * ⭐ صفحه‌ی «اطلاعات سالن» مالک سالن در پنل مدیریت (۲۰۲۶-۰۹-۲۴).
 */
class AdminSalonSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Salon $salon;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->salon = Salon::where('slug', 'rasta')->firstOrFail();
        // اولین is_admin ساخته‌شده خودکار owner سالن جاری می‌شه (UserFactory::configure)
        $this->owner = User::factory()->create(['is_admin' => true]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'سالن راستای جدید',
            'tagline' => 'زیبایی، با وقت قبلی',
            'bio' => 'معرفی تازه‌ی سالن',
            'salon_address' => 'کرج، بلوار جمهوری، پلاک ۵',
            'salon_phone' => '۰۲۶-۳۲۲۲ ۴۴۵۵',
            'experience_years' => '11',
        ], $overrides);
    }

    public function test_owner_sees_the_page_and_the_sidebar_link(): void
    {
        $this->actingAs($this->owner)->get(route('admin.salon-settings.edit'))
            ->assertOk()
            ->assertSee('اطلاعات سالن')
            ->assertSee($this->salon->slug);

        $this->actingAs($this->owner)->get(route('admin.dashboard'))
            ->assertSee(route('admin.salon-settings.edit'), false);
    }

    public function test_owner_updates_name_intro_contact_and_logo(): void
    {
        $this->actingAs($this->owner)->put(route('admin.salon-settings.update'), $this->payload([
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ]))->assertRedirect(route('admin.salon-settings.edit'))->assertSessionHas('success');

        $salon = $this->salon->fresh();
        $this->assertSame('سالن راستای جدید', $salon->name);
        $this->assertSame('زیبایی، با وقت قبلی', $salon->tagline);
        $this->assertSame('کرج، بلوار جمهوری، پلاک ۵', $salon->address);
        $this->assertSame('02632224455', $salon->phone);
        $this->assertSame(11, $salon->experienceYears());
        $this->assertStringStartsWith("salons/{$salon->id}/branding/", $salon->logo_path);
        $this->assertSame('rasta', $salon->slug, 'آدرس اختصاصی نباید قابل تغییر باشه');
    }

    public function test_slug_in_the_request_is_ignored(): void
    {
        $this->actingAs($this->owner)->put(route('admin.salon-settings.update'), $this->payload(['slug' => 'hijack']));

        $this->assertSame('rasta', $this->salon->fresh()->slug);
    }

    public function test_working_hours_are_saved_and_kept_when_not_submitted(): void
    {
        $hours = SalonWorkingHours::defaults();
        $input = [];
        foreach ($hours as $day => $value) {
            $input[$day] = $value === null ? ['closed' => '1'] : $value;
        }
        $input[1] = ['open' => '10:00', 'close' => '18:00'];

        $this->actingAs($this->owner)->put(route('admin.salon-settings.update'), $this->payload(['working_hours' => $input]));
        $this->assertSame(['open' => '10:00', 'close' => '18:00'], $this->salon->fresh()->working_hours[1]);

        // فرم بدون تیک «نمایش ساعات کاری» → working_hours ارسال نمی‌شه → دست‌نخورده
        $this->actingAs($this->owner)->put(route('admin.salon-settings.update'), $this->payload());
        $this->assertSame(['open' => '10:00', 'close' => '18:00'], $this->salon->fresh()->working_hours[1]);
    }

    public function test_owner_can_remove_the_logo(): void
    {
        Storage::disk('public')->put("salons/{$this->salon->id}/branding/old.png", 'x');
        $this->salon->update(['logo_path' => "salons/{$this->salon->id}/branding/old.png"]);

        $this->actingAs($this->owner)->put(route('admin.salon-settings.update'), $this->payload(['remove_logo' => '1']));

        $this->assertNull($this->salon->fresh()->logo_path);
        Storage::disk('public')->assertMissing("salons/{$this->salon->id}/branding/old.png");
    }

    public function test_validation_errors(): void
    {
        $this->actingAs($this->owner)->put(route('admin.salon-settings.update'), $this->payload(['name' => '', 'salon_phone' => '123']))
            ->assertSessionHasErrors(['name', 'salon_phone']);
    }

    public function test_staff_admin_cannot_open_or_update(): void
    {
        $staff = User::factory()->create(['is_admin' => true]);
        $this->salon->admins()->syncWithoutDetaching([$staff->id => ['role' => 'staff']]);

        $this->actingAs($staff)->get(route('admin.salon-settings.edit'))->assertForbidden();
        $this->actingAs($staff)->put(route('admin.salon-settings.update'), $this->payload())->assertForbidden();
        $this->assertNotSame('سالن راستای جدید', $this->salon->fresh()->name);
    }

    public function test_changes_show_up_on_the_customer_site(): void
    {
        $this->actingAs($this->owner)->put(route('admin.salon-settings.update'), $this->payload());

        $this->get('/s/rasta')->assertOk()->assertSee('سالن راستای جدید')->assertSee('کرج، بلوار جمهوری، پلاک ۵');
    }
}
