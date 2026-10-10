<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendPasswordResetCodeJob;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Support\Queues;
use App\Support\SalonOfNotifiable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function __construct(
        protected readonly UserRepositoryInterface $userRepository,
    ) {}

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'regex:/^09[0-9]{9}$/'],
        ]);

        // ⭐ Fix (۲۰۲۶-۱۰-۱۰، بسته‌ی ۲ اپلیکیشن): این بازیابی رمزِ /login (حساب کادر) است. findByPhone اولین حساب هر
        // نوعی را برمی‌داشت؛ متخصصی که با همان شماره مشتری یک سالن هم بود، کد را روی حساب مشتری می‌گرفت و رمز مشتری
        // عوض می‌شد (رمز کادر هرگز). مشتری بازیابی خودش را زیر /s/{slug}/forgot-password دارد.
        $user = $this->userRepository->findStaffByPhone($request->phone);

        if (! $user) {
            return back()->withErrors(['phone' => 'کاربری با این شماره یافت نشد.']);
        }

        $verificationCode = rand(100000, 999999);
        $token = Str::random(60);

        $user->update([
            'verification_code' => $verificationCode,
            'verification_code_expire_at' => now()->addMinutes((int) config('auth.reset_code_expire_minutes', 2)),
        ]);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['phone' => $request->phone],
            [
                'phone' => $request->phone,
                'token' => $token,
                'created_at' => now(),
            ]
        );

        // کد تأیید روی صف otp (روی هاست بدون worker بعد از پاسخ)؛ سالن کارمند همین حالا پیدا می‌شود
        $salonId = $user->salon_id ?? SalonOfNotifiable::resolve($user);
        Queues::dispatchOtp(new SendPasswordResetCodeJob($user->id, (string) $verificationCode, $salonId));

        return redirect()->route('password.verify', ['token' => $token])
            ->with('success', 'کد تایید ارسال شد.');
    }

    public function showReset(Request $request): View|RedirectResponse
    {
        $token = $request->token;

        $resetRecord = DB::table('password_reset_tokens')
            ->where('token', $token)
            ->first();

        if (! $resetRecord || Carbon::parse($resetRecord->created_at)->addHour()->isPast()) {
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

        if (! $resetRecord) {
            return back()->withErrors(['code' => 'درخواست نامعتبر است.']);
        }

        $user = $this->userRepository->findStaffByPhone($resetRecord->phone);

        if (! $user) {
            return back()->withErrors(['code' => 'کاربر یافت نشد.']);
        }

        if ($user->verification_code !== $request->code || now()->isAfter($user->verification_code_expire_at)) {
            return back()->withErrors(['code' => 'کد تایید اشتباه یا منقضی شده است.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'verification_code' => null,
            'verification_code_expire_at' => null,
        ]);

        DB::table('password_reset_tokens')->where('phone', $user->phone)->delete();

        return redirect()->route('login')->with('success', 'رمز عبور با موفقیت تغییر کرد.');
    }
}
