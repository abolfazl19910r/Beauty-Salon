<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\LegalAcceptance;
use App\Models\Role;
use App\Models\Salon;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * سند PDF پذیرش قرارداد سالن (۲۰۲۶-۱۱-۲۲): فقط ابرمدیر، برای هر سالن جدا؛ در پنل مالک و مدیر سالن دیده نمی‌شود.
 */
class SalonContractPdfTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    private User $owner;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salon = app(CurrentSalon::class)->get();
        $this->owner = User::factory()->create(['is_admin' => true, 'name' => 'مریم مالک']);
        $this->superAdmin = User::factory()->create(['is_admin' => true]);
        $this->superAdmin->roles()->attach(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));
    }

    private function acceptance(Salon $salon, string $document = 'salon_terms', ?string $version = null): LegalAcceptance
    {
        return LegalAcceptance::create([
            'user_id' => $this->owner->id, 'salon_id' => $salon->id, 'document' => $document,
            'version' => $version ?? config('legal.versions.'.$document), 'source' => 'web',
            'ip_address' => '5.215.100.80', 'user_agent' => 'Mozilla/5.0 Firefox/160.0', 'accepted_at' => now(),
        ]);
    }

    public function test_super_admin_sees_the_acceptance_history_and_downloads_a_pdf_per_acceptance(): void
    {
        $acc = $this->acceptance($this->salon);
        $this->acceptance($this->salon, 'privacy');

        $this->actingAs($this->superAdmin)->get(route('superadmin.salons.edit', $this->salon))->assertOk()
            ->assertSee('data-salon-contracts', false)->assertSee('قوانین و شرایط (سالن)')->assertSee('5.215.100.80')
            ->assertSee(route('superadmin.salons.contract-pdf', [$this->salon, $acc->id]), false);

        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.salons.contract-pdf', [$this->salon, $acc->id]))->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('contract-'.$this->salon->slug.'-'.$acc->id.'.pdf', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_the_pdf_body_has_the_salon_owner_version_ip_and_the_salon_terms_text(): void
    {
        $acc = $this->acceptance($this->salon, 'salon_terms', '1400-01-01');

        $html = view('superadmin.salons.contract-pdf', [
            'salon' => $this->salon, 'acceptance' => $acc->load('user'), 'currentVersion' => (string) config('legal.versions.salon_terms'),
            'isCurrent' => false, 'platform' => ['brand' => 'ماهرو', 'domain' => 'arisheh.ir', 'legal_name' => null, 'national_id' => null],
            'draft' => true, 'generatedBy' => $this->superAdmin,
        ])->render();

        $this->assertStringContainsString($this->salon->name, $html);
        $this->assertStringContainsString('مریم مالک', $html);
        $this->assertStringContainsString('۱۴۰۰/۰۱/۰۱', $html);
        $this->assertStringContainsString('5.215.100.80', $html);
        $this->assertStringContainsString('شرایط ثبت‌نام و احراز', $html);
        $this->assertStringContainsString('بایگانی نمی‌شود', $html, 'نسخه‌ی قدیمی‌تر ← هشدار متن نسخه‌ی فعلی');
    }

    public function test_another_salons_acceptance_is_not_served_and_only_super_admin_can_download(): void
    {
        $other = Salon::factory()->create();
        $foreign = $this->acceptance($other);
        $mine = $this->acceptance($this->salon);

        $this->actingAs($this->superAdmin)->get(route('superadmin.salons.contract-pdf', [$this->salon, $foreign->id]))->assertNotFound();
        $this->actingAs($this->owner)->get(route('superadmin.salons.contract-pdf', [$this->salon, $mine->id]))->assertForbidden();

        // پنل مالک: نه لینک و نه مسیر سند
        $this->actingAs($this->owner)->get(route('admin.terms-update.show'))->assertDontSee('contract-pdf');
        $this->actingAs($this->owner)->get(route('admin.dashboard'))->assertDontSee('contract-pdf');
    }

    public function test_public_terms_page_still_shows_the_salon_section(): void
    {
        $this->get(route('legal.terms'))->assertOk()->assertSee('بخش ب — سالن‌ها')->assertSee('شرایط ثبت‌نام و احراز')->assertSee('قانون حاکم');
    }
}
