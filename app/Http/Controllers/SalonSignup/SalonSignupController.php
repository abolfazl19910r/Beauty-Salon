<?php

namespace App\Http\Controllers\SalonSignup;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalonSignup\StoreSalonSignupRequest;
use App\Repositories\Contracts\SalonRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\PhoneVerificationService;
use App\Services\SalonSignup\SalonSignupService;
use Illuminate\Http\JsonResponse;
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
        protected readonly UserRepositoryInterface $userRepository,
        protected readonly SalonRepositoryInterface $salonRepository,
    ) {}

    public function create(): View
    {
        $prices = config('billing.subscription_prices');
        $plan = (string) request()->query('plan', '');

        return view('salon-signup.create', [
            'prices' => $prices,
            'selectedPlan' => array_key_exists($plan, $prices) ? $plan : '1m',
            'trialDays' => $this->salonSignupService->trialDays(),
        ]);
    }

    /**
     * ⭐ مورد ۴ (نشست ۲۰۲۶-۰۹-۲۰): چک یکتایی زنده برای slug — هم فرم self-service هم فرم
     * سوپرادمین (superadmin.salons.create) از همین یک endpoint عمومی استفاده می‌کنن، چون
     * منطق یکتایی (جدول salons) دقیقاً یکیه و افشای «این slug گرفته شده یا نه» به هیچ‌کس هیچ
     * اطلاعات حساس‌تری از چیزی که همین الان خطای submit کامل فرم می‌ده، نمی‌ده. عمداً بدون auth
     * (فرم self-service اصلاً کاربر لاگین‌شده نداره).
     */
    public function checkSlug(Request $request): JsonResponse
    {
        $slug = (string) $request->query('slug', '');

        if ($slug === '' || ! preg_match('/^[a-zA-Z0-9_-]+$/', $slug)) {
            return response()->json(['available' => false, 'reason' => 'invalid']);
        }

        $available = ! $this->salonRepository->slugExists($slug);

        return response()->json(['available' => $available, 'reason' => $available ? null : 'taken']);
    }

    /**
     * ⭐ مورد ۴: هم‌الگو با checkSlug — یکتایی owner_phone/admin_phone سراسری بین
     * user_type='staff' (دقیقاً قانون StoreSalonSignupRequest/StoreSalonRequest).
     */
    public function checkPhone(Request $request): JsonResponse
    {
        $phone = (string) $request->query('phone', '');

        if (! preg_match('/^09[0-9]{9}$/', $phone)) {
            return response()->json(['available' => false, 'reason' => 'invalid']);
        }

        $available = ! $this->userRepository->staffPhoneExists($phone);

        return response()->json(['available' => $available, 'reason' => $available ? null : 'taken']);
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

        $owner = $this->userRepository->find(session('salon_signup_user_id'));
        $salon = $this->salonRepository->find(session('salon_signup_salon_id'));

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

        $owner = $this->userRepository->find($userId);
        $salon = $this->salonRepository->find($salonId);

        if (! $owner || ! $salon) {
            session()->forget(['salon_signup_user_id', 'salon_signup_salon_id', 'salon_signup_attempt_time']);

            return redirect()->route('salon-signup.create')
                ->withErrors(['error' => 'اطلاعات ثبت‌نام یافت نشد.']);
        }

        if ($this->verificationService->verify($owner, $request->code)) {
            session()->forget(['salon_signup_user_id', 'salon_signup_salon_id', 'salon_signup_attempt_time']);

            Auth::login($owner);
            $request->session()->regenerate();

            // ⭐ دوره‌ی آزمایشی (۲۰۲۶-۰۹-۲۳): سالن آزمایشی همین الان فعاله، پس مستقیم به داشبورد
            // (که کارت «آدرس اختصاصی سالن» رو نشون می‌ده) می‌ره، نه صفحه‌ی پرداخت.
            if ($salon->fresh()->isOnTrial()) {
                return redirect()->route('admin.home')
                    ->with('success', "سالن «{$salon->name}» ساخته شد و دوره‌ی آزمایشی رایگان {$this->salonSignupService->trialDays()} روزه‌ی شما از همین حالا فعال است.");
            }

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

        $owner = $this->userRepository->find($userId);

        if (! $owner) {
            return back()->withErrors(['error' => 'کاربر یافت نشد.']);
        }

        $this->verificationService->sendCode($owner);

        return back()->with('success', 'کد تایید مجدد ارسال شد.');
    }
}
