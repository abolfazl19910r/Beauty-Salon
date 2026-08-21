<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsurePhoneIsVerified;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Documents a real, currently-live gap discovered while auditing the auth stack
 * during the test-writing phase: the 'verified' middleware alias (registered in
 * bootstrap/app.php as Illuminate\Auth\Middleware\EnsureEmailIsVerified) is applied
 * to three real route groups (routes/web.php's main protected group — which wraps
 * specialistprofile.php, profiles.php, services.php, bookings.php, payments.php,
 * loyalty.php, security.php, wallet.php).
 *
 * Illuminate's EnsureEmailIsVerified only calls hasVerifiedEmail() when
 * `$request->user() instanceof MustVerifyEmail`. App\Models\User does NOT implement
 * that interface (this project has no 'email' column at all — phone/OTP only), so the
 * middleware silently short-circuits to a pass-through for every request, regardless
 * of phone verification status.
 *
 * A correct, working phone-verification middleware (App\Http\Middleware\
 * EnsurePhoneIsVerified, checking hasVerifiedPhone()) already exists in the codebase
 * but is NOT registered under any alias and is not applied to any route — confirmed
 * dead code. Its failure-path redirect target ('verification.notice') doesn't even
 * correspond to a live route (the controllers that would have registered it —
 * VerificationController, EmailVerificationPromptController, etc. — were themselves
 * confirmed-orphaned Laravel Breeze scaffolding removed in this same session), so even
 * wiring EnsurePhoneIsVerified in as-is would itself break on the failure path.
 *
 * Practical impact today: in the *normal* UI flow this gap is not exploitable, because
 * both registration and login already gate Auth::login() behind a mandatory OTP step
 * (RegisteredUserController::verify() / AuthenticatedSessionController::verify()) —
 * a session can't exist at all without phone_verified_at having been set at least once.
 * This test proves the gap exists at the middleware layer in isolation (an
 * authenticated-but-explicitly-unverified user still passes through), which matters if
 * any future code path ever creates a session without going through OTP (e.g. an
 * admin-impersonation feature, a queued job that logs a user in, etc.).
 *
 * This is intentionally left as an OPEN BUSINESS DECISION, not silently fixed here:
 * fixing it properly requires either (a) registering EnsurePhoneIsVerified under the
 * 'verified' alias AND building a real 'verification.notice' page/route for the
 * failure path (new UI, out of scope for a pure test-writing session), or (b) removing
 * the 'verified' middleware from all three route groups since it currently does
 * nothing anyway. Per this project's established convention, ambiguous architectural
 * decisions like this are documented with a pinning test rather than guessed at.
 */
class PhoneVerificationMiddlewareGapTest extends TestCase
{
    use RefreshDatabase;

    public function test_documented_gap_an_unverified_user_still_passes_the_verified_middleware(): void
    {
        $user = User::factory()->unverified()->create();

        $this->assertFalse($user->hasVerifiedPhone());

        // security.dashboard sits inside the ['auth', 'verified'] group in routes/web.php.
        $this->actingAs($user)
            ->get(route('security.dashboard'))
            ->assertOk();
    }

    public function test_documented_gap_the_working_phone_verification_middleware_exists_but_is_never_applied(): void
    {
        $this->assertTrue(class_exists(EnsurePhoneIsVerified::class));

        // No dedicated alias-lookup API is exposed publicly, so we assert the negative
        // via behavior instead: confirm the class itself is never referenced by any
        // registered route's middleware list.
        $routes = collect(app('router')->getRoutes())->map(
            fn ($route) => $route->gatherMiddleware()
        )->flatten();

        $this->assertFalse(
            $routes->contains(EnsurePhoneIsVerified::class),
            'EnsurePhoneIsVerified is applied to a route — this pinning test is stale; '.
            'the middleware gap it documents may have been resolved.'
        );
    }
}
