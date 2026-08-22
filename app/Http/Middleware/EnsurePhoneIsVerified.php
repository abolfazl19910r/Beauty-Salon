<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates protected routes behind real phone verification.
 *
 * This replaces the 'verified' alias's previous binding to Illuminate's own
 * EnsureEmailIsVerified, which was a permanent silent no-op for this project:
 * that middleware only calls hasVerifiedEmail() when the user is an instance of
 * Illuminate\Contracts\Auth\MustVerifyEmail, and App\Models\User never implemented
 * that interface (this project has no 'email' column at all — phone/OTP only).
 *
 * Practical scope: in the normal registration/login UI flow this was never
 * exploitable, since both already gate Auth::login() behind a mandatory OTP step —
 * a session couldn't exist without phone_verified_at being set at least once via
 * RegisteredUserController::verify(). The real gap this closes is admin-created
 * users/specialists (AdminUserController::store(), AdminSpecialistController::store()):
 * those accounts can log in via the login OTP flow (which only clears
 * login_verification_code, it never calls markPhoneAsVerified()) while
 * phone_verified_at stays permanently null. This middleware now catches that case
 * and routes them through a real verification step before they reach any protected
 * page, instead of silently letting it slide.
 *
 * Only the intended URL is stashed in session here; sending the actual OTP is left
 * entirely to PhoneVerificationController::notice(), so there is a single place that
 * decides whether a fresh code needs sending (regardless of whether the user arrived
 * here via this middleware's redirect or by navigating to the notice page directly).
 */
class EnsurePhoneIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->hasVerifiedPhone()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'phone_verification_required' => true,
                'redirect_url' => route('verification.notice'),
                'message' => 'ابتدا باید شماره موبایل خود را تایید کنید.',
            ], 428);
        }

        session(['phone_verification_intended_url' => $request->fullUrl()]);

        return redirect()->route('verification.notice');
    }
}
