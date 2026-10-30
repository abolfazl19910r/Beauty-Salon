<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\WalletSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ آزمون دستی 5ز (۲۰۲۶-۱۰-۳۰، بند ۹): با حداقل پیش‌پرداخت ۵۰۰، مرورگر خودش (step=1000) پیام انگلیسی «Please enter a valid value»
 * نشان می‌داد و فرم اصلاً به سرور نمی‌رسید؛ صفحه‌ی «تنظیمات مالی» هم خطاهای سرور را هیچ‌جا نشان نمی‌داد. حالا فرم novalidate است
 * و خطای فارسی سرور هم بالای صفحه (هشدار) و هم زیر همان فیلد دیده می‌شود. «نحوه‌ی رزرو» هم novalidate.
 */
class WalletSettingsErrorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_errors_are_shown_in_persian_and_the_browser_does_not_block_the_form(): void
    {
        WalletSetting::get();
        $owner = User::factory()->create(['is_admin' => true]);

        $page = $this->actingAs($owner)->get(route('admin.wallet.settings'))->assertOk();
        $this->assertMatchesRegularExpression('/<form[^>]*wallet\/settings[^>]*novalidate/', $page->getContent());

        $this->actingAs($owner)->from(route('admin.wallet.settings'))
            ->put(route('admin.wallet.settings.update'), ['minimum_prepayment_amount' => 500, 'prepayment_percentage' => 30])
            ->assertRedirect(route('admin.wallet.settings'));

        $this->actingAs($owner)->get(route('admin.wallet.settings'))
            ->assertSee('role="alert"', false)
            ->assertSee('حداقل پیش‌پرداخت باید دست‌کم ۱٬۰۰۰ تومان باشد')
            ->assertSee('id="settings-errors"', false);
    }

    public function test_booking_policy_mode_form_is_novalidate(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);

        $content = $this->actingAs($owner)->get(route('admin.booking-policy.edit'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<form[^>]*booking-policy\/mode[^>]*novalidate/', $content);
    }
}
