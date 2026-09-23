<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\FilterSubscriptionPaymentsRequest;
use App\Models\Invoice;
use App\Models\Salon;
use App\Services\SuperAdmin\SubscriptionPaymentReport;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ⭐ بخش «کیف پول / درآمد اشتراک» سوپرادمین (۲۰۲۶-۰۹-۲۴) — همه‌ی واریزهای خرید/تمدید اشتراک،
 * کلی (کارت‌ها و خلاصه‌ی فیلترشده) و جزئی (جدول + صفحه‌ی هر فاکتور)، با جستجو/فیلتر و خروجی CSV.
 */
class SuperAdminPaymentController extends Controller
{
    public function __construct(protected readonly SubscriptionPaymentReport $report) {}

    public function index(FilterSubscriptionPaymentsRequest $request): View
    {
        $filters = $request->filters();

        return view('superadmin.payments.index', [
            'filters' => $filters,
            'invoices' => $this->report->paginate($filters),
            'summary' => $this->report->summary($filters),
            'overview' => $this->report->overview(),
            'salons' => Salon::withoutGlobalScopes()->orderBy('name')->get(['id', 'name', 'slug']),
            'sorts' => SubscriptionPaymentReport::SORTS,
        ]);
    }

    public function show(int $invoice): View
    {
        $invoice = Invoice::withoutGlobalScope('salon')
            ->with(['salon' => fn ($q) => $q->withoutGlobalScopes(), 'createdBy'])
            ->findOrFail($invoice);

        $salonTotals = Invoice::withoutGlobalScope('salon')
            ->where('salon_id', $invoice->salon_id)
            ->selectRaw("COUNT(*) as cnt, SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_sum")
            ->first();

        return view('superadmin.payments.show', compact('invoice', 'salonTotals'));
    }

    public function export(FilterSubscriptionPaymentsRequest $request): StreamedResponse
    {
        $query = $this->report->query($request->filters());
        $labels = self::labels();

        return response()->streamDownload(function () use ($query, $labels) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM تا Excel فارسی رو درست نشون بده
            fputcsv($out, ['شماره فاکتور', 'سالن', 'آدرس سالن', 'پلن', 'مبلغ (تومان)', 'روش پرداخت', 'وضعیت',
                'کد پیگیری', 'Authority', 'ثبت‌کننده', 'موبایل ثبت‌کننده', 'تاریخ ایجاد', 'تاریخ پرداخت', 'شروع دوره', 'پایان دوره']);

            $query->chunk(500, function ($invoices) use ($out, $labels) {
                foreach ($invoices as $invoice) {
                    fputcsv($out, [
                        $invoice->id,
                        $invoice->salon?->name,
                        $invoice->salon?->slug,
                        $labels['plans'][$invoice->subscription_type] ?? $invoice->subscription_type,
                        $invoice->amount,
                        $labels['methods'][$invoice->payment_method] ?? $invoice->payment_method,
                        $labels['statuses'][$invoice->status] ?? $invoice->status,
                        $invoice->ref_id,
                        $invoice->authority,
                        $invoice->createdBy?->name,
                        $invoice->createdBy?->phone,
                        $invoice->created_at ? jalali_date($invoice->created_at, 'Y/m/d H:i') : '',
                        $invoice->paid_at ? jalali_date($invoice->paid_at, 'Y/m/d H:i') : '',
                        $invoice->period_start ? jalali_date($invoice->period_start) : '',
                        $invoice->period_end ? jalali_date($invoice->period_end) : '',
                    ]);
                }
            });

            fclose($out);
        }, 'subscription-payments-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public static function labels(): array
    {
        return [
            'plans' => ['1m' => 'یک‌ماهه', '3m' => 'سه‌ماهه', '6m' => 'شش‌ماهه', '12m' => 'یک‌ساله'],
            'methods' => ['online' => 'آنلاین (زرین‌پال)', 'manual' => 'دستی (سوپرادمین)'],
            'statuses' => ['paid' => 'پرداخت‌شده', 'pending' => 'در انتظار', 'failed' => 'ناموفق'],
        ];
    }
}
