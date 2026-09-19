<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Invoice;
use App\Models\Role;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 8 — final item of the
 * "📊 پیگیری پیشرفت فاز ۱" checklist). Covers the definition-of-done for the super-admin panel:
 * full salon+admin creation, cross-role access control (a non-super-admin must never reach any
 * /superadmin/* route), subscription expiry/renewal/suspension math, the specialist quota rule,
 * and the default salon's immunity to suspension.
 *
 * Every test here creates its OWN salon(s) rather than relying on the 'rasta' default salon
 * TestCase::setUp() binds — same pattern AdminBookingSlotConflictTest already established for
 * salon-isolation tests (see that file, and TestCase::setUp()'s own docblock). CurrentSalon is
 * never set for /superadmin routes in production (EnsureSuperAdmin never calls set()), so these
 * tests act as the super admin without touching CurrentSalon at all — exactly mirroring runtime.
 */
class SalonManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']);
        $this->superAdmin = User::factory()->create(['is_admin' => true]);
        $this->superAdmin->roles()->attach($role);
    }

    private function validSalonPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'سالن نمونه',
            'slug' => 'sample-salon-'.uniqid(),
            'subscription_type' => '1m',
            'max_specialists_count' => 5,
            'admin_name' => 'ادمین سالن',
            'admin_phone' => '09'.random_int(100000000, 999999999),
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
        ], $overrides);
    }

    // ---------------------------------------------------------------------
    // ۱) ساخت کامل سالن + ادمین
    // ---------------------------------------------------------------------

    public function test_store_creates_a_salon_with_one_owner_admin(): void
    {
        $payload = $this->validSalonPayload();

        $response = $this->actingAs($this->superAdmin)->post('/superadmin/salons', $payload);

        $response->assertRedirect(route('superadmin.salons.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('salons', [
            'slug' => $payload['slug'],
            'subscription_type' => '1m',
            'max_specialists_count' => 5,
            'is_suspended' => false,
        ]);

        $salon = Salon::where('slug', $payload['slug'])->first();
        $admin = User::where('phone', $payload['admin_phone'])->first();

        $this->assertNotNull($admin);
        $this->assertTrue((bool) $admin->is_admin);
        $this->assertDatabaseHas('salon_admins', [
            'salon_id' => $salon->id,
            'user_id' => $admin->id,
            'role' => 'owner',
        ]);
    }

    public function test_store_rejects_a_duplicate_slug(): void
    {
        Salon::factory()->create(['slug' => 'taken-slug']);

        $response = $this->actingAs($this->superAdmin)
            ->post('/superadmin/salons', $this->validSalonPayload(['slug' => 'taken-slug']));

        $response->assertSessionHasErrors('slug');
    }

    public function test_store_rejects_admin_phone_already_used_by_another_staff_member(): void
    {
        User::factory()->create(['phone' => '09112223344', 'user_type' => 'staff', 'is_admin' => true]);

        $response = $this->actingAs($this->superAdmin)
            ->post('/superadmin/salons', $this->validSalonPayload(['admin_phone' => '09112223344']));

        $response->assertSessionHasErrors('admin_phone');
    }

    // ---------------------------------------------------------------------
    // ۲) IDOR / کنترل دسترسی — فقط سوپر ادمین
    // ---------------------------------------------------------------------

    public function test_non_super_admin_cannot_reach_any_superadmin_route(): void
    {
        $regularAdmin = User::factory()->create(['is_admin' => true]);
        $salon = Salon::factory()->create();

        $this->actingAs($regularAdmin)->get('/superadmin/dashboard')->assertForbidden();
        $this->actingAs($regularAdmin)->get('/superadmin/salons')->assertForbidden();
        $this->actingAs($regularAdmin)->get('/superadmin/salons/create')->assertForbidden();
        $this->actingAs($regularAdmin)->post('/superadmin/salons', $this->validSalonPayload())->assertForbidden();
        $this->actingAs($regularAdmin)->get("/superadmin/salons/{$salon->id}/edit")->assertForbidden();
        $this->actingAs($regularAdmin)->put("/superadmin/salons/{$salon->id}", [
            'name' => 'x', 'max_specialists_count' => 1,
        ])->assertForbidden();
        $this->actingAs($regularAdmin)
            ->post("/superadmin/salons/{$salon->id}/toggle-suspend")
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_not_shown_any_salon_data(): void
    {
        $salon = Salon::factory()->create();

        $this->get('/superadmin/salons')->assertRedirect(route('login'));
        $this->get("/superadmin/salons/{$salon->id}/edit")->assertRedirect(route('login'));
    }

    public function test_super_admin_dashboard_sees_salons_across_every_salon_without_scoping(): void
    {
        // ⭐ Regression guard for BelongsToSalon's global scope: since CurrentSalon is never set
        // on /superadmin routes, the dashboard must see every salon, not just one.
        $salonA = Salon::factory()->create(['name' => 'سالن آ']);
        $salonB = Salon::factory()->create(['name' => 'سالن ب']);

        $response = $this->actingAs($this->superAdmin)->get('/superadmin/salons');

        $response->assertOk();
        $response->assertSee($salonA->name);
        $response->assertSee($salonB->name);
    }

    // ---------------------------------------------------------------------
    // ۳) انقضا / تمدید / تعلیق اشتراک
    // ---------------------------------------------------------------------

    public function test_creating_a_salon_sets_subscription_end_based_on_type(): void
    {
        $payload = $this->validSalonPayload(['subscription_type' => '3m']);

        $this->actingAs($this->superAdmin)->post('/superadmin/salons', $payload);

        $salon = Salon::where('slug', $payload['slug'])->first();

        $this->assertTrue(
            $salon->subscription_ends_at->isSameDay(now()->addMonths(3))
        );
    }

    public function test_renewing_an_active_subscription_extends_from_its_current_end_date(): void
    {
        $salon = Salon::factory()->create([
            'subscription_type' => '1m',
            'subscription_ends_at' => now()->addDays(10),
        ]);

        $this->actingAs($this->superAdmin)
            ->post("/superadmin/salons/{$salon->id}/renew", ['subscription_type' => '1m']);

        $salon->refresh();

        // از تاریخ انقضای فعلی (نه از الان) به بعد جمع می‌شود — تمدید زودهنگام دور ریخته نمی‌شود.
        $this->assertTrue($salon->subscription_ends_at->isSameDay(now()->addDays(10)->addMonth()));
    }

    public function test_renewing_an_expired_subscription_extends_from_now(): void
    {
        $salon = Salon::factory()->expired()->create(['subscription_type' => '1m']);

        $this->actingAs($this->superAdmin)
            ->post("/superadmin/salons/{$salon->id}/renew", ['subscription_type' => '1m']);

        $salon->refresh();

        $this->assertTrue($salon->subscription_ends_at->isSameDay(now()->addMonth()));
    }

    // ---------------------------------------------------------------------
    // ⭐ فاز ۲، محور «۱. پرداخت آنلاین و صورتحساب» — تمدید دستی حالا هم در invoices ثبت می‌شود
    // ---------------------------------------------------------------------

    public function test_manual_renewal_records_a_paid_manual_invoice(): void
    {
        $salon = Salon::factory()->create([
            'subscription_type' => '1m',
            'subscription_ends_at' => now()->addDays(10),
        ]);

        $this->actingAs($this->superAdmin)
            ->post("/superadmin/salons/{$salon->id}/renew", ['subscription_type' => '3m']);

        $invoice = Invoice::withoutGlobalScope('salon')->where('salon_id', $salon->id)->first();

        $this->assertNotNull($invoice);
        $this->assertSame('paid', $invoice->status);
        $this->assertSame('manual', $invoice->payment_method);
        $this->assertSame('3m', $invoice->subscription_type);
        $this->assertSame($this->superAdmin->id, $invoice->created_by);
        $this->assertNull($invoice->ref_id);
        $this->assertNotNull($invoice->paid_at);
        $this->assertTrue($invoice->period_start->isSameDay(now()->addDays(10)));
        $this->assertTrue($invoice->period_end->isSameDay($salon->fresh()->subscription_ends_at));
    }

    public function test_invoices_page_lists_only_that_salons_invoices(): void
    {
        $salonA = Salon::factory()->create();
        $salonB = Salon::factory()->create();

        Invoice::factory()->paid()->create(['salon_id' => $salonA->id]);
        Invoice::factory()->paid()->create(['salon_id' => $salonB->id]);

        $response = $this->actingAs($this->superAdmin)->get("/superadmin/salons/{$salonA->id}/invoices");

        $response->assertOk();
        $invoices = $response->viewData('invoices');
        $this->assertCount(1, $invoices);
        $this->assertSame($salonA->id, $invoices->first()->salon_id);
    }

    public function test_toggle_suspend_flips_a_regular_salons_status(): void
    {
        $salon = Salon::factory()->create(['is_suspended' => false]);

        $this->actingAs($this->superAdmin)->post("/superadmin/salons/{$salon->id}/toggle-suspend");
        $this->assertTrue($salon->refresh()->is_suspended);

        $this->actingAs($this->superAdmin)->post("/superadmin/salons/{$salon->id}/toggle-suspend");
        $this->assertFalse($salon->refresh()->is_suspended);
    }

    public function test_expired_and_suspended_salons_do_not_have_an_active_subscription(): void
    {
        $expired = Salon::factory()->expired()->create();
        $suspended = Salon::factory()->suspended()->create(['subscription_ends_at' => now()->addMonth()]);
        $healthy = Salon::factory()->create(['is_suspended' => false, 'subscription_ends_at' => now()->addMonth()]);

        $this->assertFalse($expired->hasActiveSubscription());
        $this->assertFalse($suspended->hasActiveSubscription());
        $this->assertTrue($healthy->hasActiveSubscription());
    }

    // ---------------------------------------------------------------------
    // ۴) سقف تعداد متخصص
    // ---------------------------------------------------------------------

    public function test_update_rejects_lowering_the_quota_below_current_specialist_count(): void
    {
        $salon = Salon::factory()->create(['max_specialists_count' => 5]);
        Specialist::factory()->count(3)->create(['salon_id' => $salon->id]);

        $response = $this->actingAs($this->superAdmin)->put("/superadmin/salons/{$salon->id}", [
            'name' => $salon->name,
            'max_specialists_count' => 2,
        ]);

        $response->assertSessionHasErrors('max_specialists_count');
        $this->assertSame(5, $salon->refresh()->max_specialists_count);
    }

    public function test_update_allows_lowering_the_quota_to_exactly_the_current_specialist_count(): void
    {
        $salon = Salon::factory()->create(['max_specialists_count' => 5]);
        Specialist::factory()->count(3)->create(['salon_id' => $salon->id]);

        $response = $this->actingAs($this->superAdmin)->put("/superadmin/salons/{$salon->id}", [
            'name' => $salon->name,
            'max_specialists_count' => 3,
        ]);

        $response->assertRedirect(route('superadmin.salons.index'));
        $this->assertSame(3, $salon->refresh()->max_specialists_count);
    }

    /**
     * ⭐ Regression test for a real bug this test file discovered by actually running against
     * real PHP (see SuperAdminService::updateSalon()'s docblock): the specialist-count query
     * used a plain Specialist::where('salon_id', ...), which still passed through
     * BelongsToSalon's global 'salon' scope. Whenever CurrentSalon happened to be bound to a
     * DIFFERENT salon than the one being updated, the two salon_id filters could never both
     * match, so the count silently came back 0 and the quota guard never fired. This test
     * pins CurrentSalon to an unrelated salon to make that exact scenario explicit, rather
     * than relying on it happening to be true via TestCase's own default binding.
     */
    public function test_quota_guard_still_counts_correctly_when_current_salon_is_set_to_a_different_salon(): void
    {
        $targetSalon = Salon::factory()->create(['max_specialists_count' => 5]);
        Specialist::factory()->count(3)->create(['salon_id' => $targetSalon->id]);

        $unrelatedSalon = Salon::factory()->create();
        app(\App\Support\CurrentSalon::class)->set($unrelatedSalon);

        $response = $this->actingAs($this->superAdmin)->put("/superadmin/salons/{$targetSalon->id}", [
            'name' => $targetSalon->name,
            'max_specialists_count' => 2,
        ]);

        $response->assertSessionHasErrors('max_specialists_count');
        $this->assertSame(5, $targetSalon->refresh()->max_specialists_count);

        app(\App\Support\CurrentSalon::class)->clear();
    }

    public function test_update_allows_raising_the_quota(): void
    {
        $salon = Salon::factory()->create(['max_specialists_count' => 5]);
        Specialist::factory()->count(3)->create(['salon_id' => $salon->id]);

        $this->actingAs($this->superAdmin)->put("/superadmin/salons/{$salon->id}", [
            'name' => $salon->name,
            'max_specialists_count' => 10,
        ]);

        $this->assertSame(10, $salon->refresh()->max_specialists_count);
    }

    // ---------------------------------------------------------------------
    // پیگیری «محور ۳» (۲۰۲۶-۰۹-۲۰): tagline/bio — متن‌های بازاریابی per-salon
    // ---------------------------------------------------------------------

    public function test_super_admin_can_set_tagline_and_bio_on_a_new_salon(): void
    {
        $payload = $this->validSalonPayload([
            'slug' => 'new-salon-tagline-test',
            'tagline' => 'یک شعار کوتاه',
            'bio' => 'یک معرفی کامل از سالن.',
        ]);

        $this->actingAs($this->superAdmin)->post('/superadmin/salons', $payload);

        $this->assertDatabaseHas('salons', [
            'slug' => 'new-salon-tagline-test',
            'tagline' => 'یک شعار کوتاه',
            'bio' => 'یک معرفی کامل از سالن.',
        ]);
    }

    public function test_super_admin_can_update_tagline_and_bio(): void
    {
        $salon = Salon::factory()->create();

        $this->actingAs($this->superAdmin)->put("/superadmin/salons/{$salon->id}", [
            'name' => $salon->name,
            'max_specialists_count' => $salon->max_specialists_count,
            'tagline' => 'شعار به‌روزشده',
            'bio' => 'معرفی به‌روزشده.',
        ]);

        $salon->refresh();
        $this->assertSame('شعار به‌روزشده', $salon->tagline);
        $this->assertSame('معرفی به‌روزشده.', $salon->bio);
    }

    /**
     * ⭐ هم‌الگو با zarinpal_merchant_id — فرستادن مقدار خالی باید واقعاً پاک کنه، نه بی‌اثر بمونه.
     */
    public function test_super_admin_can_clear_tagline_and_bio_by_sending_empty_values(): void
    {
        $salon = Salon::factory()->create(['tagline' => 'قدیمی', 'bio' => 'قدیمی']);

        $this->actingAs($this->superAdmin)->put("/superadmin/salons/{$salon->id}", [
            'name' => $salon->name,
            'max_specialists_count' => $salon->max_specialists_count,
            'tagline' => '',
            'bio' => '',
        ]);

        $salon->refresh();
        $this->assertNull($salon->tagline);
        $this->assertNull($salon->bio);
    }

    public function test_update_without_tagline_and_bio_keys_leaves_them_unchanged(): void
    {
        $salon = Salon::factory()->create(['tagline' => 'دست‌نخورده', 'bio' => 'دست‌نخورده']);

        $this->actingAs($this->superAdmin)->put("/superadmin/salons/{$salon->id}", [
            'name' => $salon->name,
            'max_specialists_count' => $salon->max_specialists_count,
        ]);

        $salon->refresh();
        $this->assertSame('دست‌نخورده', $salon->tagline);
        $this->assertSame('دست‌نخورده', $salon->bio);
    }

    // ---------------------------------------------------------------------
    // ۵) مصونیت سالن پیش‌فرض (rasta) از تعلیق
    // ---------------------------------------------------------------------

    public function test_default_salon_cannot_be_suspended(): void
    {
        $defaultSalon = Salon::where('slug', 'rasta')->first();
        $this->assertNotNull($defaultSalon, 'Default salon "rasta" must exist (backfilled by migration).');

        $response = $this->actingAs($this->superAdmin)
            ->post("/superadmin/salons/{$defaultSalon->id}/toggle-suspend");

        $response->assertSessionHasErrors('error');
        $this->assertFalse($defaultSalon->refresh()->is_suspended);
    }

    public function test_non_default_salon_named_rasta_like_but_different_slug_can_still_be_suspended(): void
    {
        // ⭐ Guard against an over-broad immunity check (e.g. matching on name instead of slug).
        $salon = Salon::factory()->create(['name' => 'راستا دوم', 'slug' => 'rasta-2', 'is_suspended' => false]);

        $this->actingAs($this->superAdmin)->post("/superadmin/salons/{$salon->id}/toggle-suspend");

        $this->assertTrue($salon->refresh()->is_suspended);
    }
}
