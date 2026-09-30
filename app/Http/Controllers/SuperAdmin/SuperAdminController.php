<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreSalonRequest;
use App\Http\Requests\SuperAdmin\UpdateSalonRequest;
use App\Models\Salon;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Repositories\Contracts\SalonRepositoryInterface;
use App\Repositories\Contracts\SpecialistRepositoryInterface;
use App\Services\Payment\InvoiceService;
use App\Services\SuperAdmin\SuperAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 5). Routes already exist
 * (routes/super-admin.php, commit 4a) — this is the controller they were written against ahead
 * of time; every method name here matches what that file already references.
 *
 * Deliberately thin — validation lives in the FormRequests, business rules (quota math,
 * subscription date math, the "at most one owner" rule) live in SuperAdminService. This
 * controller's only real job is translating requests into service calls and picking what to
 * show/redirect to.
 */
class SuperAdminController extends Controller
{
    public function __construct(
        protected readonly SuperAdminService $superAdminService,
        protected readonly InvoiceService $invoiceService,
        protected readonly SalonRepositoryInterface $salonRepository,
        protected readonly InvoiceRepositoryInterface $invoiceRepository,
        protected readonly SpecialistRepositoryInterface $specialistRepository,
    ) {}

    public function dashboard(): View
    {
        // ⭐ باگ‌های ۳ و ۴ (۲۰۲۶-۰۹-۱۸): «فعال» = تعلیق‌نشده و اشتراک معتبر؛ «رو به انقضا» با مقایسه‌ی مستقیم تاریخ. هر دو تعریف حالا
        // در SalonRepository::subscriptionCounts() در SQL‌اند — ۲۰۲۶-۰۹-۳۰: قبلاً همه‌ی سالن‌ها و مدیرانشان برای شمردن بار می‌شدند.
        $stats = $this->salonRepository->subscriptionCounts(now()) + [
            'total_specialists' => $this->specialistRepository->count(),
        ];

        $recentSalons = $this->salonRepository->getRecentWithSpecialistCount(5);

        return view('superadmin.dashboard', compact('stats', 'recentSalons'));
    }

    /**
     * ⭐ ۲۰۲۶-۰۹-۲۴: جستجو و فیلتر روی همه‌ی ستون‌های لیست (App\Services\SuperAdmin\SalonListFilter).
     */
    public function index(\App\Http\Requests\SuperAdmin\FilterSalonsRequest $request): View
    {
        $filters = $request->filters();
        $salons = app(\App\Services\SuperAdmin\SalonListFilter::class)->paginate($filters);

        return view('superadmin.salons.index', [
            'salons' => $salons,
            'filters' => $filters,
            'statuses' => \App\Services\SuperAdmin\SalonListFilter::STATUSES,
            'sorts' => \App\Services\SuperAdmin\SalonListFilter::SORTS,
        ]);
    }

    public function create(): View
    {
        $pricing = app(\App\Support\Billing\SubscriptionPricing::class);

        return view('superadmin.salons.create', [
            'includedSpecialists' => $pricing->includedSpecialists(),
            'extraSpecialistPrice' => $pricing->extraSpecialistPricePerMonth(),
        ]);
    }

    public function store(StoreSalonRequest $request): RedirectResponse
    {
        $salon = $this->superAdminService->createSalonWithAdmin(
            $request->validated() + ['contact' => $request->salonContactAttributes()],
            auth()->user(),
        );

        if ($request->hasFile('logo')) {
            app(\App\Services\Salon\SalonLogoService::class)->replace($salon, $request->file('logo'));
        }

        return redirect()->route('superadmin.salons.index')
            ->with('success', "سالن «{$salon->name}» با موفقیت ایجاد شد.");
    }

    public function edit(Salon $salon): View
    {
        $quota = app(\App\Services\Sms\SmsQuotaService::class);
        $smsUsage = [
            'used' => $quota->usedCount($salon),
            'quota' => $quota->quotaFor($salon),
            'otp' => $quota->otpCount($salon),
        ];

        return view('superadmin.salons.edit', compact('salon', 'smsUsage'));
    }

    public function update(UpdateSalonRequest $request, Salon $salon): RedirectResponse
    {
        try {
            $this->superAdminService->updateSalon($salon, $request->validated() + ['contact' => $request->salonContactAttributes()]);

            // ⭐ لوگو (۲۰۲۶-۰۹-۲۴): آپلود جدید جایگزین می‌شه؛ «حذف لوگو» فقط وقتی فایل جدیدی نیومده.
            $logoService = app(\App\Services\Salon\SalonLogoService::class);
            if ($request->hasFile('logo')) {
                $logoService->replace($salon->fresh(), $request->file('logo'));
            } elseif ($request->boolean('remove_logo')) {
                $logoService->remove($salon->fresh());
            }
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['max_specialists_count' => $e->getMessage()]);
        }

        return redirect()->route('superadmin.salons.index')
            ->with('success', 'اطلاعات سالن به‌روزرسانی شد.');
    }

    /**
     * ⭐ فاز ۲، محور «۱. پرداخت آنلاین و صورتحساب» — این تمدید دستی حالا هم در جدول `invoices`
     * ثبت می‌شود (payment_method='manual') تا تاریخچه‌ی خرید/تمدید یکپارچه بماند، چه مسیر آنلاین
     * چه دستی. منطق واقعی تمدید (ریاضی تاریخ) هنوز در SuperAdminService است؛ InvoiceService فقط
     * آن را با ثبت فاکتور ترکیب می‌کند — به docblock خودِ InvoiceService نگاه کن.
     */
    public function renewSubscription(Request $request, Salon $salon): RedirectResponse
    {
        $validated = $request->validate([
            'subscription_type' => ['required', 'in:1m,3m,6m,12m'],
        ]);

        $this->invoiceService->recordManualRenewal($salon, $validated['subscription_type'], auth()->user());

        return redirect()->route('superadmin.salons.index')
            ->with('success', "اشتراک سالن «{$salon->name}» تمدید شد.");
    }

    public function invoices(Salon $salon): View
    {
        // ⭐ همان الگوی مستندشده در SuperAdminService::updateSalon()/remainingSpecialistQuota():
        // global scope BelongsToSalon با هر CurrentSalon دیگری که ممکن است ست شده باشد AND
        // می‌شود، پس یک withoutGlobalScope صریح لازم است تا این همیشه واقعاً فاکتورهای همین
        // سالن را برگرداند، صرف‌نظر از این‌که CurrentSalon چه بوده.
        $invoices = $this->invoiceRepository->paginateForSalonIgnoringScope($salon->id);

        return view('superadmin.salons.invoices', compact('salon', 'invoices'));
    }

    public function toggleSuspend(Salon $salon): RedirectResponse
    {
        try {
            $this->superAdminService->toggleSuspend($salon);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        $status = $salon->fresh()->is_suspended ? 'غیرفعال' : 'فعال';

        return redirect()->route('superadmin.salons.index')
            ->with('success', "سالن «{$salon->name}» {$status} شد.");
    }
}
