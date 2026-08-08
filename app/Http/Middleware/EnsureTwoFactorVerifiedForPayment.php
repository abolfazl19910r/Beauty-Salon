<?php

namespace App\Http\Middleware;

use App\Services\TwoFactorAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the "پرداخت امن" (payments.secure.*) routes behind two-factor authentication.
 *
 * - If the user has never enabled 2FA (`two_factor_enabled`), the whole secure-checkout path is
 *   pointless for them — bounce to the security settings page where they can turn it on.
 * - If 2FA is enabled but this browser session hasn't confirmed a code yet (`session('2fa_verified')`,
 *   the exact flag already set by the pre-existing, already-correct `TwoFactorController::verify()`),
 *   send a fresh code and redirect to a dedicated OTP entry page for this flow.
 * - Once verified this session, requests pass straight through.
 *
 * Registered under the `2fa.enabled` alias and used identically by both the web (redirect-based) and
 * api (json-based) payments/secure route groups.
 */
class EnsureTwoFactorVerifiedForPayment
{
    public function __construct(protected readonly TwoFactorAuthService $twoFactorService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->two_factor_enabled) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'برای استفاده از پرداخت امن، ابتدا احراز هویت دو مرحله‌ای را در تنظیمات امنیتی فعال کنید.',
                ], 403);
            }

            return redirect()->route('security.2fa')
                ->with('error', 'برای استفاده از پرداخت امن، ابتدا باید احراز هویت دو مرحله‌ای را در تنظیمات امنیتی فعال کنید.');
        }

        if (! session('2fa_verified')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'otp_required' => true,
                    'redirect_url' => route('payments.secure.otp'),
                    'message' => 'ابتدا باید کد تایید دو مرحله‌ای را وارد کنید.',
                ], 428);
            }

            if (! session('secure_payment_2fa_code_sent')) {
                $this->twoFactorService->generateCode($user);
                session(['secure_payment_2fa_code_sent' => true]);
            }

            session(['secure_payment_intended_url' => $request->fullUrl()]);

            return redirect()->route('payments.secure.otp');
        }

        return $next($request);
    }
}
