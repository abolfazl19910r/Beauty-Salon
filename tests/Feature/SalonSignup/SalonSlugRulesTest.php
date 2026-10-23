<?php

namespace Tests\Feature\SalonSignup;

use App\Models\Role;
use App\Models\Salon;
use App\Models\User;
use App\Support\SalonSubdomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\SalonContactPayload;
use Tests\TestCase;

/**
 * slug سالن برچسب DNS هم هست (ساب‌دامین {slug}.دامنه؛ بسته‌ی Docker ۲۰۲۶-۱۰-۲۳): قبلاً alpha_dash بود که زیرخط، حروف بزرگ و
 * یونیکد را می‌پذیرفت، سقف ۱۰۰ داشت و www/admin/api قابل ثبت بودند.
 */
class SalonSlugRulesTest extends TestCase
{
    use RefreshDatabase;
    use SalonContactPayload;

    private function passes(string $slug): bool
    {
        return Validator::make(['slug' => $slug], ['slug' => SalonSubdomain::rules()])->passes();
    }

    public function test_only_dns_safe_slugs_pass(): void
    {
        foreach (['almas', 'almas-beauty', 'a', 'salon24', str_repeat('a', 63)] as $ok) {
            $this->assertTrue($this->passes($ok), $ok);
        }

        foreach (['almas_beauty', 'Almas', '-almas', 'almas-', 'سالن', 'almas beauty', 'almas.beauty', str_repeat('a', 64), 'www', 'admin', 'api', 'superadmin'] as $bad) {
            $this->assertFalse($this->passes($bad), $bad);
        }
    }

    public function test_check_slug_lowercases_and_reports_reserved_names(): void
    {
        $this->getJson(route('salon-signup.check-slug', ['slug' => ' Almas-Beauty ']))->assertJson(['available' => true]);
        $this->getJson(route('salon-signup.check-slug', ['slug' => 'www']))->assertJson(['available' => false, 'reason' => 'reserved']);
        $this->getJson(route('salon-signup.check-slug', ['slug' => 'almas_beauty']))->assertJson(['available' => false, 'reason' => 'invalid']);

        Salon::factory()->create(['slug' => 'almas']);
        $this->getJson(route('salon-signup.check-slug', ['slug' => 'ALMAS']))->assertJson(['available' => false, 'reason' => 'taken']);
    }

    public function test_public_signup_lowercases_the_slug_and_rejects_reserved_ones(): void
    {
        $payload = array_merge([
            'name' => 'سالن الماس', 'subscription_type' => '1m', 'specialists_count' => 3,
            'owner_name' => 'سارا', 'owner_phone' => '09121234567',
            'owner_password' => 'Str0ng!Passw0rd', 'owner_password_confirmation' => 'Str0ng!Passw0rd',
        ], $this->salonContactPayload());

        $this->post(route('salon-signup.store'), $payload + ['slug' => 'admin'])->assertSessionHasErrors(['slug' => 'این آدرس رزرو شده است؛ آدرس دیگری انتخاب کنید.']);
        $this->post(route('salon-signup.store'), $payload + ['slug' => 'Almas_1'])->assertSessionHasErrors('slug');
        $this->post(route('salon-signup.store'), $payload + ['slug' => 'Almas-Beauty'])->assertSessionDoesntHaveErrors('slug');
    }

    public function test_super_admin_form_uses_the_same_rules(): void
    {
        $super = User::factory()->create(['is_admin' => true]);
        $super->roles()->attach(Role::firstOrCreate(['name' => 'super-admin'], ['label' => 'سوپر ادمین']));
        $super->salons()->detach();

        $this->actingAs($super)->post(route('superadmin.salons.store'), ['slug' => 'www', 'name' => 'x'])
            ->assertSessionHasErrors('slug');
        $this->actingAs($super)->post(route('superadmin.salons.store'), ['slug' => 'rasta_2', 'name' => 'x'])
            ->assertSessionHasErrors('slug');
    }
}
