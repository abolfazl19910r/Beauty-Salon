<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Role;
use App\Models\Salon;
use App\Models\User;
use App\Services\Sms\SmsQuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * سهمیه‌ی پیامک اختصاصی هر سالن (۲۰۲۶-۰۹-۳۰): ستون salons.sms_quota_per_month از قبل بود ولی هیچ فرمی نداشت. سوپرادمین
 * در ویرایش سالن عددی (بر حسب قطعه) می‌گذارد یا خالی می‌گذارد تا پیش‌فرض SMS_QUOTA_PER_MONTH اعمال شود.
 */
class SalonSmsQuotaFieldTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $user = User::factory()->create(['is_admin' => true]);
        $user->roles()->attach(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));

        return $user;
    }

    private function update(Salon $salon, array $extra): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->superAdmin())->put("/superadmin/salons/{$salon->id}", [
            'name' => $salon->name,
            'max_specialists_count' => $salon->max_specialists_count,
        ] + $extra);
    }

    public function test_the_edit_form_shows_the_field_with_the_default_as_hint(): void
    {
        $salon = Salon::factory()->create(['sms_quota_per_month' => 3500]);

        $this->actingAs($this->superAdmin())->get("/superadmin/salons/{$salon->id}/edit")
            ->assertOk()
            ->assertSee('name="sms_quota_per_month"', false)
            ->assertSee('value="3500"', false);
    }

    public function test_a_custom_quota_is_saved_and_used(): void
    {
        $salon = Salon::factory()->create(['sms_quota_per_month' => null]);

        $this->update($salon, ['sms_quota_per_month' => '3500'])->assertRedirect(route('superadmin.salons.index'));

        $this->assertSame(3500, app(SmsQuotaService::class)->quotaFor($salon->fresh()));
    }

    public function test_an_empty_field_returns_the_salon_to_the_default(): void
    {
        $salon = Salon::factory()->create(['sms_quota_per_month' => 3500]);

        $this->update($salon, ['sms_quota_per_month' => ''])->assertRedirect(route('superadmin.salons.index'));

        $this->assertNull($salon->fresh()->sms_quota_per_month);
        $this->assertSame((int) config('billing.sms_quota_per_month'), app(SmsQuotaService::class)->quotaFor($salon->fresh()));
    }

    public function test_a_form_without_the_field_leaves_the_quota_alone(): void
    {
        $salon = Salon::factory()->create(['sms_quota_per_month' => 3500]);

        $this->update($salon, [])->assertRedirect(route('superadmin.salons.index'));

        $this->assertSame(3500, (int) $salon->fresh()->sms_quota_per_month);
    }

    public function test_a_negative_quota_is_rejected(): void
    {
        $salon = Salon::factory()->create(['sms_quota_per_month' => 3500]);

        $this->update($salon, ['sms_quota_per_month' => '-5'])->assertSessionHasErrors('sms_quota_per_month');
    }
}
