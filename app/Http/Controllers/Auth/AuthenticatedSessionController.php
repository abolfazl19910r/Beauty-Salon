<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Auth\TrustedDeviceService;
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
        protected readonly UserRepositoryInterface $userRepository,
        protected readonly TrustedDeviceService $trustedDevices,
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

        $user = $this->userRepository->findStaffByPhone($credentials['phone']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            $this->securityLogService->logLogin(false, $credentials['phone'], $user);

            return back()->withErrors([
                'phone' => 'اطلاعات وارد شده صحیح نمی‌باشد.',
            ])->withInput($request->only('phone'));
        }

        // بسته‌ی ۶: دستگاه شناخته‌شده («مرا به خاطر بسپار» قبلی روی همین مرورگر) ← بدون کد پیامکی
        if ($this->trustedDevices->isTrusted($user, $request)) {
            return $this->completeLogin($request, $user, remember: true, trustDevice: false);
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
            'login_remember' => $request->boolean('remember'),
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

        $user = $this->userRepository->find(session('login_user_id'));

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

        $user = $this->userRepository->find($userId);

        if (! $user) {
            session()->forget(['login_user_id', 'login_attempt_time']);

            return redirect()->route('login')
                ->withErrors(['error' => 'کاربر یافت نشد.']);
        }

        if ($this->verificationService->verifyLoginCode($user, $request->code)) {
            $remember = (bool) session('login_remember');
            session()->forget(['login_user_id', 'login_attempt_time', 'login_remember']);

            return $this->completeLogin($request, $user, remember: $remember, trustDevice: $remember);
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

        $user = $this->userRepository->find($userId);

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

        return redirect()->route('login')
            ->with('success', 'با موفقیت خارج شدید.');
    }

    /**
     * ورود نهایی. remember: نشست ماندگار (بسته‌ی ۶، ۹۰ روز)؛ trustDevice: این مرورگر برای ورود بعدی بدون کد پیامکی.
     */
    private function completeLogin(Request $request, \App\Models\User $user, bool $remember, bool $trustDevice): RedirectResponse
    {
        // متخصصی که مدیر بعد از ساخت این حساب با همین شماره اضافه کرده (۲۰۲۶-۱۰-۲۲)
        app(\App\Services\Specialist\SpecialistAccountLinker::class)->linkPending($user);

        Auth::login($user, $remember);
        $request->session()->regenerate();
        // مقصد کهنه نماند تا ورود بعدی در همین مرورگر (مثلاً مشتری) به آن نرود
        $request->session()->forget('url.intended');

        if ($trustDevice) {
            $this->trustedDevices->trust($user, $request);
        }

        $this->securityLogService->logLogin(true, $user->phone, $user);

        // ⭐ باگ ۶ (گزارش‌شده ۲۰۲۶-۰۹-۱۸، رفع‌شده ۲۰۲۶-۰۹-۱۸ب): redirect()->intended() اگر
        // در session مقدار 'url.intended' از قبل ذخیره شده باشد (دقیقاً همان چیزی که
        // middleware auth هر بار کاربر لاگین‌نشده یک صفحه‌ی محافظت‌شده مثل
        // /admin/dashboard را باز می‌کند، ذخیره می‌کند)، به همان آدرس می‌رود و
        // redirectPath() را کاملاً نادیده می‌گیرد — حتی وقتی redirectPath() برای
        // سوپر ادمین صحیح /superadmin/dashboard را برمی‌گرداند. این پروژه از اول یک
        // سیستم ریدایرکت مبتنی‌بر-نقش دارد که با معنای «intended» در تناقض بنیادی است،
        // پس مستقیم به redirectPath() می‌رویم و intended() را کنار می‌گذاریم.
        return redirect()->to($this->redirectPath())
            ->with('success', 'خوش آمدید!');
    }

    protected function redirectPath(): string
    {
        return Auth::user()->homePath();
    }
}
