<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Invoice;
use App\Models\Role;
use App\Models\Salon;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بسته‌ی ۱ ظاهری (۲۰۲۶-۱۱-۲۴): جستجوی سراسری نوار بالای پنل ابرمدیر — سالن، شماره‌ی موبایل و فاکتور در یک صفحه.
 */
class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $super;

    protected function setUp(): void
    {
        parent::setUp();
        app(CurrentSalon::class)->clear();
        $this->super = User::factory()->create(['is_admin' => true, 'user_type' => 'staff', 'salon_id' => null]);
        $this->super->roles()->attach(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));
    }

    public function test_every_page_has_the_search_bar_and_the_old_menu_item_is_gone(): void
    {
        $html = $this->actingAs($this->super)->get(route('superadmin.salons.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-global-search', $html);
        $this->assertStringContainsString('action="'.route('superadmin.search.index').'"', $html);
        $this->assertStringContainsString('جستجوی سالن، شماره موبایل یا فاکتور', $html);
        $this->assertStringContainsString('sa-search-btn', $html); // آیکن موبایل
        $this->assertStringNotContainsString('>جستجوی شماره', $html);
    }

    public function test_text_finds_salons_by_name_and_slug(): void
    {
        $salon = Salon::factory()->create(['name' => 'سالن یاس سپید', 'slug' => 'yas-sepid']);
        $owner = User::factory()->create(['name' => 'نرگس مالک', 'phone' => '09127778899']);
        $salon->admins()->attach($owner->id, ['role' => 'owner']);

        $this->actingAs($this->super)->get(route('superadmin.search.index', ['q' => 'یاس']))->assertOk()
            ->assertSee('data-search-salons', false)->assertSee('سالن یاس سپید')->assertSee('نرگس مالک')
            ->assertSee(route('superadmin.salons.edit', $salon), false);
        $this->actingAs($this->super)->get(route('superadmin.search.index', ['q' => 'yas-se']))->assertSee('سالن یاس سپید');
        // شماره‌ی مالک سالن را هم پیدا می‌کند
        $this->actingAs($this->super)->get(route('superadmin.search.index', ['q' => '۰۹۱۲۷۷۷۸۸۹۹']))->assertSee('data-search-salon', false)->assertSee('سالن یاس سپید');
        $this->actingAs($this->super)->get(route('superadmin.search.index', ['q' => 'ی']))->assertSee('دست‌کم دو حرف');
    }

    public function test_numbers_find_invoices_by_id_and_reference(): void
    {
        $salon = Salon::factory()->create(['name' => 'سالن فاکتوردار']);
        $invoice = Invoice::factory()->paid()->create(['salon_id' => $salon->id, 'ref_id' => '50012345678', 'amount' => 1_500_000]);

        $this->actingAs($this->super)->get(route('superadmin.search.index', ['q' => (string) $invoice->id]))->assertOk()
            ->assertSee('data-search-invoices', false)->assertSee('سالن فاکتوردار')->assertSee(route('superadmin.payments.show', $invoice), false);
        $this->actingAs($this->super)->get(route('superadmin.search.index', ['q' => '۱۲۳۴۵۶']))->assertSee('data-search-invoice', false)->assertSee('50012345678');
        $this->actingAs($this->super)->get(route('superadmin.search.index', ['q' => 'هیچ‌چیز']))->assertSee('چیزی پیدا نشد');
    }
}
