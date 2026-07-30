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
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PasswordResetController extends Controller
{
    public function __construct(protected readonly SMSService $smsService)
    {
    }

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'regex:/^09[0-9]{9}$/'],
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return back()->withErrors(['phone' => 'کاربری با این شماره یافت نشد.']);
        }

        $verificationCode = rand(100000, 999999);
        $token = Str::random(60);

        $user->update([
            'verification_code' => $verificationCode,
            'verification_code_expire_at' => now()->addMinutes(2)
        ]);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['phone' => $request->phone],
            [
                'phone' => $request->phone,
                'token' => $token,
                'created_at' => now()
            ]
        );

        try {
            $template = config('services.kavenegar.templates.reset_password', 'verification');
            $this->smsService->sendTemplate($user->phone, $template, [(string)$verificationCode]);
        } catch (\Exception $e) {
            return back()->withErrors(['phone' => 'خطا در ارسال پیامک.']);
        }

        return redirect()->route('password.verify', ['token' => $token])
            ->with('success', 'کد تایید ارسال شد.');
    }

    public function showReset(Request $request): View|RedirectResponse
    {
        $token = $request->token;

        $resetRecord = DB::table('password_reset_tokens')
            ->where('token', $token)
            ->first();

        if (!$resetRecord || Carbon::parse($resetRecord->created_at)->addHour()->isPast()) {
            return redirect()
                ->route('password.request')
                ->withErrors(['phone' => 'لینک بازیابی نامعتبر یا منقضی شده است. لطفا مجدد درخواست دهید.']);
        }

        return view('auth.reset-password', compact('token'));
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'code' => 'required|string|size:6',
            'password' => 'required|min:8|confirmed',
        ]);

        $resetRecord = DB::table('password_reset_tokens')
            ->where('token', $request->token)
            ->first();

        if (!$resetRecord) {
            return back()->withErrors(['code' => 'درخواست نامعتبر است.']);
        }

        $user = User::where('phone', $resetRecord->phone)->first();

        if (!$user) {
            return back()->withErrors(['code' => 'کاربر یافت نشد.']);
        }

        if ($user->verification_code !== $request->code || now()->isAfter($user->verification_code_expire_at)) {
            return back()->withErrors(['code' => 'کد تایید اشتباه یا منقضی شده است.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'verification_code' => null,
            'verification_code_expire_at' => null
        ]);

        DB::table('password_reset_tokens')->where('phone', $user->phone)->delete();

        return redirect()->route('login')->with('success', 'رمز عبور با موفقیت تغییر کرد.');
    }
}
