<?php

namespace App\Http\Controllers\SalonSignup;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalonSignup\StoreSalonSignupRequest;
use App\Models\Salon;
use App\Models\User;
use App\Services\PhoneVerificationService;
use App\Services\SalonSignup\SalonSignupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * ⭐ فاز ۲ SaaS، محور «۴. ثبت‌نام عمومی سالن (self-service)». الگوی ثبت‌نام دو-مرحله‌ای
 * (ثبت → OTP → ورود) کپی مستقیم از App\Http\Controllers\Salon\Auth\CustomerRegisteredController
 * است — همون PhoneVerificationService، همون کلیدهای session-based قبل/بعد از OTP — با سه فرق:
 *
 *   ۱. همراه owner، یک Salon هم ساخته می‌شه (SalonSignupService::register)، نه فقط یک User مشتری.
 *   ۲. بعد از تایید موفق OTP، به‌جای صفحه‌ی خانه‌ی سالن، مستقیم به admin.billing.index هدایت
 *      می‌شه — اولین پرداخت واقعی سالن (به docblock SalonSignupService نگاه کن که چرا این سالن
 *      خودش‌به‌خود به اونجا redirect می‌شه، حتی بدون این redirect دستی، اگه ادمین مستقیم لاگین کنه).
 *   ۳. چون هنوز هیچ سالنی برای scope کردن phone-uniqueness وجود نداره، owner_phone سراسری بین
 *      user_type='staff' چک می‌شه (قانون StoreSalonSignupRequest، هم‌الگو با StoreSalonRequest
 *      سوپرادمین) — نه per-salon مثل CustomerRegisteredController.
 *
 * ⚠️ عمداً salon+owner همین‌جا در store() ساخته می‌شن (نه بعد از تایید OTP) — دقیقاً هم‌الگو با
 * CustomerRegisteredController که User رو قبل از OTP می‌سازه؛ یعنی یک ثبت‌نام ناتمام (کسی که کد
 * OTP رو هیچ‌وقت وارد نمی‌کنه) یک ردیف Salon+User تاییدنشده باقی می‌ذاره، دقیقاً همون سطح ریسکی
 * که پروژه از قبل برای مشتری‌ها پذیرفته — نه یک ریسک جدید مخصوص این فیچر.
 */
class SalonSignupController extends Controller
{
    public function __construct(
        protected readonly SalonSignupService $salonSignupService,
        protected readonly PhoneVerificationService $verificationService,
    ) {}

    public function create(): View
    {
        return view('salon-signup.create', [
            'prices' => config('billing.subscription_prices'),
        ]);
    }

    public function store(StoreSalonSignupRequest $request): RedirectResponse
    {
        $result = $this->salonSignupService->register($request->validated());

        $this->verificationService->sendCode($result['owner']);

        session([
            'salon_signup_salon_id' => $result['salon']->id,
            'salon_signup_user_id' => $result['owner']->id,
            'salon_signup_attempt_time' => now(),
        ]);

        return redirect()->route('salon-signup.verify')
            ->with('success', 'کد تایید به شماره موبایل شما ارسال شد.');
    }

    public function showVerify(): View|RedirectResponse
    {
        if (! session('salon_signup_user_id')) {
            return redirect()->route('salon-signup.create')
                ->withErrors(['error' => 'لطفا ابتدا ثبت‌نام کنید.']);
        }

        $owner = User::find(session('salon_signup_user_id'));
        $salon = Salon::find(session('salon_signup_salon_id'));

        if (! $owner || ! $salon) {
            session()->forget(['salon_signup_user_id', 'salon_signup_salon_id', 'salon_signup_attempt_time']);

            return redirect()->route('salon-signup.create')
                ->withErrors(['error' => 'اطلاعات ثبت‌نام یافت نشد. لطفا دوباره تلاش کنید.']);
        }

        return view('salon-signup.verify', ['owner' => $owner, 'salon' => $salon]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'size:6']]);

        $userId = session('salon_signup_user_id');
        $salonId = session('salon_signup_salon_id');

        if (! $userId || ! $salonId) {
            return redirect()->route('salon-signup.create')
                ->withErrors(['error' => 'جلسه شما منقضی شده است. لطفا دوباره ثبت‌نام کنید.']);
        }

        $owner = User::find($userId);
        $salon = Salon::find($salonId);

        if (! $owner || ! $salon) {
            session()->forget(['salon_signup_user_id', 'salon_signup_salon_id', 'salon_signup_attempt_time']);

            return redirect()->route('salon-signup.create')
                ->withErrors(['error' => 'اطلاعات ثبت‌نام یافت نشد.']);
        }

        if ($this->verificationService->verify($owner, $request->code)) {
            session()->forget(['salon_signup_user_id', 'salon_signup_salon_id', 'salon_signup_attempt_time']);

            Auth::login($owner);
            $request->session()->regenerate();

            return redirect()->route('admin.billing.index')
                ->with('success', "سالن «{$salon->name}» با موفقیت ساخته شد! برای فعال‌سازی، یکی از پلن‌های زیر را خریداری کنید.");
        }

        return back()->withErrors(['code' => 'کد وارد شده نامعتبر یا منقضی شده است.']);
    }

    public function resendCode(Request $request): RedirectResponse
    {
        $userId = session('salon_signup_user_id');

        if (! $userId) {
            return back()->withErrors(['error' => 'جلسه شما منقضی شده است.']);
        }

        $owner = User::find($userId);

        if (! $owner) {
            return back()->withErrors(['error' => 'کاربر یافت نشد.']);
        }

        $this->verificationService->sendCode($owner);

        return back()->with('success', 'کد تایید مجدد ارسال شد.');
    }
}
