<?php

namespace Tests\Feature\Legal;

use App\Models\LegalAcceptance;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * پذیرش دوباره‌ی قوانین سالن‌ها در پنل مدیر (۲۰۲۶-۱۰-۲۸): مالک تا تغییر مهم را نپذیرد پنلش قفل است (فقط همین صفحه، پشتیبانی و
 * خروج)؛ منشی قفل نمی‌شود و نوار «منتظر پذیرش مالک» می‌بیند؛ رزرو عمومی سالن ادامه دارد. ثبت با salon_id همین سالن.
 */
class SalonTermsReacceptanceTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create(['is_admin' => true]);
    }

    private function majorSalonTermsChange(): void
    {
        config(['legal.versions.salon_terms' => '1405-09-01', 'legal.changes.salon_terms.1405-09-01' => ['major' => true, 'summary' => 'کارمزد درگاه عوض شد']]);
    }

    private function secretary(): User
    {
        $this->actingAs($this->owner)->post('/admin/users', [
            'name' => 'منشی', 'phone' => '09120004444', 'salon_role' => 'staff', 'password' => 'Str0ng!Passw0rd', 'password_confirmation' => 'Str0ng!Passw0rd',
        ]);

        return User::where('phone', '09120004444')->sole();
    }

    public function test_without_a_major_change_the_panel_is_open(): void
    {
        $this->actingAs($this->owner)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_the_owner_panel_is_locked_until_accepted(): void
    {
        $this->majorSalonTermsChange();
        $this->actingAs($this->owner);

        $this->get(route('admin.bookings.index'))->assertRedirect(route('admin.terms-update.show'));
        $this->post(route('admin.services.store'), [])->assertRedirect(route('admin.terms-update.show'));
        $this->getJson(route('admin.bookings.index'))->assertStatus(409)->assertJsonPath('code', 'terms_update_required');
        $this->get(route('admin.support-tickets.index'))->assertOk();

        $this->get(route('admin.terms-update.show'))->assertOk()
            ->assertSee('کارمزد درگاه عوض شد')->assertSee('data-pending-document="salon_terms"', false)
            ->assertSee('/terms#salons', false)->assertSee('data-terms-decline', false);
    }

    public function test_accepting_records_salon_terms_for_this_salon_and_returns(): void
    {
        $this->majorSalonTermsChange();
        $salon = app(CurrentSalon::class)->get();
        $this->actingAs($this->owner)->get(route('admin.bookings.index'));

        $this->post(route('admin.terms-update.accept'), [])->assertSessionHasErrors('accept_terms');
        $this->withServerVariables(['REMOTE_ADDR' => '5.6.7.12'])
            ->post(route('admin.terms-update.accept'), ['accept_terms' => '1'])->assertRedirect(route('admin.bookings.index'));

        $row = LegalAcceptance::where('document', 'salon_terms')->where('version', '1405-09-01')->sole();
        $this->assertSame($salon->id, $row->salon_id);
        $this->assertSame($this->owner->id, $row->user_id);
        $this->assertSame('5.6.7.12', $row->ip_address);
        $this->assertSame('web', $row->source);

        $this->get(route('admin.bookings.index'))->assertOk();
    }

    public function test_the_secretary_is_not_locked_and_sees_the_banner_and_public_booking_continues(): void
    {
        $secretary = $this->secretary();
        $this->majorSalonTermsChange();

        $this->actingAs($secretary)->get(route('admin.bookings.index'))->assertOk()->assertSee('data-terms-awaiting-owner', false);

        auth()->logout();
        $this->get(route('home'))->assertOk();
    }

    public function test_another_owner_of_the_same_salon_accepting_unlocks_the_salon_terms(): void
    {
        $this->majorSalonTermsChange();
        $salon = app(CurrentSalon::class)->get();
        LegalAcceptance::create([
            'user_id' => User::factory()->create()->id, 'salon_id' => $salon->id, 'document' => 'salon_terms', 'version' => '1405-09-01',
            'source' => 'web', 'accepted_at' => now(),
        ]);

        $this->actingAs($this->owner)->get(route('admin.bookings.index'))->assertOk();
    }
}
