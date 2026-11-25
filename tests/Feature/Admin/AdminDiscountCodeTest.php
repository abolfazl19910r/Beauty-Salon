<?php

namespace Tests\Feature\Admin;

use App\Models\DiscountCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDiscountCodeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_can_create_a_discount_code(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/discount-codes', [
            'code' => 'WELCOME10',
            'type' => 'percentage',
            'amount' => 10,
            'max_uses' => 50,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('discount_codes', ['code' => 'WELCOME10', 'used_count' => 0, 'is_active' => 1]);
    }

    public function test_code_is_normalized_to_uppercase(): void
    {
        $this->actingAs($this->admin)->post('/admin/discount-codes', [
            'code' => 'lowercase-code',
            'type' => 'fixed',
            'amount' => 10000,
            'max_uses' => 10,
        ]);

        $this->assertDatabaseHas('discount_codes', ['code' => 'LOWERCASE-CODE']);
    }

    public function test_percentage_amount_over_100_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)
            ->from('/admin/discount-codes/create')
            ->post('/admin/discount-codes', [
                'code' => 'TOOBIG',
                'type' => 'percentage',
                'amount' => 150,
                'max_uses' => 10,
            ]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseMissing('discount_codes', ['code' => 'TOOBIG']);
    }

    public function test_fixed_amount_over_100_is_allowed(): void
    {
        // Regression guard: MaxPercentage must only apply conditionally to percentage-type
        // codes — a fixed discount of e.g. 500,000 tomans is completely valid and must not be
        // rejected by a percentage-only rule (documented R-AdminForms regression).
        $response = $this->actingAs($this->admin)->post('/admin/discount-codes', [
            'code' => 'BIGFIXED',
            'type' => 'fixed',
            'amount' => 500000,
            'max_uses' => 10,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('discount_codes', ['code' => 'BIGFIXED']);
    }

    public function test_duplicate_code_is_rejected(): void
    {
        DiscountCode::factory()->create(['code' => 'EXISTING']);

        $response = $this->actingAs($this->admin)
            ->from('/admin/discount-codes/create')
            ->post('/admin/discount-codes', [
                'code' => 'EXISTING',
                'type' => 'fixed',
                'amount' => 10000,
                'max_uses' => 10,
            ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_non_admin_cannot_access_the_discount_code_panel(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->get('/admin/discount-codes');

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_away_from_the_discount_code_panel(): void
    {
        $response = $this->get('/admin/discount-codes');

        $response->assertRedirect(route('login'));
    }

    // ── preview() ────────────────────────────────────────────────────────

    public function test_preview_endpoint_returns_the_calculated_discount(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/discount-codes/preview?'.http_build_query([
            'type' => 'percentage',
            'amount' => 20,
            'base_amount' => 100000,
        ]));

        $response->assertOk();
        $response->assertJson(['discount_amount' => 20000, 'final_amount' => 80000]);
    }

    public function test_preview_endpoint_respects_max_amount_cap(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/discount-codes/preview?'.http_build_query([
            'type' => 'percentage',
            'amount' => 50,
            'max_amount' => 30000,
            'base_amount' => 500000,
        ]));

        $response->assertOk();
        $response->assertJson(['discount_amount' => 30000]);
    }

    // ── destroy() ────────────────────────────────────────────────────────

    public function test_an_unused_discount_code_can_be_deleted(): void
    {
        $code = DiscountCode::factory()->create(['used_count' => 0]);

        $response = $this->actingAs($this->admin)->delete("/admin/discount-codes/{$code->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('discount_codes', ['id' => $code->id]);
    }

    public function test_a_used_discount_code_cannot_be_deleted(): void
    {
        // Financial record integrity: a code that has already been applied to at least one
        // booking must never be hard-deleted — only deactivated.
        $code = DiscountCode::factory()->create(['used_count' => 3]);

        $response = $this->actingAs($this->admin)->delete("/admin/discount-codes/{$code->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('discount_codes', ['id' => $code->id]);
    }

    // ── update() ─────────────────────────────────────────────────────────

    public function test_admin_can_deactivate_a_discount_code(): void
    {
        $code = DiscountCode::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)->put("/admin/discount-codes/{$code->id}", [
            'is_active' => false,
            'max_uses' => $code->max_uses,
        ]);

        $response->assertRedirect();
        $this->assertSame(0, (int) $code->fresh()->is_active);
    }

    public function test_update_cannot_change_the_code_type_or_amount(): void
    {
        // UpdateDiscountCodeRequest deliberately only accepts is_active/expires_at/max_uses so
        // that a code's discount math can never silently change after bookings already
        // reference it.
        $code = DiscountCode::factory()->create([
            'type' => 'percentage', 'amount' => 10, 'is_active' => true, 'max_uses' => 5,
        ]);

        $this->actingAs($this->admin)->put("/admin/discount-codes/{$code->id}", [
            'is_active' => true,
            'max_uses' => 20,
            'type' => 'fixed', // attempted tampering, must be ignored
            'amount' => 999999, // attempted tampering, must be ignored
        ]);

        $fresh = $code->fresh();
        $this->assertSame('percentage', $fresh->type);
        $this->assertSame(10.0, (float) $fresh->amount);
        $this->assertSame(20, $fresh->max_uses);
    }

    public function test_show_on_site_is_off_by_default_and_never_on_for_a_personal_code(): void
    {
        $base = ['type' => 'percentage', 'amount' => 10, 'max_uses' => 5];
        $this->actingAs($this->admin)->post('/admin/discount-codes', $base + ['code' => 'quiet']);
        $this->assertFalse(\App\Models\DiscountCode::where('code', 'QUIET')->first()->show_on_site);

        $this->actingAs($this->admin)->post('/admin/discount-codes', $base + ['code' => 'site', 'show_on_site' => '1']);
        $this->assertTrue(\App\Models\DiscountCode::where('code', 'SITE')->first()->show_on_site);

        $customer = User::factory()->create();
        $mine = app(\App\Services\Admin\DiscountCode\AdminDiscountCodeService::class)
            ->store($base + ['code' => 'mine', 'show_on_site' => true, 'user_id' => $customer->id]);
        $this->assertFalse($mine->fresh()->show_on_site, 'کد شخصی هیچ‌وقت در سایت نیست');
    }

    public function test_show_on_site_can_be_toggled_on_edit(): void
    {
        $code = \App\Models\DiscountCode::factory()->create(['user_id' => null, 'show_on_site' => false, 'is_active' => true]);

        $this->actingAs($this->admin)->put(route('admin.discount-codes.update', $code), ['is_active' => '1', 'show_on_site' => '1']);
        $this->assertTrue($code->fresh()->show_on_site);
        $this->actingAs($this->admin)->get(route('admin.discount-codes.edit', $code))->assertOk()->assertSee('نمایش در سایت سالن');

        $this->actingAs($this->admin)->put(route('admin.discount-codes.update', $code), ['is_active' => '1', 'show_on_site' => '0']);
        $this->assertFalse($code->fresh()->show_on_site);
    }

    public function test_shown_on_site_scope_keeps_only_live_public_flagged_codes(): void
    {
        $live = \App\Models\DiscountCode::factory()->create(['code' => 'LIVE', 'user_id' => null, 'show_on_site' => true, 'is_active' => true, 'max_uses' => 5, 'used_count' => 0, 'expires_at' => now()->addDay()]);
        \App\Models\DiscountCode::factory()->create(['code' => 'OFF', 'user_id' => null, 'show_on_site' => false, 'is_active' => true, 'max_uses' => 5, 'used_count' => 0]);
        \App\Models\DiscountCode::factory()->create(['code' => 'USED', 'user_id' => null, 'show_on_site' => true, 'is_active' => true, 'max_uses' => 5, 'used_count' => 5]);
        \App\Models\DiscountCode::factory()->create(['code' => 'OLD', 'user_id' => null, 'show_on_site' => true, 'is_active' => true, 'max_uses' => 5, 'used_count' => 0, 'expires_at' => now()->subDay()]);
        \App\Models\DiscountCode::factory()->create(['code' => 'DEAD', 'user_id' => null, 'show_on_site' => true, 'is_active' => false, 'max_uses' => 5, 'used_count' => 0]);

        $this->assertSame(['LIVE'], \App\Models\DiscountCode::query()->shownOnSite()->pluck('code')->all());
    }
}
