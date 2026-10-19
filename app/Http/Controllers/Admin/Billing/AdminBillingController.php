<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Services\Payment\InvoiceService;
use App\Services\Payment\SubscriptionPaymentService;
use App\Support\CurrentSalon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminBillingController extends Controller
{
    public function __construct(
        protected readonly SubscriptionPaymentService $subscriptionPaymentService,
        protected readonly InvoiceService $invoiceService,
        protected readonly InvoiceRepositoryInterface $invoiceRepository,
    ) {}

    public function index(): View
    {
        $salon = app(CurrentSalon::class)->get();

        $invoices = $this->invoiceRepository->paginateForSalon($salon->id, 15);

        // قیمت هر پلن برای همین سالن: قیمت پلن + متخصص‌های بیشتر از تعداد شامل‌شده (SubscriptionPricing).
        $pricing = app(\App\Support\Billing\SubscriptionPricing::class);
        $specialists = (int) $salon->max_specialists_count;
        $prices = collect(\App\Support\Billing\SubscriptionPricing::PLAN_MONTHS)
            ->map(fn ($months, $type) => $pricing->price($type, $specialists))
            ->all();
        $includedSpecialists = $pricing->includedSpecialists();
        $extraSpecialistPrice = $pricing->extraSpecialistPricePerMonth();

        // پلن‌ها (بسته‌ی الف، ۲۰۲۶-۱۰-۱۴): بدون هیچ پلن قابل خرید (پیش از plans:sync-defaults) همان فرم قدیمی با قیمت config
        $billing = app(\App\Services\Plans\PlanBilling::class);
        $currentPlan = $salon->effectivePlan();
        $scheduledPlan = $billing->scheduledPlan($salon);
        $purchasable = $billing->purchasablePlans($salon);
        $planOptions = $purchasable->map(fn (\App\Models\Plan $plan) => [
            'plan' => $plan,
            'features' => $plan->featureKeys(),
            'prices' => collect($plan->periods())->mapWithKeys(fn ($type) => [$type => $pricing->price($type, $specialists, $plan)])->all(),
            // همان اطلاعات کارت‌های لندینگ (۲۰۲۶-۱۰-۱۷): ماهانه، تخفیف بازه، قالب‌ها و «پیشنهاد ما» — برای تعداد متخصص همین سالن
            'periods' => $this->periodDetails($plan, $purchasable, $specialists, $pricing),
            'admin_themes' => $plan->adminThemeAllowance(),
            'recommended' => $plan->key === 'pro',
            'included' => $pricing->includedSpecialists($plan),
            'extra_price' => $pricing->extraSpecialistPricePerMonth($plan),
            'upgrade_price' => $billing->upgradeError($salon, $plan) === null ? $pricing->upgradePrice($salon, $plan) : null,
            'purchase_error' => $billing->purchaseError($salon, $plan, $plan->periods()[0]),
        ])->all();
        $defaultPlanId = $billing->defaultPurchasePlan($salon)?->id;
        $enabledFeatures = $salon->enabledFeatureKeys();
        $remainingDays = $pricing->remainingDays($salon);

        $quota = app(\App\Services\Sms\SmsQuotaService::class);
        $smsUsage = [
            'used' => $quota->usedCount($salon),
            'quota' => $quota->quotaFor($salon),
            'remaining' => $quota->remaining($salon),
            'otp' => $quota->otpCount($salon),
            'credit' => $quota->credit($salon),
            'credit_used' => $quota->creditUsed($salon),
        ];
        $smsCredit = app(\App\Services\Sms\SmsCreditService::class);
        $smsPacks = collect($smsCredit->packs())->map(fn ($parts) => ['parts' => $parts, 'price' => $smsCredit->price($parts)])->all();
        $smsPartPrice = $smsCredit->partPrice();
        $smsPurchases = $smsCredit->history($salon, 10);

        return view('admin.billing.index', compact('salon', 'invoices', 'prices', 'specialists', 'includedSpecialists', 'extraSpecialistPrice', 'smsUsage', 'smsPacks', 'smsPartPrice', 'smsPurchases',
            'currentPlan', 'scheduledPlan', 'planOptions', 'defaultPlanId', 'enabledFeatures', 'remainingDays'));
    }

    public function purchase(Request $request): RedirectResponse
    {
        // فرم پلن‌ها یک رادیوی «planId:type» دارد (بدون JS هم کار کند)
        if (is_string($choice = $request->input('choice')) && preg_match('/^(\d+):(1m|3m|6m|12m)$/', $choice, $m)) {
            $request->merge(['plan_id' => (int) $m[1], 'subscription_type' => $m[2]]);
        }

        $validated = $request->validate([
            'subscription_type' => ['required', 'in:1m,3m,6m,12m'],
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
        ]);

        $salon = app(CurrentSalon::class)->get();

        $plan = null;
        if (! empty($validated['plan_id'])) {
            $plan = \App\Models\Plan::findOrFail($validated['plan_id']);
            if ($error = app(\App\Services\Plans\PlanBilling::class)->purchaseError($salon, $plan, $validated['subscription_type'])) {
                return back()->withErrors(['error' => $error]);
            }
        }

        $invoice = $this->invoiceService->createPendingOnlinePurchase(
            $salon,
            $validated['subscription_type'],
            auth()->user(),
            $plan,
        );

        return $this->redirectToGateway($invoice);
    }

    /** ارتقای فوری وسط دوره با پرداخت مابه‌التفاوت روزهای مانده (تصمیم ۲۰۲۶-۱۰-۱۴) */
    public function upgrade(Request $request): RedirectResponse
    {
        $validated = $request->validate(['plan_id' => ['required', 'integer', 'exists:plans,id']]);

        $salon = app(CurrentSalon::class)->get();
        $plan = \App\Models\Plan::findOrFail($validated['plan_id']);

        if ($error = app(\App\Services\Plans\PlanBilling::class)->upgradeError($salon, $plan)) {
            return back()->withErrors(['error' => $error]);
        }

        return $this->redirectToGateway($this->invoiceService->createPendingUpgrade($salon, $plan, auth()->user()));
    }

    private function redirectToGateway(\App\Models\Invoice $invoice): RedirectResponse
    {
        $result = $this->subscriptionPaymentService->createPayment($invoice);

        if (! $result['success']) {
            $this->invoiceService->markFailed($invoice);

            return back()->withErrors(['error' => $result['message'] ?? 'خطا در اتصال به درگاه پرداخت.']);
        }

        return redirect()->away($result['payment_url']);
    }

    public function callback(Request $request): RedirectResponse
    {
        $invoiceId = $request->query('invoice');
        $invoice = $this->invoiceRepository->findOrFail($invoiceId);

        $status = $request->Status ?? $request->status;
        $authority = $request->Authority ?? $request->authority;

        if (! $invoice->isPending()) {
            return redirect()->route('admin.billing.index')
                ->with('info', 'این فاکتور قبلاً پردازش شده است.');
        }

        $result = $this->subscriptionPaymentService->verifyPayment($invoice, $status, $authority);

        if (! $result['success']) {
            $this->invoiceService->markFailed($invoice);
            $this->sendPendingWelcome($invoice);

            return redirect()->route('admin.billing.index')
                ->withErrors(['error' => $result['message'] ?? 'پرداخت تأیید نشد.']);
        }

        $this->invoiceService->markPaidFromGateway($invoice, $result['ref_id']);
        $this->sendPendingWelcome($invoice);

        // ⭐ ۲۰۲۶-۰۹-۲۳: آدرس عمومی سالن هم همراه پیام موفقیت — همون لحظه‌ای که مالک سالن تازه
        // خریده و بیشتر از هر وقت دیگه‌ای دنبال «حالا لینک رو به مشتری‌هام چی بدم» می‌گرده.
        $salonUrl = $invoice->salon()->withoutGlobalScopes()->first()?->publicUrl();

        if ($invoice->isUpgrade()) {
            return redirect()->route('admin.billing.index')
                ->with('success', sprintf('پرداخت موفق بود و سالن به پلن «%s» ارتقا یافت.', $invoice->planName() ?? ''));
        }

        return redirect()->route('admin.billing.index')
            ->with('success', 'پرداخت موفق بود و اشتراک سالن فعال شد.'.($salonUrl ? " آدرس رزرو آنلاین سالن شما: {$salonUrl}" : ''));
    }

    /** پیامک خوش‌آمدِ ثبت‌نام با «خرید» که منتظر همین نتیجه بود (بسته‌ی «ح»، ۳)؛ برای سالن‌های دیگر کاری نمی‌کند */
    private function sendPendingWelcome(\App\Models\Invoice $invoice): void
    {
        $salon = $invoice->salon()->withoutGlobalScopes()->first();
        if ($salon?->welcome_sms_pending_at) {
            app(\App\Services\SalonSignup\SalonWelcome::class)->sendPending($salon);
        }
    }

    /** خرید بسته‌ی پیامک (۲۰۲۶-۰۹-۳۰): فقط اندازه‌های SMS_PACKS؛ قیمت از سرور، نه فرم */
    public function purchaseSmsPack(Request $request): RedirectResponse
    {
        $credit = app(\App\Services\Sms\SmsCreditService::class);
        $validated = $request->validate([
            'parts' => ['required', 'integer', \Illuminate\Validation\Rule::in($credit->packs())],
        ]);

        $salon = app(CurrentSalon::class)->get();
        $purchase = $credit->startOnlinePurchase($salon, (int) $validated['parts'], auth()->user());
        $result = app(\App\Services\Payment\SmsPackPaymentService::class)->createPayment($purchase);

        if (! $result['success']) {
            $credit->markFailed($purchase);

            return back()->withErrors(['error' => $result['message'] ?? 'خطا در اتصال به درگاه پرداخت.']);
        }

        return redirect()->away($result['payment_url']);
    }

    public function smsPackCallback(Request $request, int $purchase): RedirectResponse
    {
        $salon = app(CurrentSalon::class)->get();
        // فقط خرید همین سالن — شناسه در URL است
        $record = \App\Models\SmsCreditPurchase::where('salon_id', $salon->id)->where('source', 'online')->findOrFail($purchase);
        $credit = app(\App\Services\Sms\SmsCreditService::class);

        if (! $record->isPending()) {
            return redirect()->route('admin.billing.index')->with('info', 'این خرید قبلاً پردازش شده است.');
        }

        $result = app(\App\Services\Payment\SmsPackPaymentService::class)
            ->verifyPayment($record, $request->Status ?? $request->status, $request->Authority ?? $request->authority);

        if (! $result['success']) {
            $credit->markFailed($record);

            return redirect()->route('admin.billing.index')->withErrors(['error' => $result['message'] ?? 'پرداخت تأیید نشد.']);
        }

        $credit->completeOnlinePurchase($record, $result['ref_id']);

        return redirect()->route('admin.billing.index')
            ->with('success', sprintf('پرداخت موفق بود؛ %s قطعه پیامک به اعتبار سالن اضافه شد.', number_format($record->parts)));
    }

    /**
     * جزئیات هر بازه‌ی یک پلن برای همین سالن: ماهانه، درصد تخفیف نسبت به خرید ماهانه، تعداد قالب سایت آن بازه، و پلن بالاتری که با
     * همین تعداد متخصص در همان بازه ارزان‌تر است (متخصص اضافه در پلن پایه گاهی از پلن بالاتر گران‌تر تمام می‌شود).
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Plan>  $plans
     * @return array<string, array{per_month: int, saving_percent: int, themes: int, cheaper: ?string}>
     */
    private function periodDetails(\App\Models\Plan $plan, \Illuminate\Support\Collection $plans, int $specialists, \App\Support\Billing\SubscriptionPricing $pricing): array
    {
        $monthly = in_array('1m', $plan->periods(), true) ? $pricing->price('1m', $specialists, $plan) : null;
        $out = [];
        foreach ($plan->periods() as $type) {
            $months = \App\Support\Billing\SubscriptionPricing::PLAN_MONTHS[$type];
            $price = $pricing->price($type, $specialists, $plan);
            $cheaper = $plans
                ->filter(fn (\App\Models\Plan $other) => $other->sort > $plan->sort && in_array($type, $other->periods(), true))
                ->first(fn (\App\Models\Plan $other) => $pricing->price($type, $specialists, $other) < $price);
            $out[$type] = [
                'per_month' => (int) round($price / $months),
                'saving_percent' => $monthly && $months > 1 ? max(0, (int) round((1 - $price / ($monthly * $months)) * 100)) : 0,
                'themes' => $plan->siteThemeAllowance($type),
                'cheaper' => $cheaper?->name,
            ];
        }

        return $out;
    }
}
