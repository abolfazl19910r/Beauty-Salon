<?php

namespace Tests\Feature\Salon;

use App\Models\Role;
use App\Models\Salon;
use App\Models\SalonVerification;
use App\Models\User;
use App\Rules\IranianNationalCode;
use App\Services\Salon\SalonVerificationService;
use App\Services\SMSService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * احراز هویت مالک و پروانه‌ی کسب سالن، مرحله‌ی الف دستی (بسته‌ی مجوز 5د؛ دستورالعمل ساماندهی سکوهای اینترنتی بند ۲ و ۴).
 * پیش‌تر سالن بی هیچ احرازی (فقط موبایل مالک) همان لحظه سایت عمومی و رزرو داشت.
 */
class SalonVerificationTest extends TestCase
{
    use RefreshDatabase;

    private const VALID_CODE = '0499370899';

    private Salon $salon;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(SalonVerificationService::DISK);
        $this->salon = Salon::factory()->unverified()->create(['slug' => 'tazeh']);
        $this->owner = User::factory()->create(['is_admin' => true, 'user_type' => 'staff', 'phone' => '09121110000']);
        $this->salon->admins()->attach($this->owner->id, ['role' => 'owner']);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['is_admin' => true]);
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));

        return $user;
    }

    private function asOwner(): static
    {
        app(CurrentSalon::class)->set($this->salon);

        return $this->actingAs($this->owner)->withSession(['admin_salon_id' => $this->salon->id]);
    }

    private function submit(array $overrides = [])
    {
        return $this->asOwner()->post(route('admin.verification.store'), array_merge([
            'owner_name' => 'سارا محمدی', 'owner_national_code' => '۰۴۹۹۳۷۰۸۹۹', 'license_type' => 'women_hairdressing',
            'license_unique_id' => 'NPM-1404-123456', 'license_file' => UploadedFile::fake()->create('license.pdf', 300, 'application/pdf'),
        ], $overrides));
    }

    public function test_national_code_check_digit(): void
    {
        $this->assertTrue(IranianNationalCode::isValid(self::VALID_CODE));
        $this->assertFalse(IranianNationalCode::isValid('0499370898'));
        $this->assertFalse(IranianNationalCode::isValid('1111111111'));
        $this->assertFalse(IranianNationalCode::isValid('123'));
    }

    public function test_an_unverified_salon_is_open_for_booking_without_prepayment_but_not_indexed(): void
    {
        // بسته‌ی ۷ (تصمیم ابوالفضل ۲۰۲۶-۱۱-۲۶): دوره‌ی آزمایشی و سالن بی درگاه منتظر احراز نمی‌مانند
        $this->get('/s/tazeh')->assertOk()->assertDontSee('data-unverified-preview', false)->assertSee('noindex', false);
        $this->get('/s/tazeh/services')->assertOk();
        $this->getJson('/api/v1/customer/salons/tazeh')->assertOk();
        $this->get('/s/tazeh/robots.txt')->assertOk()->assertSee("Disallow: /\n", false);

        $this->asOwner()->get(route('admin.verification.show'))->assertOk()->assertSee('احراز سالن')->assertSee('name="owner_national_code"', false);
    }

    public function test_verification_is_needed_only_to_connect_a_payment_gateway(): void
    {
        $this->asOwner()->get(route('admin.dashboard'))->assertOk()->assertDontSee('data-verification-banner', false);
        $this->asOwner()->get(route('admin.payment-gateways.index'))->assertOk()
            ->assertSee('data-gateway-needs-verification', false)->assertSee('data-verification-banner', false)
            ->assertDontSee('id="add-gateway"', false);
        $before = \App\Models\SalonPaymentGateway::withoutGlobalScopes()->where('salon_id', $this->salon->id)->count();
        $this->asOwner()->post(route('admin.payment-gateways.store'), ['driver' => 'zarinpal', 'credentials' => ['merchant_id' => str_repeat('a', 36)]])
            ->assertRedirect(route('admin.verification.show'));
        $this->assertSame($before, \App\Models\SalonPaymentGateway::withoutGlobalScopes()->where('salon_id', $this->salon->id)->count());

        $setup = \App\Services\Admin\Dashboard\SalonSetupChecklist::for($this->salon->fresh());
        $this->assertNull(collect($setup['steps'])->firstWhere('key', 'verification'));
        $this->assertSame(route('admin.verification.show'), collect($setup['steps'])->firstWhere('key', 'gateway')['url']);
    }

    public function test_the_super_admin_can_verify_without_documents_with_a_reason(): void
    {
        $super = $this->superAdmin();
        $this->actingAs($super)->get(route('superadmin.salons.edit', $this->salon))->assertOk()->assertSee('data-verify-manually', false);
        $this->actingAs($super)->post(route('superadmin.salons.verify-manually', $this->salon), ['note' => ''])->assertSessionHasErrors('note');

        $this->mock(SMSService::class)->shouldReceive('send')->once()->withArgs(fn ($phone, $text) => $phone === '09121110000' && str_contains($text, 'درگاه پرداخت'))->andReturnTrue();
        $this->actingAs($super)->post(route('superadmin.salons.verify-manually', $this->salon), ['note' => 'سالن آزمایشی ماهرو'])->assertRedirect(route('superadmin.salons.edit', $this->salon));

        $this->assertTrue($this->salon->fresh()->isVerified());
        $this->assertDatabaseHas('superadmin_audit_logs', ['action' => 'salon.verification']);
        $this->actingAs($this->owner)->post(route('superadmin.salons.verify-manually', $this->salon), ['note' => 'xxxxx'])->assertForbidden();
    }

    public function test_the_owner_submits_documents_to_a_private_disk_and_the_salon_becomes_pending(): void
    {
        $this->submit(['owner_national_code' => '0499370898'])->assertSessionHasErrors('owner_national_code');
        $this->submit(['license_file' => UploadedFile::fake()->create('x.exe', 10)])->assertSessionHasErrors('license_file');

        $this->submit()->assertRedirect(route('admin.verification.show'));

        $v = SalonVerification::sole();
        $this->assertSame('pending', $v->status);
        $this->assertSame(self::VALID_CODE, $v->owner_national_code, 'رقم فارسی لاتین می‌شود');
        $this->assertStringNotContainsString(self::VALID_CODE, (string) DB::table('salon_verifications')->value('owner_national_code'), 'رمزنگاری‌شده');
        Storage::disk(SalonVerificationService::DISK)->assertExists($v->license_file_path);
        $this->assertStringStartsWith('salon-verifications/'.$this->salon->id.'/', $v->license_file_path);
        $this->assertSame('pending', $this->salon->fresh()->verification_status);

        $this->submit()->assertSessionHas('error');
        $this->assertSame(1, SalonVerification::count(), 'در انتظار بررسی، ارسال دوباره نه');
    }

    public function test_the_super_admin_approves_with_an_sms_to_the_owner(): void
    {
        $this->submit();
        $v = SalonVerification::sole();
        $super = $this->superAdmin();

        $this->actingAs($super)->get(route('superadmin.verifications.index'))->assertOk()->assertSee('data-verification-row', false);
        $this->actingAs($super)->get(route('superadmin.verifications.show', $v))->assertOk()->assertSee(self::VALID_CODE)->assertSee('NPM-1404-123456');
        $this->actingAs($super)->get(route('superadmin.verifications.file', $v))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs($this->owner)->get(route('superadmin.verifications.file', $v))->assertForbidden();

        $this->mock(SMSService::class)->shouldReceive('send')->once()->withArgs(fn ($phone, $text) => $phone === '09121110000' && str_contains($text, 'تأیید شد') && str_contains($text, 'درگاه پرداخت'))->andReturnTrue();
        $this->actingAs($super)->post(route('superadmin.verifications.approve', $v))->assertRedirect();

        $this->assertTrue($this->salon->fresh()->isVerified());
        $this->assertSame('approved', $v->fresh()->status);
        auth()->logout();
        $this->get('/s/tazeh')->assertOk();
    }

    public function test_rejection_needs_a_reason_and_the_owner_can_resubmit(): void
    {
        $this->submit();
        $v = SalonVerification::sole();
        $super = $this->superAdmin();

        $this->actingAs($super)->post(route('superadmin.verifications.reject', $v), ['reason' => ''])->assertSessionHasErrors('reason');
        $this->actingAs($super)->post(route('superadmin.verifications.reject', $v), ['reason' => 'شناسه‌ی یکتا با نام مالک نمی‌خواند'])->assertRedirect();

        $this->assertSame('rejected', $this->salon->fresh()->verification_status);
        $this->asOwner()->get(route('admin.verification.show'))->assertSee('شناسه‌ی یکتا با نام مالک نمی‌خواند');
        $this->submit()->assertRedirect();
        $this->assertSame(2, SalonVerification::count());
    }

    public function test_the_demo_salon_and_factory_salons_are_already_verified(): void
    {
        $this->assertTrue(Salon::where('slug', 'rasta')->first()->isVerified());
        $this->get('/s/rasta')->assertOk();
    }

    public function test_new_salons_from_signup_or_super_admin_start_unverified(): void
    {
        $this->assertSame('unverified', Salon::query()->create([
            'name' => 'x', 'slug' => 'x-new', 'subscription_type' => '1m', 'subscription_started_at' => now(), 'subscription_ends_at' => now()->addMonth(),
        ])->fresh()->verification_status);
    }
}
