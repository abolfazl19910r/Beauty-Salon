<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * گزارش ۲۰۲۶-۱۰-۱۷: مشتری بعد از ورود در صفحه‌ی سالن به admin/notifications/count (۴۰۳) می‌رفت، چون شمارنده‌ی اعلان پنل مدیر
 * در همان مرورگر، بعد از تمام شدن نشست، آدرس خودش را «مقصد بعد از ورود» ذخیره کرده بود.
 */
class CustomerLoginIntendedTest extends TestCase
{
    use RefreshDatabase;

    private function verifyWithIntended(?string $intended)
    {
        $salon = app(CurrentSalon::class)->get();
        $customer = User::factory()->create([
            'salon_id' => $salon->id, 'user_type' => 'customer', 'phone' => '09121110000',
            'login_verification_code' => '123456', 'login_verification_code_expire_at' => now()->addMinutes(2),
        ]);

        $session = ['customer_login_user_id' => $customer->id];
        if ($intended !== null) {
            $session['url.intended'] = $intended;
        }

        return [$salon, $this->withSession($session)->post(route('salon.login.verify', ['salon_slug' => $salon->slug]), ['code' => '123456'])];
    }

    public function test_panel_address_left_in_the_session_is_ignored(): void
    {
        [$salon, $response] = $this->verifyWithIntended(url('/admin/notifications/count'));

        $response->assertRedirect(route('home', ['salon_slug' => $salon->slug]));
        $this->assertAuthenticated();
        $this->assertNull(session('url.intended'));
    }

    public function test_other_salon_and_other_host_are_ignored(): void
    {
        [$salon, $response] = $this->verifyWithIntended(url('/s/another-salon/booking'));
        $response->assertRedirect(route('home', ['salon_slug' => $salon->slug]));
    }

    public function test_page_of_the_same_salon_is_kept(): void
    {
        $salon = app(CurrentSalon::class)->get();
        $target = url('/s/'.$salon->slug.'/booking');
        [, $response] = $this->verifyWithIntended($target);

        $response->assertRedirect($target);
    }

    public function test_staff_login_clears_a_stale_intended_address(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true, 'login_verification_code' => '654321', 'login_verification_code_expire_at' => now()->addMinutes(2),
        ]);

        $this->withSession(['login_user_id' => $admin->id, 'url.intended' => url('/admin/notifications/count')])
            ->post(route('login.verify'), ['code' => '654321'])->assertRedirect();

        $this->assertNull(session('url.intended'));
    }

    public function test_notification_counter_polling_does_not_store_an_intended_address(): void
    {
        $this->getJson('/admin/notifications/count')->assertStatus(401);
        $this->assertNull(session('url.intended'));
    }

    public function test_admin_counter_sends_json_headers(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $html = $this->actingAs($admin)->get('/admin/notification-settings')->getContent();
        $this->assertMatchesRegularExpression("/fetch\\(window\\.AdminRoutes\\.notificationsCount, \\{.*?'Accept': 'application\\/json'/s", $html);
    }
}
