<?php

namespace Tests\Feature\SalonSignup;

use App\Models\Salon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ فاز ۲ SaaS، محور «۴. ثبت‌نام عمومی سالن (self-service)». پوشش این تست‌ها: مسیر کامل
 * موفق (ثبت → OTP → ورود مستقیم به admin.billing.index)، دو قانون یکتایی (slug، owner_phone
 * سراسری بین staff)، کد OTP نادرست/ارسال‌مجدد، و رفتار «سالن هنوز پول نداده» که کاملاً از
 * EnsureAdminSalonActive موجود می‌آد (بدون هیچ middleware جدید — به docblock
 * SalonSignupService نگاه کن).
 */
class SalonSignupTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'سالن الماس',
            'slug' => 'almas-test-salon',
            'subscription_type' => '3m',
            'owner_name' => 'سارا محمدی',
            'owner_phone' => '09121234567',
            'owner_password' => 'Str0ng!Passw0rd',
            'owner_password_confirmation' => 'Str0ng!Passw0rd',
        ], $overrides);
    }

    public function test_registration_form_is_publicly_accessible(): void
    {
        $this->get(route('salon-signup.create'))->assertOk();
    }

    public function test_store_creates_an_unverified_pending_salon_and_owner_and_sends_otp(): void
    {
        $this->post(route('salon-signup.store'), $this->validPayload())
            ->assertRedirect(route('salon-signup.verify'));

        $salon = Salon::where('slug', 'almas-test-salon')->firstOrFail();
        $owner = User::where('phone', '09121234567')->firstOrFail();

        $this->assertSame('3m', $salon->subscription_type);
        $this->assertFalse($salon->is_suspended);
        // ⭐ عمدی: از قبل منقضی، تا EnsureAdminSalonActive بدون هیچ کد جدیدی مستقیم به
        // admin.billing.index هدایت کنه — نه یک باگ.
        $this->assertTrue($salon->subscription_ends_at->isPast());
        $this->assertSame((int) config('billing.default_max_specialists_count'), $salon->max_specialists_count);

        $this->assertTrue($salon->admins()->wherePivot('user_id', $owner->id)->wherePivot('role', 'owner')->exists());
        $this->assertTrue($owner->is_admin);
        $this->assertFalse($owner->hasVerifiedPhone());
        $this->assertNotNull($owner->fresh()->verification_code);

        $this->assertGuest();
    }

    public function test_store_rejects_duplicate_slug(): void
    {
        Salon::factory()->create(['slug' => 'taken-slug']);

        $this->post(route('salon-signup.store'), $this->validPayload(['slug' => 'taken-slug']))
            ->assertSessionHasErrors('slug');
    }

    public function test_store_rejects_duplicate_owner_phone_among_staff(): void
    {
        User::factory()->admin()->create(['phone' => '09121234567']);

        $this->post(route('salon-signup.store'), $this->validPayload())
            ->assertSessionHasErrors('owner_phone');
    }

    public function test_full_flow_verifies_otp_and_logs_owner_straight_into_billing(): void
    {
        $this->post(route('salon-signup.store'), $this->validPayload());

        $owner = User::where('phone', '09121234567')->firstOrFail();
        $code = $owner->fresh()->verification_code;

        $response = $this->post(route('salon-signup.verify.store'), ['code' => $code]);

        $response->assertRedirect(route('admin.billing.index'));
        $this->assertAuthenticatedAs($owner->fresh());
        $this->assertTrue($owner->fresh()->hasVerifiedPhone());

        // ⭐ مصرف واقعی رفتار موجود EnsureAdminSalonActive: چون subscription_ends_at از قبل
        // گذشته، حتی بدون رفتن دستی، ادمین به admin.billing.index محدود می‌مونه — نه به کل پنل.
        $this->followingRedirects()->get(route('admin.billing.index'))->assertOk();
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.billing.index'));
    }

    public function test_wrong_otp_code_does_not_log_in(): void
    {
        $this->post(route('salon-signup.store'), $this->validPayload());

        $owner = User::where('phone', '09121234567')->firstOrFail();

        $this->post(route('salon-signup.verify.store'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertFalse($owner->fresh()->hasVerifiedPhone());
    }

    public function test_resend_code_issues_a_new_code(): void
    {
        $this->post(route('salon-signup.store'), $this->validPayload());

        $owner = User::where('phone', '09121234567')->firstOrFail();
        $firstCode = $owner->fresh()->verification_code;

        $this->post(route('salon-signup.resend-code'))->assertRedirect();

        $this->assertNotNull($owner->fresh()->verification_code);
        // کد جدید همیشه با کد قبلی فرق نمی‌کنه (rand می‌تونه تصادفاً تکرار بشه)، ولی حداقل
        // verification_code_expire_at باید تازه‌سازی شده باشه.
        $this->assertTrue($owner->fresh()->verification_code_expire_at->isFuture());
        unset($firstCode);
    }

    public function test_verify_page_redirects_to_create_without_a_pending_session(): void
    {
        $this->get(route('salon-signup.verify'))->assertRedirect(route('salon-signup.create'));
    }

    // ⭐ مورد ۴ (نشست ۲۰۲۶-۰۹-۲۰): چک یکتایی زنده — همین دو endpoint رو فرم سوپرادمین
    // (superadmin.salons.create) هم مستقیم استفاده می‌کنه، پس این تست‌ها هر دو مصرف‌کننده رو
    // پوشش می‌دن.
    public function test_check_slug_reports_available_for_a_free_slug(): void
    {
        $this->getJson(route('salon-signup.check-slug', ['slug' => 'brand-new-salon']))
            ->assertOk()
            ->assertJson(['available' => true, 'reason' => null]);
    }

    public function test_check_slug_reports_taken_for_an_existing_slug(): void
    {
        Salon::factory()->create(['slug' => 'already-taken']);

        $this->getJson(route('salon-signup.check-slug', ['slug' => 'already-taken']))
            ->assertOk()
            ->assertJson(['available' => false, 'reason' => 'taken']);
    }

    public function test_check_slug_rejects_invalid_characters(): void
    {
        $this->getJson(route('salon-signup.check-slug', ['slug' => 'not valid slug!']))
            ->assertOk()
            ->assertJson(['available' => false, 'reason' => 'invalid']);
    }

    public function test_check_phone_reports_available_for_a_free_phone(): void
    {
        $this->getJson(route('salon-signup.check-phone', ['phone' => '09129999999']))
            ->assertOk()
            ->assertJson(['available' => true, 'reason' => null]);
    }

    public function test_check_phone_reports_taken_for_an_existing_staff_phone(): void
    {
        User::factory()->admin()->create(['phone' => '09129999999']);

        $this->getJson(route('salon-signup.check-phone', ['phone' => '09129999999']))
            ->assertOk()
            ->assertJson(['available' => false, 'reason' => 'taken']);
    }

    public function test_check_phone_does_not_flag_a_customer_phone_as_taken(): void
    {
        // ⭐ قانون StoreSalonSignupRequest: phone فقط بین user_type='staff' یکتاست، نه مشتری‌ها —
        // یک مشتری با همین شماره نباید ثبت‌نام یک ادمین جدید رو مسدود کنه.
        User::factory()->create(['phone' => '09121112233', 'user_type' => 'customer']);

        $this->getJson(route('salon-signup.check-phone', ['phone' => '09121112233']))
            ->assertOk()
            ->assertJson(['available' => true, 'reason' => null]);
    }

    public function test_check_phone_rejects_invalid_format(): void
    {
        $this->getJson(route('salon-signup.check-phone', ['phone' => '12345']))
            ->assertOk()
            ->assertJson(['available' => false, 'reason' => 'invalid']);
    }
}
