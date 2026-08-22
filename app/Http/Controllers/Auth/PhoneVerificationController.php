<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PhoneVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * ⭐ Fix (test-writing session 10, option A): real working replacement for the failure
 * path of App\Http\Middleware\EnsurePhoneIsVerified, now bound to the 'verified' alias.
 *
 * This is a fresh, working implementation — not a revival of the old Laravel Breeze
 * scaffolding class of the same name that was confirmed dead and removed earlier in
 * this same session (it referenced a nonexistent 'auth.verify-phone' view and a
 * nonexistent hasVerifiedEmail()-style check).
 *
 * Reuses PhoneVerificationService::sendCode()/verify() — the exact same
 * verification_code/verification_code_expire_at columns and OTP semantics already
 * used by registration (RegisteredUserController), since "has this phone been
 * verified" is the same fact regardless of which flow the user is coming from.
 */
class PhoneVerificationController extends Controller
{
    public function __construct(protected readonly PhoneVerificationService $phoneVerificationService) {}

    public function notice(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->hasVerifiedPhone()) {
            return $this->redirectAfterVerification();
        }

        // Single place that decides whether a fresh code needs sending, regardless of
        // whether the user arrived here via EnsurePhoneIsVerified's redirect or by
        // navigating to this page directly (e.g. a bookmark, or a second tab).
        if (! session('phone_verification_code_sent')) {
            $this->phoneVerificationService->sendCode($user);
            session(['phone_verification_code_sent' => true]);
        }

        return view('auth.verify-phone');
    }

    public function verify(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'code' => ['required', 'string', 'size:6'],
            ]);

            if ($this->phoneVerificationService->verify(auth()->user(), $request->code)) {
                session()->forget('phone_verification_code_sent');

                Log::info('Phone verification successful (post-auth notice flow)', [
                    'user_id' => auth()->id(),
                ]);

                return response()->json([
                    'message' => 'شماره موبایل با موفقیت تایید شد.',
                    'redirect_url' => $this->intendedUrl(),
                ]);
            }

            return response()->json([
                'error' => 'کد وارد شده نامعتبر یا منقضی شده است.',
            ], 422);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Phone verification failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'خطا در تایید کد.',
            ], 500);
        }
    }

    public function resend(): JsonResponse
    {
        try {
            $this->phoneVerificationService->sendCode(auth()->user());

            Log::info('Phone verification code resent', [
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'کد جدید ارسال شد.',
            ]);
        } catch (\Exception $e) {
            Log::error('Phone verification code resend failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'خطا در ارسال مجدد کد.',
            ], 500);
        }
    }

    private function intendedUrl(): string
    {
        return session()->pull('phone_verification_intended_url')
            ?? url($this->homePathForUser(auth()->user()));
    }

    /**
     * Deliberately duplicates the logic in AuthenticatedSessionController::redirectPath()
     * rather than reusing RouteServiceProvider::getHomeForUser() — that static method is
     * confirmed dead code (never called anywhere in the project besides its own
     * definition) and has a bug of its own: it only checks hasRole('specialists')
     * (plural), missing the hasRole('specialist') (singular, the real role name
     * assigned everywhere else) fallback that redirectPath() already correctly has.
     * Reusing the buggy dead method here would have quietly imported that bug into a
     * newly-live code path.
     */
    private function homePathForUser(User $user): string
    {
        if ($user->hasRole('specialists') || $user->hasRole('specialist')) {
            return '/my-dashboard';
        }

        if ($user->is_admin) {
            return '/admin/dashboard';
        }

        return '/dashboard';
    }

    private function redirectAfterVerification(): RedirectResponse
    {
        return redirect($this->intendedUrl());
    }
}
