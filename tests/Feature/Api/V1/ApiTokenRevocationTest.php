<?php

namespace Tests\Feature\Api\V1;

use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Services\Admin\Specialist\AdminSpecialistService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * ابطال خودکار توکن‌های اپ (بسته‌ی ۱ اپلیکیشن): تغییر رمز، تغییر نوع حساب، حذف متخصص، حذف کاربر؛ و سالن
 * غیرفعال که توکن را نگه می‌دارد ولی salon_inactive می‌دهد.
 */
class ApiTokenRevocationTest extends TestCase
{
    use RefreshDatabase;

    protected function customer(): User
    {
        return User::factory()->create(['user_type' => 'customer', 'salon_id' => app(CurrentSalon::class)->id()]);
    }

    protected function tokenFor(User $user, string $audience): string
    {
        return $user->createToken('phone', [$audience], now()->addDays(90))->plainTextToken;
    }

    public function test_password_change_revokes_every_token_of_that_user_only(): void
    {
        $user = $this->customer();
        $other = $this->customer();
        $this->tokenFor($user, 'customer');
        $this->tokenFor($user, 'customer');
        $this->tokenFor($other, 'customer');

        $user->update(['name' => 'نام تازه']);
        $this->assertSame(2, $user->tokens()->count(), 'other fields keep the tokens');

        $user->forceFill(['password' => Hash::make('new-password-1')])->save();

        $this->assertSame(0, $user->tokens()->count());
        $this->assertSame(1, $other->tokens()->count());
    }

    public function test_password_change_through_the_web_profile_logs_the_apps_out(): void
    {
        $user = $this->customer();
        $user->forceFill(['password' => Hash::make('old-password-1')])->save();
        $plain = $this->tokenFor($user, 'customer');

        $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'old-password-1',
            'password' => 'Brand-New-Pass-77',
            'password_confirmation' => 'Brand-New-Pass-77',
        ])->assertSessionHasNoErrors();

        $this->app['auth']->forgetGuards();
        $this->withToken($plain)->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_promoting_a_customer_to_staff_revokes_the_customer_app_tokens(): void
    {
        $user = $this->customer();
        $this->tokenFor($user, 'customer');

        $user->update(['user_type' => 'staff']);

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_deleting_a_specialist_revokes_the_staff_app_tokens(): void
    {
        $specialist = Specialist::factory()->create();
        $user = User::find($specialist->user_id);
        $plain = $this->tokenFor($user, 'staff');

        $this->withToken($plain)->getJson('/api/v1/me')->assertOk();

        app(AdminSpecialistService::class)->delete($specialist);

        $this->assertSoftDeleted($specialist);
        $this->assertSame(0, $user->tokens()->count());
        $this->app['auth']->forgetGuards();
        $this->withToken($plain)->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_deleting_a_user_removes_its_tokens(): void
    {
        $user = $this->customer();
        $this->tokenFor($user, 'customer');

        $user->delete();

        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id, 'tokenable_type' => User::class]);
    }

    public function test_suspended_salon_blocks_the_token_without_deleting_it(): void
    {
        $salon = Salon::factory()->create();
        $user = User::factory()->create(['user_type' => 'customer', 'salon_id' => $salon->id]);
        $plain = $this->tokenFor($user, 'customer');

        $salon->update(['is_suspended' => true]);

        $this->withToken($plain)->getJson('/api/v1/me')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'salon_inactive');
        $this->assertSame(1, $user->tokens()->count());

        $salon->update(['is_suspended' => false]);
        $this->app['auth']->forgetGuards();
        $this->withToken($plain)->getJson('/api/v1/me')->assertOk();
    }

    public function test_expired_subscription_blocks_the_staff_app_too(): void
    {
        $salon = Salon::factory()->create();
        $user = app(CurrentSalon::class)->withSalon($salon, fn () => User::find(Specialist::factory()->create()->user_id));
        $plain = $this->tokenFor($user, 'staff');

        $salon->update(['subscription_ends_at' => now()->subDay()]);

        $this->withToken($plain)->getJson('/api/v1/me')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'salon_inactive');
    }
}
