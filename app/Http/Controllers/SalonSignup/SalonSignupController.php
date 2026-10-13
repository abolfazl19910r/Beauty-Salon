<?php

namespace App\Http\Controllers\SalonSignup;

use App\Exceptions\Api\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\SalonSignup\StoreSalonSignupRequest;
use App\Repositories\Contracts\SalonRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Payment\InvoiceService;
use App\Services\Payment\SubscriptionPaymentService;
use App\Services\SalonSignup\SalonSignupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * ⭐ فاز ۲ SaaS، محور «۴. ثبت‌نام عمومی سالن (self-service)»: فرم ← کد پیامکی ← سالن، مالک و دوره‌ی آزمایشی ← پنل مدیر.
 *
 * از ۲۰۲۶-۱۰-۱۱ سالن و مالک **فقط بعد از تأیید کد** ساخته می‌شوند (SalonSignupChallenge، همان سازوکار ثبت‌نام مشتری):
 * ثبت‌نام نیمه‌کاره دیگر شماره‌ی کادر یا slug کسی را نمی‌گیرد. شماره‌ی مالک در کل پلتفرم بین user_type='staff' یکتاست
 * (StoreSalonSignupRequest؛ هنوز سالنی برای scope نیست) و slug و شماره هنگام تأیید دوباره چک می‌شوند.
 * بعد از تأیید: دوره‌ی آزمایشی → پنل مدیر؛ intent=buy → درگاه پرداخت اشتراک؛ بدون آزمایشی → صفحه‌ی صورتحساب.
 */
class SalonSignupController extends Controller
{
    private const SESSION_KEY = 'salon_signup_challenge';

    public function __construct(
        protected readonly SalonSignupService $salonSignupService,
        protected readonly \App\Services\SalonSignup\SalonSignupChallenge $signupChallenge,
        protected readonly UserRepositoryInterface $userRepository,
        protected readonly SalonRepositoryInterface $salonRepository,
        protected readonly InvoiceService $invoiceService,
        protected readonly SubscriptionPaymentService $subscriptionPaymentService,
    ) {}

    public function create(): View
    {
        $prices = config('billing.subscription_prices');
        $plan = (string) request()->query('plan', '');
        $selectedPlan = array_key_exists($plan, $prices) ? $plan : '1m';

        return view('salon-signup.create', [
            'selectedPlan' => $selectedPlan,
            'selectedPlanPrice' => (int) $prices[$selectedPlan],
            'trialDays' => $this->salonSignupService->trialDays(),
            'includedSpecialists' => app(\App\Support\Billing\SubscriptionPricing::class)->includedSpecialists(),
            'extraSpecialistPrice' => app(\App\Support\Billing\SubscriptionPricing::class)->extraSpecialistPricePerMonth(),
            'maxSignupSpecialists' => max(1, (int) config('billing.max_signup_specialists', 50)),
            // ⭐ «خرید مستقیم بدون دوره‌ی رایگان» (۲۰۲۶-۰۹-۲۳): ?intent=buy از دکمه‌ی «خرید و پرداخت
            // آنلاین» صفحه‌ی فروش میاد؛ هر مقدار دیگه‌ای یعنی مسیر عادی (آزمایشی).
            'intent' => request()->query('intent') === 'buy' ? 'buy' : 'trial',
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
        // سالن و مالک فقط بعد از تأیید کد ساخته می‌شوند (SalonSignupChallenge، تصمیم ۲۰۲۶-۱۰-۱۱)
        $challenge = $this->signupChallenge->start(
            $request->validated() + [
                'contact' => $request->salonContactAttributes(),
                'intent' => $request->input('intent') === 'buy' ? 'buy' : 'trial',
            ],
            $request->file('logo'),
        );

        session([self::SESSION_KEY => $challenge]);

        return redirect()->route('salon-signup.verify')
            ->with('success', 'کد تایید به شماره موبایل شما ارسال شد.');
    }

    public function showVerify(): View|RedirectResponse
    {
        $data = ($challenge = session(self::SESSION_KEY)) ? $this->signupChallenge->peek($challenge) : null;

        if (! $data) {
            session()->forget(self::SESSION_KEY);

            return redirect()->route('salon-signup.create')
                ->withErrors(['error' => 'لطفا ابتدا ثبت‌نام کنید.']);
        }

        return view('salon-signup.verify', [
            'owner' => (object) ['phone' => $data['owner_phone']],
            'salon' => (object) ['name' => $data['name']],
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'size:6']]);

        $challenge = session(self::SESSION_KEY);
        if (! $challenge) {
            return redirect()->route('salon-signup.create')
                ->withErrors(['error' => 'جلسه شما منقضی شده است. لطفا دوباره ثبت‌نام کنید.']);
        }

        try {
            ['salon' => $salon, 'owner' => $owner, 'intent' => $intent] = $this->signupChallenge->complete($challenge, (string) $request->code);
        } catch (ApiException $e) {
            if (in_array($e->errorCode, ['invalid_code', 'code_expired'], true)) {
                return back()->withErrors(['code' => 'کد وارد شده نامعتبر یا منقضی شده است.']);
            }

            session()->forget(self::SESSION_KEY);

            return redirect()->route('salon-signup.create')->withErrors(['error' => $e->getMessage()]);
        }

        session()->forget(self::SESSION_KEY);
        Auth::login($owner);
        $request->session()->regenerate();

        $owner->notify(new \App\Notifications\Salon\SalonWelcomeNotification($salon, $salon->isOnTrial() ? $this->salonSignupService->trialDays() : 0));

        if ($intent === 'buy') {
            return $this->startImmediatePurchase($salon, $owner);
        }

        if ($salon->isOnTrial()) {
            return redirect()->route('admin.home')
                ->with('success', "سالن «{$salon->name}» ساخته شد و دوره‌ی آزمایشی رایگان {$this->salonSignupService->trialDays()} روزه‌ی شما از همین حالا فعال است.");
        }

        return redirect()->route('admin.billing.index')
            ->with('success', "سالن «{$salon->name}» با موفقیت ساخته شد! برای فعال‌سازی، یکی از پلن‌های زیر را خریداری کنید.");
    }

    private function startImmediatePurchase(\App\Models\Salon $salon, \App\Models\User $owner): RedirectResponse
    {
        $invoice = $this->invoiceService->createPendingOnlinePurchase($salon, $salon->subscription_type, $owner);
        $result = $this->subscriptionPaymentService->createPayment($invoice);

        if (! $result['success']) {
            $this->invoiceService->markFailed($invoice);

            $fallback = $salon->isOnTrial()
                ? ' تا آن زمان دوره‌ی آزمایشی رایگان سالن شما فعال است.'
                : '';

            return redirect()->route('admin.billing.index')
                ->withErrors(['error' => 'سالن ساخته شد، ولی اتصال به درگاه پرداخت ناموفق بود. از همین صفحه دوباره پرداخت کنید.'.$fallback]);
        }

        return redirect()->away($result['payment_url']);
    }

    public function resendCode(Request $request): RedirectResponse
    {
        $challenge = session(self::SESSION_KEY);
        if (! $challenge) {
            return back()->withErrors(['error' => 'جلسه شما منقضی شده است.']);
        }

        try {
            $this->signupChallenge->resend($challenge);
        } catch (ApiException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', 'کد تایید مجدد ارسال شد.');
    }
}
