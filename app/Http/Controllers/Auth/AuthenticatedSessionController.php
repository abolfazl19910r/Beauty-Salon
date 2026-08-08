<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use App\Services\PhoneVerificationService;
use App\Services\SecurityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        protected readonly PhoneVerificationService $verificationService,
        protected readonly SecurityLogService $securityLogService,
    ) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('phone', $credentials['phone'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            $this->securityLogService->logLogin(false, $credentials['phone'], $user);

            return back()->withErrors([
                'phone' => 'اطلاعات وارد شده صحیح نمی‌باشد.',
            ])->withInput($request->only('phone'));
        }

        try {
            $this->verificationService->sendLoginCode($user);
        } catch (\Exception $e) {
            Log::error('Failed to send login code', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'phone' => 'خطا در ارسال کد تایید. لطفا دوباره تلاش کنید.',
            ]);
        }

        session([
            'login_user_id' => $user->id,
            'login_attempt_time' => now(),
        ]);

        return redirect()->route('login.verify.show')
            ->with('success', 'کد تایید به شماره موبایل شما ارسال شد.');
    }

    public function showVerify(): View|RedirectResponse
    {
        if (! session('login_user_id')) {
            return redirect()->route('login')
                ->withErrors(['error' => 'لطفا ابتدا وارد شوید.']);
        }

        $user = User::find(session('login_user_id'));

        if (! $user) {
            session()->forget(['login_user_id', 'login_attempt_time']);

            return redirect()->route('login')
                ->withErrors(['error' => 'کاربر یافت نشد.']);
        }

        return view('auth.login-verify', compact('user'));
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $userId = session('login_user_id');

        if (! $userId) {
            return redirect()->route('login')
                ->withErrors(['error' => 'جلسه شما منقضی شده است. لطفا دوباره تلاش کنید.']);
        }

        $user = User::find($userId);

        if (! $user) {
            session()->forget(['login_user_id', 'login_attempt_time']);

            return redirect()->route('login')
                ->withErrors(['error' => 'کاربر یافت نشد.']);
        }

        if ($this->verificationService->verifyLoginCode($user, $request->code)) {
            session()->forget(['login_user_id', 'login_attempt_time']);

            Auth::login($user);
            $request->session()->regenerate();

            $this->securityLogService->logLogin(true, $user->phone, $user);

            return redirect()->intended($this->redirectPath())
                ->with('success', 'خوش آمدید!');
        }

        $this->securityLogService->logLogin(false, $user->phone, $user);

        return back()->withErrors([
            'code' => 'کد وارد شده نامعتبر یا منقضی شده است.',
        ]);
    }

    public function resendCode(Request $request): RedirectResponse
    {
        $userId = session('login_user_id');

        if (! $userId) {
            return back()->withErrors(['error' => 'جلسه شما منقضی شده است.']);
        }

        $user = User::find($userId);

        if (! $user) {
            return back()->withErrors(['error' => 'کاربر یافت نشد.']);
        }

        $this->verificationService->sendLoginCode($user);

        return back()->with('success', 'کد تایید مجدد ارسال شد.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')
            ->with('success', 'با موفقیت خارج شدید.');
    }

    protected function redirectPath(): string
    {
        $user = Auth::user();

        if ($user->hasRole('specialists') || $user->hasRole('specialist')) {
            return '/my-dashboard';
        }

        if ($user->is_admin) {
            return RouteServiceProvider::HOME;
        }

        return RouteServiceProvider::USER_HOME;
    }
}
