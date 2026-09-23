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

        $prices = config('billing.subscription_prices');

        return view('admin.billing.index', compact('salon', 'invoices', 'prices'));
    }

    public function purchase(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subscription_type' => ['required', 'in:1m,3m,6m,12m'],
        ]);

        $salon = app(CurrentSalon::class)->get();

        $invoice = $this->invoiceService->createPendingOnlinePurchase(
            $salon,
            $validated['subscription_type'],
            auth()->user(),
        );

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

            return redirect()->route('admin.billing.index')
                ->withErrors(['error' => $result['message'] ?? 'پرداخت تأیید نشد.']);
        }

        $this->invoiceService->markPaidFromGateway($invoice, $result['ref_id']);

        // ⭐ ۲۰۲۶-۰۹-۲۳: آدرس عمومی سالن هم همراه پیام موفقیت — همون لحظه‌ای که مالک سالن تازه
        // خریده و بیشتر از هر وقت دیگه‌ای دنبال «حالا لینک رو به مشتری‌هام چی بدم» می‌گرده.
        $salonUrl = $invoice->salon()->withoutGlobalScopes()->first()?->publicUrl();

        return redirect()->route('admin.billing.index')
            ->with('success', 'اشتراک سالن با موفقیت تمدید شد.'.($salonUrl ? " آدرس رزرو آنلاین سالن شما: {$salonUrl}" : ''));
    }
}
