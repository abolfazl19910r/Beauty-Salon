<?php

namespace Tests\Feature\Legal;

use App\Models\Role;
use App\Models\User;
use App\Support\PlatformSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * صفحه‌های حقوقی پلتفرم (بسته‌ی مجوز 5د): حریم خصوصی، قوانین و شرایط، تماس با ما. «تماس با ما» تلفن ثابت، ایمیل و نشانی پستی
 * را از تنظیمات پلتفرم ابرمدیر نشان می‌دهد (الزام اینماد)، نه از کد. متن‌ها پیش‌نویس‌اند و علامت می‌خورند.
 */
class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $user = User::factory()->create(['is_admin' => true]);
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));

        return $user;
    }

    public function test_the_three_pages_are_public_marked_as_drafts_and_versioned(): void
    {
        foreach (['legal.privacy' => 'حریم خصوصی', 'legal.terms' => 'قوانین و شرایط', 'legal.contact' => 'تماس با ما'] as $route => $title) {
            $this->get(route($route))->assertOk()->assertSee($title)
                ->assertSee('data-legal-draft', false)->assertSee('نیاز به بازبینی حقوقی');
        }
        $this->get(route('legal.terms'))->assertSee(to_persian_num(str_replace('-', '/', config('legal.versions.terms'))));

        config(['legal.draft' => false]);
        $this->get(route('legal.privacy'))->assertDontSee('data-legal-draft', false);
    }

    public function test_contact_page_shows_landline_email_and_address_from_platform_settings(): void
    {
        $this->get(route('legal.contact'))->assertSee('data-legal-contact-incomplete', false);

        $this->actingAs($this->superAdmin())->put(route('superadmin.settings.legal'), [
            'legal_name' => 'ابوالفضل نمونه',
            'landline_phone' => '۰۲۱-۸۸۰۰ ۱۲۳۴',
            'contact_email' => 'info@arisheh.ir',
            'postal_address' => 'تهران، خیابان نمونه، پلاک ۱',
            'postal_code' => '۱۲۳۴۵۶۷۸۹۰',
        ])->assertRedirect()->assertSessionHas('success');
        auth()->logout();

        $this->get(route('legal.contact'))
            ->assertSee('ابوالفضل نمونه')
            ->assertSee('href="tel:02188001234"', false)
            ->assertSee('href="mailto:info@arisheh.ir"', false)
            ->assertSee('تهران، خیابان نمونه، پلاک ۱')
            ->assertSee('1234567890')
            ->assertDontSee('data-legal-contact-incomplete', false);
    }

    public function test_contact_email_falls_back_to_the_public_email(): void
    {
        PlatformSettings::save(['public_email' => 'hello@arisheh.ir']);

        $this->get(route('legal.contact'))->assertSee('href="mailto:hello@arisheh.ir"', false);
    }

    public function test_legal_settings_are_validated_and_only_for_the_super_admin(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->put(route('superadmin.settings.legal'), ['legal_name' => 'x'])->assertForbidden();

        $this->actingAs($this->superAdmin())->put(route('superadmin.settings.legal'), [
            'postal_code' => '123', 'contact_email' => 'nope', 'enamad_badge_html' => '<img src="/fake-enamad.png">',
        ])->assertSessionHasErrors(['postal_code', 'contact_email', 'enamad_badge_html']);

        $this->actingAs($this->superAdmin())->put(route('superadmin.settings.legal'), [
            'enamad_badge_html' => '<iframe src="https://trustseal.enamad.ir/x"></iframe>',
        ])->assertSessionHasErrors('enamad_badge_html');

        $this->actingAs($this->superAdmin())->get(route('superadmin.settings.edit'))->assertOk()->assertSee('اطلاعات حقوقی و اینماد');
    }

    public function test_footers_link_to_the_legal_pages_on_the_central_domain(): void
    {
        $this->get(route('central.home'))->assertSee('data-footer-legal', false)
            ->assertSee('href="'.route('legal.terms').'"', false)
            ->assertSee('href="'.route('legal.contact').'"', false);

        // سایت سالن و صفحه‌ی ورود سالن: لینک به صفحه‌های پلتفرم
        foreach (['/s/rasta', '/s/rasta/login'] as $uri) {
            $this->get($uri)->assertOk()->assertSee('data-platform-legal-links', false)
                ->assertSee('href="'.\App\Support\CentralUrl::route('legal.privacy').'"', false);
        }
    }

    public function test_privacy_page_states_the_retention_periods(): void
    {
        $this->get(route('legal.privacy'))->assertSee(to_persian_num((string) \App\Support\Retention::days('security_logs_days')))
            ->assertSee('شش ماه پس از حذف');
    }
}
