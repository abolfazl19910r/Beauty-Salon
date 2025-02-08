<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SMSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PasswordResetController extends Controller
{
    protected SMSService $smsService;

    public function __construct(SMSService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function create()
    {
        return view('auth.forgot-password');
    }

    public function sendCode(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'regex:/^09[0-9]{9}$/'],
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return back()->withErrors([
                'phone' => 'کاربری با این شماره موبایل یافت نشد.',
            ]);
        }

        $token = Str::random(60);

        $verificationCode = rand(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['phone' => $request->phone],
            [
                'token' => $token,
                'created_at' => now()
            ]
        );

        $user->update([
            'verification_code' => $verificationCode,
            'verification_code_expire_at' => now()->addMinutes(2)
        ]);

        $message = "کد بازیابی رمز عبور شما: {$verificationCode}";
        $this->smsService->send($request->phone, $message);

        return redirect()
            ->route('password.reset', ['token' => $token])
            ->with('success', 'کد بازیابی به شماره موبایل شما ارسال شد.');
    }

    public function showReset(Request $request)
    {
        $token = $request->token;
        $reset = DB::table('password_reset_tokens')
            ->where('token', $token)
            ->first();

        if (!$reset || Carbon::parse($reset->created_at)->addHour()->isPast()) {
            return redirect()
                ->route('password.request')
                ->withErrors(['token' => 'لینک بازیابی نامعتبر یا منقضی شده است.']);
        }

        return view('auth.reset-password', compact('token'));
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'code' => 'required|string|size:6',
            'password' => 'required|min:8|confirmed',
        ]);

        $reset = DB::table('password_reset_tokens')
            ->where('token', $request->token)
            ->first();

        if (!$reset || Carbon::parse($reset->created_at)->addHour()->isPast()) {
            return back()->withErrors([
                'token' => 'لینک بازیابی نامعتبر یا منقضی شده است.',
            ]);
        }

        $user = User::where('phone', $reset->phone)->first();

        if (!$user) {
            return back()->withErrors([
                'phone' => 'کاربری با این شماره موبایل یافت نشد.',
            ]);
        }

        if ($user->verification_code !== $request->code ||
            now()->isAfter($user->verification_code_expire_at)) {
            return back()->withErrors([
                'code' => 'کد تایید نامعتبر یا منقضی شده است.',
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'verification_code' => null,
            'verification_code_expire_at' => null
        ]);

        DB::table('password_reset_tokens')
            ->where('phone', $reset->phone)
            ->delete();

        return redirect()
            ->route('login')
            ->with('success', 'رمز عبور شما با موفقیت تغییر کرد.');
    }
}
