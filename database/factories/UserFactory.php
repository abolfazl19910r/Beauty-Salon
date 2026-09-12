<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('09#########'),
            'phone_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'is_admin' => false,
            // ⭐ Fix (found sweeping the remaining test failures for the /s/{slug} migration):
            // User deliberately isn't a BelongsToSalon model (see that trait's docblock — admin
            // and specialist rows would break under a blanket salon_id filter), so it has no
            // auto-fill-on-create hook of its own. Every ordinary customer User this factory
            // makes was silently getting salon_id = null, while everything else in a given test
            // (the admin, the specialist, any booking) correctly defaults to the bound
            // CurrentSalon (see TestCase::setUp()). Any admin-side code that scopes customers by
            // salon_id explicitly (e.g. AdminBookingCustomerController::search(), which can't rely
            // on a model-level global scope for the same reason) then legitimately found zero
            // matches. Explicit `salon_id` overrides passed to create([...]) still win as normal.
            'salon_id' => app(\App\Support\CurrentSalon::class)->id(),
            'verification_code' => null,
            'verification_code_expire_at' => null,
            'login_verification_code' => null,
            'login_verification_code_expire_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
        ]);
    }

    /**
     * ⭐ SaaS multi-tenant (rediscovered 2026-09-10, same root cause as the UserSeeder fix — see
     * that file's docblock): EnsureAdminSalonActive requires every non-super-admin to have a row
     * in salon_admins, or it force-logs them out of every /admin/* request with "اشتراک سالن شما
     * پایان یافته یا غیرفعال شده است". Before this, ~40 Feature/Admin/* test files that create
     * their admin with plain `User::factory()->create(['is_admin' => true])` (no salon link, the
     * pre-SaaS convention from when hasPermission()'s is_admin bypass was still what mattered)
     * all failed with exactly that error the instant they hit any /admin/* route — confirmed by
     * actually running the full suite, not just by reading test code.
     *
     * afterCreating() (not the definition()/creating() event) is used deliberately: it runs once
     * the row + any role-assigning factory states have already applied, so hasRole('super-admin')
     * below is reliable. Guards, in order:
     * - is_admin must actually be true (never touches ordinary customer users)
     * - never touches a user who already has a real super-admin role — a super admin isn't tied
     *   to any one salon (see salons() docblock; EnsureSuperAdmin bypasses entirely on that role)
     * - only fires when a salon is actually bound (CurrentSalon) — during CLI/seeding contexts
     *   that never set it (this factory is never used by UserSeeder itself, which creates both
     *   seeded accounts directly via User::firstOrCreate()), this is a safe no-op
     * - respects the phase-1 "at most one owner per salon" rule enforced by
     *   SuperAdminService::createSalonWithAdmin() — if the resolved salon already has an owner,
     *   this deliberately does NOT attach a second one (silently violating that documented
     *   invariant here would be worse than leaving a factory-made admin unlinked)
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            if (! $user->is_admin) {
                return;
            }

            // ⭐ Fix (regression caught by AdminMiddlewareTest, introduced by this same factory
            // hook): the original version called $user->hasRole('super-admin') here, which lazy
            // LOADS AND CACHES the roles relationship onto this exact $user object instance —
            // as an empty collection, since at afterCreating() time no roles have been attached
            // yet. Tests routinely attach a role to the freshly-created admin right after the
            // factory call (`$admin->roles()->attach(...)`), which updates the database but does
            // NOT refresh that already-cached, now-stale in-memory collection. Since
            // actingAs($admin) reuses that exact same object for the whole test, any LATER
            // ->hasRole() check (e.g. AdminMiddleware's specialist-redirect check) read the stale
            // empty cache and always returned false. A plain exists() query never populates
            // $user->relations['roles'], so it can't leave that stale cache behind.
            if ($user->roles()->where('name', 'super-admin')->exists()) {
                return;
            }

            $salonId = app(\App\Support\CurrentSalon::class)->id();
            if (! $salonId) {
                return;
            }

            $salon = \App\Models\Salon::find($salonId);
            if (! $salon || $salon->admins()->wherePivot('role', 'owner')->exists()) {
                return;
            }

            $salon->admins()->attach($user->id, ['role' => 'owner']);
        });
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone_verified_at' => null,
        ]);
    }

    public function withActiveOtp(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_code' => '123456',
            'verification_code_expire_at' => now()->addMinutes(2),
        ]);
    }

    public function withActiveLoginOtp(): static
    {
        return $this->state(fn (array $attributes) => [
            'login_verification_code' => '987654',
            'login_verification_code_expire_at' => now()->addMinutes(2),
        ]);
    }
}
