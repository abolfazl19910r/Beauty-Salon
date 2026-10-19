<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Role;
use App\Models\Salon;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بسته‌ی «ح»، ۲ (۲۰۲۶-۱۰-۱۹): ظاهر پنل ابرمدیر. شکل در Chromium (دسکتاپ و ۳۹۰ پیکسل) سنجیده شد؛ اینجا سیم‌کشی‌ها.
 */
class SuperAdminLayoutPolishTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $role = Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']);
        $this->superAdmin = User::factory()->create(['is_admin' => true]);
        $this->superAdmin->roles()->attach($role);
        app(CurrentSalon::class)->clear();
    }

    public function test_layout_has_a_mobile_menu_table_scroll_and_persian_digits(): void
    {
        $yas = Salon::factory()->create(['slug' => 'yas']);

        $html = $this->actingAs($this->superAdmin)->get(route('superadmin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('data-sa-menu', $html);
        $this->assertStringContainsString('<aside id="sa-side"', $html);
        $this->assertStringContainsString("querySelectorAll('main table')", $html);
        $this->assertStringContainsString("var FA = '۰۱۲۳۴۵۶۷۸۹';", $html);
        // آدرس واقعی سالن (ساب‌دامین وقتی CENTRAL_DOMAIN پر است)، چپ‌به‌راست
        $this->assertStringContainsString('class="sa-ltr" style="color: var(--sa-accent);">'.\App\Support\SalonAddress::short($yas).'</a>', $html);
        $this->assertStringEndsWith('/s/yas', \App\Support\SalonAddress::short($yas));
        $this->assertStringContainsString('data-dashboard-stats', $html);
    }

    public function test_salon_edit_is_split_into_sections(): void
    {
        $salon = Salon::factory()->create();

        $this->actingAs($this->superAdmin)->get(route('superadmin.salons.edit', $salon))->assertOk()
            ->assertSeeInOrder(['پلن و امکانات', 'اطلاعات سالن', 'اطلاعات تماس و فعالیت سالن', 'محدودیت‌ها، دسترسی‌ها و پرداخت', 'آدرس و مالک', 'پیامک‌های این ماه']);
    }

    public function test_ticket_categories_are_shown_in_persian(): void
    {
        $salon = Salon::factory()->create();
        SupportTicket::query()->withoutGlobalScopes()->create([
            'salon_id' => $salon->id, 'user_id' => $this->superAdmin->id, 'title' => 'کندی پنل', 'description' => 'آزمون',
            'category' => 'technical', 'priority' => 'medium', 'status' => 'open', 'ticket_number' => 'T-1',
        ]);

        $this->actingAs($this->superAdmin)->get(route('superadmin.support-tickets.index'))->assertOk()
            ->assertSee('فنی')->assertDontSee('>technical<', false);
    }
}
