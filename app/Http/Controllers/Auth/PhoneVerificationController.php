<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PhoneVerificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PhoneVerificationController extends Controller
{
    public function __construct(protected readonly PhoneVerificationService $verificationService)
    {
    }

    public function notice(): View
    {
        return view('auth.verify-phone');
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|size:6'
        ]);

        if ($this->verificationService->verify($request->user(), $request->code)) {
            return redirect()->intended()
                ->with('success', 'شماره موبایل شما با موفقیت تایید شد.');
        }

        return back()->withErrors([
            'code' => 'کد وارد شده نامعتبر است.'
        ]);
    }

    public function resend(Request $request): RedirectResponse
    {
        $this->verificationService->sendCode($request->user());

        return back()->with('success', 'کد تایید جدید ارسال شد.');
    }
}
