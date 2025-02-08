<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PhoneVerificationService;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    protected PhoneVerificationService $verificationService;

    public function __construct(PhoneVerificationService $verificationService)
    {
        $this->middleware('auth');
        $this->verificationService = $verificationService;
    }

    public function show()
    {
        return view('auth.verify');
    }

    public function verify(Request $request)
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

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedPhone()) {
            return redirect()->route('home');
        }

        $this->verificationService->sendCode($request->user());

        return back()->with('message', 'کد تایید جدید ارسال شد.');
    }
}
