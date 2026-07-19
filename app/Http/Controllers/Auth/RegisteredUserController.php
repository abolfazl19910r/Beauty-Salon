<?php

namespace App\Http\Controllers\Auth;

use App\Events\User\NewUserRegistered;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PhoneVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    protected PhoneVerificationService $verificationService;

    public function __construct(PhoneVerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^09[0-9]{9}$/', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'phone.regex' => 'شماره موبایل باید با 09 شروع شود و 11 رقم باشد',
            'phone.unique' => 'این شماره موبایل قبلاً ثبت شده است',
        ]);

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        event(new NewUserRegistered($user));

        $this->verificationService->sendCode($user);

        session([
            'register_user_id' => $user->id,
            'register_attempt_time' => now()
        ]);

        return redirect()->route('register.verify.show')
            ->with('success', 'کد تایید به شماره موبایل شما ارسال شد.');
    }

    public function showVerify(): View|RedirectResponse
    {
        if (!session('register_user_id')) {
            return redirect()->route('register')
                ->withErrors(['error' => 'لطفا ابتدا ثبت نام کنید.']);
        }

        $user = User::find(session('register_user_id'));

        if (!$user) {
            session()->forget(['register_user_id', 'register_attempt_time']);
            return redirect()->route('register')
                ->withErrors(['error' => 'کاربر یافت نشد. لطفا دوباره ثبت نام کنید.']);
        }

        return view('auth.register-verify', compact('user'));
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $userId = session('register_user_id');

        if (!$userId) {
            return redirect()->route('register')
                ->withErrors(['error' => 'جلسه شما منقضی شده است. لطفا دوباره ثبت نام کنید.']);
        }

        $user = User::find($userId);

        if (!$user) {
            session()->forget(['register_user_id', 'register_attempt_time']);
            return redirect()->route('register')
                ->withErrors(['error' => 'کاربر یافت نشد.']);
        }

        if ($this->verificationService->verify($user, $request->code)) {
            session()->forget(['register_user_id', 'register_attempt_time']);

            Auth::login($user);
            $request->session()->regenerate();

            return redirect('/dashboard')
                ->with('success', 'ثبت نام شما با موفقیت انجام شد. خوش آمدید!');
        }

        return back()->withErrors([
            'code' => 'کد وارد شده نامعتبر یا منقضی شده است.'
        ]);
    }

    public function resendCode(Request $request): RedirectResponse
    {
        $userId = session('register_user_id');

        if (!$userId) {
            return back()->withErrors(['error' => 'جلسه شما منقضی شده است.']);
        }

        $user = User::find($userId);

        if (!$user) {
            return back()->withErrors(['error' => 'کاربر یافت نشد.']);
        }

        $this->verificationService->sendCode($user);

        return back()->with('success', 'کد تایید مجدد ارسال شد.');
    }
}
