<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Invoice;
use App\Models\Role;
use App\Models\Salon;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Morilog\Jalali\Jalalian;
use Tests\TestCase;

/**
 * ⭐ بخش «کیف پول / درآمد اشتراک» سوپرادمین (۲۰۲۶-۰۹-۲۴).
 */
class SubscriptionPaymentsTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private Salon $almas;

    private Salon $yas;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']);
        $this->superAdmin = User::factory()->create(['is_admin' => true, 'name' => 'مدیر کل', 'phone' => '09120001111']);
        $this->superAdmin->roles()->attach($role);

        // سوپرادمین در runtime هیچ CurrentSalon ای نداره
        app(CurrentSalon::class)->clear();

        $this->almas = Salon::factory()->create(['name' => 'سالن الماس', 'slug' => 'almas']);
        $this->yas = Salon::factory()->create(['name' => 'سالن یاس', 'slug' => 'yas']);

        Invoice::withoutEvents(function () {
            Invoice::factory()->paid()->create(['salon_id' => $this->almas->id, 'subscription_type' => '6m', 'amount' => 7650000, 'ref_id' => 'REF777', 'paid_at' => now()->subDays(2), 'created_at' => now()->subDays(2)]);
            Invoice::factory()->paid()->manual()->create(['salon_id' => $this->yas->id, 'subscription_type' => '1m', 'amount' => 1500000, 'ref_id' => null, 'created_by' => $this->superAdmin->id, 'paid_at' => now()->subDays(40), 'created_at' => now()->subDays(40)]);
            Invoice::factory()->create(['salon_id' => $this->yas->id, 'subscription_type' => '3m', 'amount' => 4150000, 'status' => 'failed', 'authority' => 'A0000FAIL']);
            Invoice::factory()->create(['salon_id' => $this->almas->id, 'subscription_type' => '12m', 'amount' => 13850000, 'status' => 'pending', 'authority' => 'A0000PEND']);
        });
    }

    private function index(array $query = [])
    {
        return $this->actingAs($this->superAdmin)->get(route('superadmin.payments.index', $query));
    }

    private function ids($response): array
    {
        return collect($response->viewData('invoices')->items())->pluck('id')->sort()->values()->all();
    }

    public function test_only_super_admin_can_open_it(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('superadmin.payments.index'))
            ->assertStatus(403);
    }

    public function test_lists_every_salons_invoices_with_overview_and_summary(): void
    {
        $response = $this->index()->assertOk()->assertSee('سالن الماس')->assertSee('سالن یاس');

        $this->assertCount(4, $response->viewData('invoices')->items());
        $summary = $response->viewData('summary');
        $this->assertSame(4, $summary['total_count']);
        $this->assertSame(2, $summary['paid_count']);
        $this->assertSame(1, $summary['pending_count']);
        $this->assertSame(1, $summary['failed_count']);
        $this->assertSame(7650000 + 1500000, $summary['paid_sum']);
        $this->assertSame(7650000, $summary['online_sum']);
        $this->assertSame(1500000, $summary['manual_sum']);
        $this->assertSame(2, $summary['paying_salons']);
        $this->assertSame(['count' => 1, 'total' => 7650000], $summary['by_plan']['6m']);
        $this->assertSame(9150000, $response->viewData('overview')['all_time']);
        $this->assertSame(1, $response->viewData('overview')['pending_now']);
    }

    public function test_search_by_salon_name_slug_ref_id_authority_invoice_id_and_creator(): void
    {
        $this->assertCount(2, $this->index(['q' => 'الماس'])->viewData('invoices')->items());
        $this->assertCount(2, $this->index(['q' => 'yas'])->viewData('invoices')->items());
        $this->assertCount(1, $this->index(['q' => 'REF777'])->viewData('invoices')->items());
        $this->assertCount(1, $this->index(['q' => 'A0000FAIL'])->viewData('invoices')->items());
        $this->assertCount(1, $this->index(['q' => '09120001111'])->viewData('invoices')->items());

        $id = Invoice::withoutGlobalScopes()->where('authority', 'A0000PEND')->value('id');
        $this->assertSame([$id], $this->ids($this->index(['q' => '#'.$id])));
    }

    public function test_filters_combine_and_summary_follows_them(): void
    {
        $response = $this->index(['salon_id' => $this->almas->id, 'status' => 'paid']);
        $this->assertCount(1, $response->viewData('invoices')->items());
        $this->assertSame(7650000, $response->viewData('summary')['paid_sum']);

        $this->assertCount(1, $this->index(['payment_method' => 'manual'])->viewData('invoices')->items());
        $this->assertCount(1, $this->index(['subscription_type' => '12m'])->viewData('invoices')->items());
        $this->assertCount(2, $this->index(['amount_min' => 5000000])->viewData('invoices')->items());
        $this->assertCount(1, $this->index(['amount_min' => 1000000, 'amount_max' => 2000000])->viewData('invoices')->items());
    }

    public function test_jalali_date_range_on_paid_at(): void
    {
        $from = Jalalian::fromCarbon(now()->subDays(7))->format('Y/m/d');
        $to = Jalalian::fromCarbon(now())->format('Y/m/d');

        $response = $this->index(['date_field' => 'paid_at', 'date_from' => $from, 'date_to' => $to]);
        $this->assertCount(1, $response->viewData('invoices')->items());

        // ارقام فارسی هم پذیرفته می‌شن
        $this->assertCount(1, $this->index([
            'date_field' => 'paid_at', 'date_from' => to_persian_num($from), 'date_to' => to_persian_num($to),
        ])->viewData('invoices')->items());
    }

    public function test_invalid_filter_values_are_rejected_not_crashing(): void
    {
        $this->index(['status' => 'hacked'])->assertSessionHasErrors('status');
        $this->index(['date_from' => 'not-a-date'])->assertSessionHasErrors('date_from');
    }

    public function test_sorting_by_amount(): void
    {
        $items = $this->index(['sort' => 'amount_desc'])->viewData('invoices')->items();
        $this->assertSame(13850000, (int) $items[0]->amount);
        $this->assertSame(1500000, (int) end($items)->amount);
    }

    public function test_invoice_detail_page(): void
    {
        $invoice = Invoice::withoutGlobalScopes()->where('ref_id', 'REF777')->firstOrFail();

        $this->actingAs($this->superAdmin)->get(route('superadmin.payments.show', $invoice->id))
            ->assertOk()
            ->assertSee('REF777')
            ->assertSee('سالن الماس')
            ->assertSee('شش‌ماهه');

        $this->actingAs($this->superAdmin)->get(route('superadmin.payments.show', 999999))->assertNotFound();
    }

    public function test_detail_page_works_even_when_a_current_salon_is_bound(): void
    {
        // scope سراسری Invoice نباید اینجا فاکتور سالن دیگه‌ای رو ۴۰۴ کنه
        app(CurrentSalon::class)->set($this->yas);
        $invoice = Invoice::withoutGlobalScopes()->where('ref_id', 'REF777')->firstOrFail();

        $this->actingAs($this->superAdmin)->get(route('superadmin.payments.show', $invoice->id))->assertOk();
    }

    public function test_csv_export_respects_filters(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.payments.export', ['status' => 'paid']));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('REF777', $csv);
        $this->assertStringContainsString('سالن یاس', $csv);
        $this->assertStringNotContainsString('A0000FAIL', $csv);
    }
}
