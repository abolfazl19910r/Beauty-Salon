<?php

namespace Tests\Feature\Auth;

use App\Models\Specialist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * رگرسیون: متخصص بعد از ورود وب به /dashboard مشتری می‌رفت، چون مقصد فقط از نقش «specialist»
 * خوانده می‌شد که در عمل به هیچ متخصصی داده نمی‌شود.
 */
class SpecialistLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function loginThroughOtp(User $user): \Illuminate\Testing\TestResponse
    {
        $this->post('/login', ['phone' => $user->phone, 'password' => 'password'])->assertRedirect(route('login.verify.show'));

        return $this->post('/login/verify', ['code' => $user->fresh()->login_verification_code]);
    }

    public function test_specialist_lands_on_specialist_panel_after_login(): void
    {
        $user = User::find(Specialist::factory()->create()->user_id);

        $this->loginThroughOtp($user)->assertRedirect('/my-dashboard');
        $this->assertAuthenticatedAs($user);

        // صفحه‌ی مهمان (/login) هم برای متخصصِ واردشده باید به همان پنل برگردد
        $this->get('/login')->assertRedirect('/my-dashboard');
    }

    public function test_salon_admin_still_goes_to_admin_panel_even_if_also_a_specialist(): void
    {
        $user = User::find(Specialist::factory()->create()->user_id);
        $user->forceFill(['is_admin' => true])->save();

        $this->loginThroughOtp($user)->assertRedirect('/admin/dashboard');
    }

    public function test_staff_without_specialist_record_keeps_old_destination(): void
    {
        $user = User::factory()->create(['user_type' => 'staff', 'password' => bcrypt('password')]);

        $this->loginThroughOtp($user)->assertRedirect('/dashboard');
    }
}
