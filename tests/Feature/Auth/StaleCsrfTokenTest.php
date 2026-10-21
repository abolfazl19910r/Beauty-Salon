<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ۴۱۹ با توکن CSRF کهنه (گزارش ۲۰۲۶-۱۰-۲۱، سایت نصب‌شده روی آیفون): خروج همیشه خارج می‌کند؛ فرم‌های دیگر با پیام برمی‌گردند.
 * VerifyCsrfToken در تست خاموش است (runningUnitTests)؛ اینجا محیط برای همین درخواست‌ها «local» می‌شود تا واقعاً بررسی شود.
 */
class StaleCsrfTokenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['env'] = 'local';
    }

    public function test_customer_logout_with_a_stale_token_still_logs_out(): void
    {
        $salon = app(CurrentSalon::class)->get();
        $customer = User::factory()->create(['salon_id' => $salon->id, 'user_type' => 'customer']);

        $this->actingAs($customer)->post('/s/'.$salon->slug.'/logout', ['_token' => 'kohne'])
            ->assertRedirect(route('home', ['salon_slug' => $salon->slug]));
        $this->assertGuest();
    }

    public function test_staff_logout_with_a_stale_token_still_logs_out(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))->post('/logout', ['_token' => 'kohne'])
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_other_forms_return_with_a_message_instead_of_a_419_page(): void
    {
        $salon = app(CurrentSalon::class)->get();

        $this->from('/s/'.$salon->slug.'/login')
            ->post('/s/'.$salon->slug.'/login', ['_token' => 'kohne', 'phone' => '09120000000', 'password' => 'x'])
            ->assertRedirect('/s/'.$salon->slug.'/login')
            ->assertSessionHas('error')
            ->assertSessionHasInput('phone', '09120000000')
            ->assertSessionMissing('_old_input.password');
    }

    public function test_json_requests_keep_the_419_status(): void
    {
        $this->postJson('/logout', ['_token' => 'kohne'])->assertStatus(419);
    }
}
