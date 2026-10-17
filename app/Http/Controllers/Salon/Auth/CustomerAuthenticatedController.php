<?php

namespace App\Http\Controllers\Salon\Auth;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\UserRepositoryInterface;
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
        protected readonly UserRepositoryInterface $userRepository,
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

        $user = $this->userRepository->findCustomerByPhoneInSalon($credentials['phone'], $salon->id);

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

        $user = $this->userRepository->find(session('customer_login_user_id'));

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

        $user = $this->userRepository->find($userId);

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

            return redirect()->to($this->customerIntendedUrl($request))
                ->with('success', 'خوش آمدید!');
        }

        $this->securityLogService->logLogin(false, $user->phone, $user);

        return back()->withErrors([
            'code' => 'کد وارد شده نامعتبر یا منقضی شده است.',
        ]);
    }

    /**
     * مقصد بعد از ورود مشتری: صفحه‌ای از همین سالن که پیش از ورود خواسته بود (مثلاً رزرو)، وگرنه صفحه‌ی اصلی سالن.
     * «url.intended» در یک مرورگر مشترک است؛ اگر پنل مدیر/متخصص در همان مرورگر باز بوده و نشستش تمام شده، آدرس پنل
     * (مثل admin/notifications/count) آنجا می‌ماند و مشتری بعد از ورود به صفحه‌ی ۴۰۳ می‌رفت (گزارش ۲۰۲۶-۱۰-۱۷).
     */
    private function customerIntendedUrl(Request $request): string
    {
        $salon = $this->currentSalon->get();
        $home = route('home', ['salon_slug' => $salon->slug]);
        $intended = $request->session()->pull('url.intended');

        if (! is_string($intended) || $intended === '') {
            return $home;
        }

        $host = parse_url($intended, PHP_URL_HOST);
        $path = '/'.ltrim((string) parse_url($intended, PHP_URL_PATH), '/');

        if ($host !== null && strcasecmp($host, $request->getHost()) !== 0) {
            return $home;
        }

        $first = strtolower(explode('/', ltrim($path, '/'))[0] ?? '');
        if (in_array($first, ['admin', 'specialist', 'superadmin', 'api', 'login', 'logout'], true)) {
            return $home;
        }

        // مسیر قدیمی /s/{slug}/...: فقط همین سالن
        if ($first === 's' && ! str_starts_with($path.'/', '/s/'.$salon->slug.'/')) {
            return $home;
        }

        return $intended;
    }

    public function resendCode(Request $request): RedirectResponse
    {
        $userId = session('customer_login_user_id');

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
        $salonSlug = $this->currentSalon->get()->slug;

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home', ['salon_slug' => $salonSlug])
            ->with('success', 'با موفقیت خارج شدید.');
    }
}
