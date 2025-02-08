<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PhoneVerificationService;
use Illuminate\Http\Request;

class PhoneVerificationController extends Controller
{
    protected PhoneVerificationService $verificationService;

    public function __construct(PhoneVerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }

    public function notice()
    {
        return view('auth.verify-phone');
    }

    public function verify(Request $request)
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

    public function resend(Request $request): \Illuminate\Http\RedirectResponse
    {
        $this->verificationService->sendCode($request->user());

        return back()->with('success', 'کد تایید جدید ارسال شد.');
    }
}
