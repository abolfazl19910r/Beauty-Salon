<?php

namespace App\Http\Controllers\Auth;

use App\Events\User\NewUserRegistered;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\PasswordStrengthService;
use App\Services\PhoneVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(
        protected readonly PhoneVerificationService $verificationService,
        protected readonly PasswordStrengthService $passwordStrengthService,
        protected readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * ⭐ Customer identity redesign (confirmed 2026-08-30): self-registration through this
     * controller was, in practice, always customer signup — nothing else in this codebase
     * creates an admin (App\Services\Admin\User\AdminUserService, via /admin/users) or specialist
     * (App\Services\Admin\Specialist\AdminSpecialistService, matched by phone) account through a
     * public registration form. Redirecting here rather than deleting the route keeps
     * route('register') working everywhere it's already referenced across the codebase — it now
     * just lands on the default salon's own registration page
     * (CustomerRegisteredController::create()) instead of rendering a form itself. store()/
     * verify()/resendCode() below are unreached through normal navigation now that create() never
     * renders the form that would POST to them; left in place rather than removed since deleting
     * them isn't necessary for this fix and it's a shared controller other flows may still touch.
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('salon.register', ['salon_slug' => 'rasta']);
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

        $user = $this->userRepository->create([
            'name' => $request->name,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'password_changed_at' => now(),
            'password_strength_score' => $this->passwordStrengthService->score($request->password),
        ]);

        event(new NewUserRegistered($user));

        $this->verificationService->sendCode($user);

        session([
            'register_user_id' => $user->id,
            'register_attempt_time' => now(),
        ]);

        return redirect()->route('register.verify.show')
            ->with('success', 'کد تایید به شماره موبایل شما ارسال شد.');
    }

    public function showVerify(): View|RedirectResponse
    {
        if (! session('register_user_id')) {
            return redirect()->route('register')
                ->withErrors(['error' => 'لطفا ابتدا ثبت نام کنید.']);
        }

        $user = $this->userRepository->find(session('register_user_id'));

        if (! $user) {
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

        if (! $userId) {
            return redirect()->route('register')
                ->withErrors(['error' => 'جلسه شما منقضی شده است. لطفا دوباره ثبت نام کنید.']);
        }

        $user = $this->userRepository->find($userId);

        if (! $user) {
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
            'code' => 'کد وارد شده نامعتبر یا منقضی شده است.',
        ]);
    }

    public function resendCode(Request $request): RedirectResponse
    {
        $userId = session('register_user_id');

        if (! $userId) {
            return back()->withErrors(['error' => 'جلسه شما منقضی شده است.']);
        }

        $user = $this->userRepository->find($userId);

        if (! $user) {
            return back()->withErrors(['error' => 'کاربر یافت نشد.']);
        }

        $this->verificationService->sendCode($user);

        return back()->with('success', 'کد تایید مجدد ارسال شد.');
    }
}
