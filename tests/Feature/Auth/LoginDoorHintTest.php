<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * آزمون ویندوز بسته‌ی ۴ (۲۰۲۶-۱۱-۲۵): کارکنان از /login و مشتری از /s/{slug}/login وارد می‌شوند؛ کسی که از صفحه‌ی دیگر
 * می‌آمد فقط «اطلاعات وارد شده صحیح نمی‌باشد» می‌دید. حالا با رمز درستِ حساب نوع دیگر، لینک صفحه‌ی درست را می‌بیند —
 * و فقط با رمز درست (شماره‌ی تنها چیزی لو نمی‌دهد).
 */
class LoginDoorHintTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_on_the_salon_login_page_gets_a_link_to_staff_login(): void
    {
        $salon = app(CurrentSalon::class)->get();
        User::factory()->create(['user_type' => 'staff', 'salon_id' => null, 'phone' => '09121230001', 'password' => Hash::make('secret-1')]);
        $page = route('salon.login', ['salon_slug' => $salon->slug]);

        $this->from($page)->post($page, ['phone' => '09121230001', 'password' => 'secret-1'])
            ->assertRedirect($page)->assertSessionHas('login_door_hint', fn ($h) => str_contains($h['text'], 'کارکنان'));
        $this->assertGuest();
        $this->get($page)->assertSee('data-login-door-hint', false)->assertSee('رفتن به ورود کارکنان');

        $this->from($page)->post($page, ['phone' => '09121230001', 'password' => 'wrong'])
            ->assertSessionMissing('login_door_hint')->assertSessionHasErrors('phone');
    }

    public function test_customer_on_the_staff_login_page_gets_a_link_to_their_salon(): void
    {
        $salon = app(CurrentSalon::class)->get();
        User::factory()->create(['user_type' => 'customer', 'salon_id' => $salon->id, 'phone' => '09121230002', 'password' => Hash::make('secret-2')]);

        $this->from(route('login'))->post(route('login'), ['phone' => '09121230002', 'password' => 'secret-2'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('login_door_hint', fn ($h) => $h['url'] === route('salon.login', ['salon_slug' => $salon->slug]) && str_contains($h['text'], $salon->name));
        $this->assertGuest();

        $this->from(route('login'))->post(route('login'), ['phone' => '09121230002', 'password' => 'nope'])
            ->assertSessionMissing('login_door_hint')->assertSessionHasErrors('phone');
    }

    public function test_both_login_pages_point_to_each_other(): void
    {
        $salon = app(CurrentSalon::class)->get();
        $this->get(route('login'))->assertOk()->assertSee('data-customer-door', false);
        $this->get(route('salon.login', ['salon_slug' => $salon->slug]))->assertOk()->assertSee('data-staff-door', false)->assertSee('ورود کارکنان');
    }
}
