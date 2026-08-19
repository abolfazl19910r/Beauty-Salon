<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function __construct(protected readonly TwoFactorAuthService $twoFactorService) {}

    public function show(): View
    {
        return view('auth.2fa.index', [
            'enabled' => $this->twoFactorService->isEnabled(auth()->user()),
        ]);
    }

    public function showSetup(): View|RedirectResponse
    {
        if ($this->twoFactorService->isEnabled(auth()->user())) {
            return redirect()->route('security.2fa')
                ->with('error', 'احراز هویت دو مرحله‌ای قبلاً فعال شده است.');
        }

        $code = $this->twoFactorService->generateCode(auth()->user());

        return view('auth.2fa.setup', compact('code'));
    }

    public function showConfirmation(): View
    {
        return view('auth.2fa.confirm');
    }

    public function enable(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'code' => 'required|string|size:6',
            ]);

            if ($this->twoFactorService->verify(auth()->user(), $request->code)) {
                $this->twoFactorService->enable(auth()->user());

                Log::info('2FA enabled', [
                    'user_id' => auth()->id(),
                ]);

                return response()->json([
                    'message' => 'احراز هویت دو مرحله‌ای با موفقیت فعال شد.',
                ]);
            }

            return response()->json([
                'error' => 'کد وارد شده نامعتبر است.',
            ], 422);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('2FA enable failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'خطا در فعال‌سازی احراز هویت دو مرحله‌ای.',
            ], 500);
        }
    }

    public function disable(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'code' => 'required|string|size:6',
            ]);

            if ($this->twoFactorService->verify(auth()->user(), $request->code)) {
                $this->twoFactorService->disable(auth()->user());

                Log::info('2FA disabled', [
                    'user_id' => auth()->id(),
                ]);

                return response()->json([
                    'message' => 'احراز هویت دو مرحله‌ای غیرفعال شد.',
                ]);
            }

            return response()->json([
                'error' => 'کد وارد شده نامعتبر است.',
            ], 422);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('2FA disable failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'خطا در غیرفعال‌سازی احراز هویت دو مرحله‌ای.',
            ], 500);
        }
    }

    public function verify(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'code' => 'required|string|size:6',
            ]);

            if ($this->twoFactorService->verify(auth()->user(), $request->code)) {
                session(['2fa_verified' => true]);

                Log::info('2FA verification successful', [
                    'user_id' => auth()->id(),
                ]);

                return response()->json([
                    'message' => 'کد تایید شد.',
                ]);
            }

            return response()->json([
                'error' => 'کد وارد شده نامعتبر است.',
            ], 422);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('2FA verification failed', [
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
            $code = $this->twoFactorService->generateCode(auth()->user());

            Log::info('2FA code resent', [
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'کد جدید ارسال شد.',
            ]);

        } catch (\Exception $e) {
            Log::error('2FA code resend failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'خطا در ارسال مجدد کد.',
            ], 500);
        }
    }
}
