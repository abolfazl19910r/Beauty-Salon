<?php

namespace Database\Factories;

use App\Models\Salon;
use App\Models\User;
use App\Support\CurrentSalon;
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
            'verification_code' => null,
            'verification_code_expire_at' => null,
            'login_verification_code' => null,
            'login_verification_code_expire_at' => null,
            'remember_token' => Str::random(10),
            // ⭐ Customer identity redesign (2026-08-30): User deliberately does NOT use
            // BelongsToSalon (see that trait's docblock — a blanket salon_id filter would break
            // staff rows, which are salon_id=null by design). That means, unlike every other
            // factory in this project, this one MUST set salon_id itself — nothing does it
            // automatically. Discovered when php artisan db:seed started failing on the new
            // salon_id NOT NULL constraint for every seeder that calls User::factory() (10+
            // call sites — SpecialistFactory, SpecialistWalletFactory, BookingSeeder,
            // LoyaltySimulationSeeder, and others), even after DatabaseSeeder was already fixed
            // to bind CurrentSalon — that fix only helps models that actually read it via
            // BelongsToSalon, and User never did.
            'salon_id' => app(CurrentSalon::class)->id(),
            'user_type' => 'customer',
        ];
    }

    /**
     * ⭐ Customer identity redesign: also flips user_type/salon_id, not just is_admin — an admin
     * row with user_type still 'customer' would be invisible to AuthenticatedSessionController's
     * now staff-only login lookup, the exact inconsistency found and fixed in UserSeeder and
     * AdminUserService for the same reason.
     *
     * ⭐ Merge note (two parallel fixes for the same underlying gap, reconciled here): this state
     * method makes a user pass the *login* check (user_type='staff'). The separate configure()
     * hook below makes an is_admin=true user (however it was created — this state, or a bare
     * ->create(['is_admin' => true])) pass the *panel-access* check (EnsureAdminSalonActive,
     * which needs a salon_admins row, not user_type). Both are needed; neither alone is enough
     * for a factory-made admin to both log in and actually reach /admin/*.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
            'user_type' => 'staff',
            'salon_id' => null,
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

            // ⭐ Fix (regression caught by AdminMiddlewareTest, introduced by an earlier version
            // of this same factory hook): calling $user->hasRole('super-admin') here lazy LOADS
            // AND CACHES the roles relationship onto this exact $user object instance — as an
            // empty collection, since at afterCreating() time no roles have been attached yet.
            // Tests routinely attach a role to the freshly-created admin right after the factory
            // call (`$admin->roles()->attach(...)`), which updates the database but does NOT
            // refresh that already-cached, now-stale in-memory collection. Since actingAs($admin)
            // reuses that exact same object for the whole test, any LATER ->hasRole() check
            // (e.g. AdminMiddleware's specialist-redirect check) read the stale empty cache and
            // always returned false. A plain exists() query never populates
            // $user->relations['roles'], so it can't leave that stale cache behind.
            if ($user->roles()->where('name', 'super-admin')->exists()) {
                return;
            }

            $salonId = app(CurrentSalon::class)->id();
            if (! $salonId) {
                return;
            }

            $salon = Salon::find($salonId);
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
