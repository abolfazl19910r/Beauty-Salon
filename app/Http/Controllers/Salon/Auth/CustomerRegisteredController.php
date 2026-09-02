<?php

namespace App\Http\Controllers\Salon\Auth;

use App\Events\User\NewUserRegistered;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PasswordStrengthService;
use App\Services\PhoneVerificationService;
use App\Support\CurrentSalon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * ⭐ Customer identity redesign (confirmed 2026-08-30 — see "🔴 بازطراحی هویت مشتری" in
 * Rasta_unified_prompt.md). Deliberately a SEPARATE controller/namespace from
 * App\Http\Controllers\Auth\RegisteredUserController rather than a modified version of it —
 * that one stays exactly as-is for admin/specialist accounts (globally unique phone, no salon
 * awareness at all). Mirrors its OTP flow closely (same PhoneVerificationService, same session-
 * based two-step register→verify pattern) with three differences: the phone-uniqueness check is
 * scoped to the current salon, `salon_id`+`user_type='customer'` are set explicitly at creation,
 * and post-verification lands on the salon's own home page rather than a global /dashboard.
 *
 * ⚠️ Known gap, not fixed here: after successful verification there is no salon-scoped customer
 * dashboard/booking flow to redirect to yet — web/bookings.php, web/profiles.php, web/wallet.php
 * etc. are still deliberately un-migrated (see commit 4b-2 in Rasta_unified_prompt.md), so this
 * redirects to the salon home page instead. Once those routes move under /s/{slug}, this should
 * redirect there instead.
 */
class CustomerRegisteredController extends Controller
{
    public function __construct(
        protected readonly PhoneVerificationService $verificationService,
        protected readonly PasswordStrengthService $passwordStrengthService,
        protected readonly CurrentSalon $currentSalon,
    ) {}

    public function create(): View
    {
        return view('salon.auth.register', ['salon' => $this->currentSalon->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $salon = $this->currentSalon->get();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required', 'string', 'regex:/^09[0-9]{9}$/',
                Rule::unique('users', 'phone')->where(fn ($query) => $query
                    ->where('salon_id', $salon->id)
                    ->where('user_type', 'customer')),
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'phone.regex' => 'شماره موبایل باید با 09 شروع شود و 11 رقم باشد',
            'phone.unique' => 'این شماره موبایل قبلاً در همین سالن ثبت شده است',
        ]);

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'password_changed_at' => now(),
            'password_strength_score' => $this->passwordStrengthService->score($request->password),
            'salon_id' => $salon->id,
            'user_type' => 'customer',
        ]);

        event(new NewUserRegistered($user));

        $this->verificationService->sendCode($user);

        session([
            'customer_register_user_id' => $user->id,
            'customer_register_attempt_time' => now(),
        ]);

        return redirect()->route('salon.register.verify.show')
            ->with('success', 'کد تایید به شماره موبایل شما ارسال شد.');
    }

    public function showVerify(): View|RedirectResponse
    {
        if (! session('customer_register_user_id')) {
            return redirect()->route('salon.register')
                ->withErrors(['error' => 'لطفا ابتدا ثبت نام کنید.']);
        }

        $user = User::find(session('customer_register_user_id'));

        if (! $user) {
            session()->forget(['customer_register_user_id', 'customer_register_attempt_time']);

            return redirect()->route('salon.register')
                ->withErrors(['error' => 'کاربر یافت نشد. لطفا دوباره ثبت نام کنید.']);
        }

        return view('salon.auth.register-verify', ['user' => $user, 'salon' => $this->currentSalon->get()]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $userId = session('customer_register_user_id');

        if (! $userId) {
            return redirect()->route('salon.register')
                ->withErrors(['error' => 'جلسه شما منقضی شده است. لطفا دوباره ثبت نام کنید.']);
        }

        $user = User::find($userId);

        if (! $user) {
            session()->forget(['customer_register_user_id', 'customer_register_attempt_time']);

            return redirect()->route('salon.register')
                ->withErrors(['error' => 'کاربر یافت نشد.']);
        }

        if ($this->verificationService->verify($user, $request->code)) {
            session()->forget(['customer_register_user_id', 'customer_register_attempt_time']);

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->route('home', ['salon_slug' => $this->currentSalon->get()->slug])
                ->with('success', 'ثبت نام شما با موفقیت انجام شد. خوش آمدید!');
        }

        return back()->withErrors([
            'code' => 'کد وارد شده نامعتبر یا منقضی شده است.',
        ]);
    }

    public function resendCode(Request $request): RedirectResponse
    {
        $userId = session('customer_register_user_id');

        if (! $userId) {
            return back()->withErrors(['error' => 'جلسه شما منقضی شده است.']);
        }

        $user = User::find($userId);

        if (! $user) {
            return back()->withErrors(['error' => 'کاربر یافت نشد.']);
        }

        $this->verificationService->sendCode($user);

        return back()->with('success', 'کد تایید مجدد ارسال شد.');
    }
}
