<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PhoneVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerificationController extends Controller
{
    public function __construct(protected readonly PhoneVerificationService $verificationService)
    {
        $this->middleware('auth');
    }

    public function show(): View
    {
        return view('auth.verify');
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        if ($this->verificationService->verify($request->user(), $request->code)) {
            return redirect()->route('home');
        }

        return back()->withErrors([
            'code' => 'کد وارد شده نامعتبر است.',
        ]);
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedPhone()) {
            return redirect()->route('home');
        }

        $this->verificationService->sendCode($request->user());

        return back()->with('message', 'کد تایید جدید ارسال شد.');
    }
}
