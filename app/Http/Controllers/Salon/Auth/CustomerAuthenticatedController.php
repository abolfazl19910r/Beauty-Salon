<?php

namespace App\Http\Controllers\Salon\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PhoneVerificationService;
use App\Services\SecurityLogService;
use App\Support\CurrentSalon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * ⭐ Customer identity redesign (confirmed 2026-08-30). Separate from
 * App\Http\Controllers\Auth\AuthenticatedSessionController (unchanged, still handles
 * admin/specialist login globally) — see CustomerRegisteredController's docblock for the full
 * rationale; the same reasoning applies here.
 *
 * ⚠️ No fallback to the global /login: a customer landing on the staff /login page is out of
 * scope for this controller to handle — per the confirmed decision, customers only ever reach
 * this flow through their own salon's /s/{slug}/login.
 */
class CustomerAuthenticatedController extends Controller
{
    public function __construct(
        protected readonly PhoneVerificationService $verificationService,
        protected readonly SecurityLogService $securityLogService,
        protected readonly CurrentSalon $currentSalon,
    ) {}

    public function create(): View
    {
        return view('salon.auth.login', ['salon' => $this->currentSalon->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $salon = $this->currentSalon->get();

        $credentials = $request->validate([
            'phone' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('phone', $credentials['phone'])
            ->where('salon_id', $salon->id)
            ->where('user_type', 'customer')
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            $this->securityLogService->logLogin(false, $credentials['phone'], $user);

            return back()->withErrors([
                'phone' => 'اطلاعات وارد شده صحیح نمی‌باشد.',
            ])->withInput($request->only('phone'));
        }

        try {
            $this->verificationService->sendLoginCode($user);
        } catch (\Exception $e) {
            Log::error('Failed to send customer login code', [
                'user_id' => $user->id,
                'salon_id' => $salon->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'phone' => 'خطا در ارسال کد تایید. لطفا دوباره تلاش کنید.',
            ]);
        }

        session([
            'customer_login_user_id' => $user->id,
            'customer_login_attempt_time' => now(),
        ]);

        return redirect()->route('salon.login.verify.show')
            ->with('success', 'کد تایید به شماره موبایل شما ارسال شد.');
    }

    public function showVerify(): View|RedirectResponse
    {
        if (! session('customer_login_user_id')) {
            return redirect()->route('salon.login')
                ->withErrors(['error' => 'لطفا ابتدا وارد شوید.']);
        }

        $user = User::find(session('customer_login_user_id'));

        if (! $user) {
            session()->forget(['customer_login_user_id', 'customer_login_attempt_time']);

            return redirect()->route('salon.login')
                ->withErrors(['error' => 'کاربر یافت نشد.']);
        }

        return view('salon.auth.login-verify', ['user' => $user, 'salon' => $this->currentSalon->get()]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $userId = session('customer_login_user_id');

        if (! $userId) {
            return redirect()->route('salon.login')
                ->withErrors(['error' => 'جلسه شما منقضی شده است. لطفا دوباره تلاش کنید.']);
        }

        $user = User::find($userId);

        if (! $user) {
            session()->forget(['customer_login_user_id', 'customer_login_attempt_time']);

            return redirect()->route('salon.login')
                ->withErrors(['error' => 'کاربر یافت نشد.']);
        }

        if ($this->verificationService->verifyLoginCode($user, $request->code)) {
            session()->forget(['customer_login_user_id', 'customer_login_attempt_time']);

            Auth::login($user);
            $request->session()->regenerate();

            $this->securityLogService->logLogin(true, $user->phone, $user);

            return redirect()->intended(route('home', ['salon_slug' => $this->currentSalon->get()->slug]))
                ->with('success', 'خوش آمدید!');
        }

        $this->securityLogService->logLogin(false, $user->phone, $user);

        return back()->withErrors([
            'code' => 'کد وارد شده نامعتبر یا منقضی شده است.',
        ]);
    }

    public function resendCode(Request $request): RedirectResponse
    {
        $userId = session('customer_login_user_id');

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
        $salonSlug = $this->currentSalon->get()->slug;

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home', ['salon_slug' => $salonSlug])
            ->with('success', 'با موفقیت خارج شدید.');
    }
}
