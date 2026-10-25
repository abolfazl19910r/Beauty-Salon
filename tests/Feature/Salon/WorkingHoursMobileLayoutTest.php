<?php

namespace Tests\Feature\Salon;

use App\Models\Role;
use App\Models\Salon;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ساعت کاری سالن در عرض گوشی (۲۰۲۶-۱۰-۲۵). 🐞 `/salon-signup` در ۳۹۰px حدود ۵۸px سرریز افقی داشت: ردیف ساعت (روز، «تعطیل»، دو ساعت)
 * در یک خط ~۴۰۰px جا می‌خواست و fieldset (پیش‌فرض min-width: min-content) از کارت بیرون می‌زد. در پنل مدیر و ابرمدیر سرریز نبود ولی هر
 * ساعت ~۶۰px می‌شد و فقط «09» دیده می‌شد. خود چیدمان در Chromium بی‌سر در ۳۲۰ تا ۱۳۶۶ سنجیده شد (بی سرریز، ساعت ≥۱۳۰px)؛ اینجا سیم‌کشی آن.
 */
class WorkingHoursMobileLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_salon_signup_hours_wrap_on_phones_and_the_fieldset_cannot_stretch(): void
    {
        $html = $this->get(route('salon-signup.create'))->assertOk()->getContent();

        $this->assertStringContainsString('fieldset { border: none; padding: 0; margin: 0 0 1.5rem; min-width: 0; }', $html);
        $this->assertStringContainsString('.hours-row input[type=time] { padding: .4rem .5rem; direction: ltr; min-width: 0; }', $html);
        $this->assertMatchesRegularExpression('/@media \(max-width: 520px\) \{\s*\.hours-row \{ grid-template-columns: 1fr 1fr;/', $html);
        $this->assertStringContainsString('@media (max-width: 359px) { .hours-row input[type=time] { grid-column: 1 / -1; } }', $html);
    }

    public function test_admin_salon_settings_hours_wrap_on_phones(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->assertResponsiveHours($this->actingAs($admin)->get(route('admin.salon-settings.edit'))->assertOk()->getContent());
    }

    public function test_super_admin_salon_form_hours_wrap_on_phones(): void
    {
        $role = Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']);
        $superAdmin = User::factory()->create(['is_admin' => true]);
        $superAdmin->roles()->attach($role);
        app(CurrentSalon::class)->clear();
        $salon = Salon::factory()->create();

        $this->assertResponsiveHours($this->actingAs($superAdmin)->get(route('superadmin.salons.edit', $salon))->assertOk()->getContent());
    }

    private function assertResponsiveHours(string $html): void
    {
        $this->assertSame(7, substr_count($html, 'class="grid grid-cols-2 sm:grid-cols-[4.5rem_auto_minmax(0,1fr)_minmax(0,1fr)] gap-2'), 'هر هفت روز');
        $this->assertSame(0, substr_count($html, 'class="grid grid-cols-4 gap-2 items-center text-sm"'));
        $this->assertSame(14, preg_match_all('/<input type="time" name="working_hours\[\d\]\[(open|close)\]"[^>]*min-w-0 max-\[359px\]:col-span-2/', $html));
    }
}
