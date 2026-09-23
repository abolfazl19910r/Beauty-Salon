<?php

namespace App\Services\SuperAdmin;

use App\Models\Invoice;
use App\Support\JalaliDateInput;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * ⭐ بخش «کیف پول / درآمد اشتراک» سوپرادمین (۲۰۲۶-۰۹-۲۴): همه‌ی فاکتورهای خرید/تمدید اشتراک همه‌ی
 * سالن‌ها (آنلاین زرین‌پال و دستی سوپرادمین)، با جستجو و فیلتر روی همه‌ی ستون‌ها، خلاصه‌ی آماری و
 * خروجی CSV. همیشه withoutGlobalScope('salon') — scope سراسری Invoice بر پایه‌ی CurrentSalon
 * درخواسته و اینجا (پنل سوپرادمین) باید همه‌ی سالن‌ها دیده بشن.
 */
class SubscriptionPaymentReport
{
    public const SORTS = [
        'newest' => 'جدیدترین',
        'oldest' => 'قدیمی‌ترین',
        'amount_desc' => 'بیشترین مبلغ',
        'amount_asc' => 'کمترین مبلغ',
        'paid_desc' => 'آخرین پرداخت',
    ];

    public function query(array $filters): Builder
    {
        $query = Invoice::withoutGlobalScope('salon')
            ->with(['salon' => fn ($q) => $q->withoutGlobalScopes(), 'createdBy']);

        if ($term = trim((string) ($filters['q'] ?? ''))) {
            $normalized = strtr($term, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9']);
            // «#۱۲» یا «12» → جستجوی شماره فاکتور هم (کنار بقیه‌ی ستون‌ها)
            $invoiceId = preg_match('/^#?(\d{1,18})$/', $normalized, $m) ? (int) $m[1] : null;
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

            $query->where(function (Builder $q) use ($like, $invoiceId) {
                $q->where('ref_id', 'like', $like)
                    ->orWhere('authority', 'like', $like)
                    ->orWhereHas('salon', fn ($s) => $s->withoutGlobalScopes()
                        ->where('name', 'like', $like)->orWhere('slug', 'like', $like))
                    ->orWhereHas('createdBy', fn ($u) => $u->where('name', 'like', $like)->orWhere('phone', 'like', $like));

                if ($invoiceId !== null) {
                    $q->orWhere('invoices.id', $invoiceId);
                }
            });
        }

        foreach (['salon_id', 'status', 'payment_method', 'subscription_type'] as $field) {
            if (! empty($filters[$field])) {
                $query->where("invoices.{$field}", $filters[$field]);
            }
        }

        $dateField = ($filters['date_field'] ?? 'created_at') === 'paid_at' ? 'paid_at' : 'created_at';
        if ($from = JalaliDateInput::toCarbon($filters['date_from'] ?? null)) {
            $query->where("invoices.{$dateField}", '>=', $from);
        }
        if ($to = JalaliDateInput::toCarbon($filters['date_to'] ?? null, endOfDay: true)) {
            $query->where("invoices.{$dateField}", '<=', $to);
        }

        if (isset($filters['amount_min']) && $filters['amount_min'] !== '') {
            $query->where('invoices.amount', '>=', (int) $filters['amount_min']);
        }
        if (isset($filters['amount_max']) && $filters['amount_max'] !== '') {
            $query->where('invoices.amount', '<=', (int) $filters['amount_max']);
        }

        match ($filters['sort'] ?? 'newest') {
            'oldest' => $query->orderBy('invoices.created_at')->orderBy('invoices.id'),
            'amount_desc' => $query->orderByDesc('invoices.amount')->orderByDesc('invoices.id'),
            'amount_asc' => $query->orderBy('invoices.amount')->orderBy('invoices.id'),
            'paid_desc' => $query->orderByRaw('invoices.paid_at IS NULL')->orderByDesc('invoices.paid_at')->orderByDesc('invoices.id'),
            default => $query->orderByDesc('invoices.created_at')->orderByDesc('invoices.id'),
        };

        return $query;
    }

    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * خلاصه‌ی همون مجموعه‌ی فیلترشده (نه فقط صفحه‌ی جاری). مبلغ‌ها فقط از فاکتورهای paid — فاکتور
     * pending/failed پولی جابه‌جا نکرده.
     */
    public function summary(array $filters): array
    {
        $base = $this->query($filters)->reorder()->getQuery();
        $base->columns = null;
        $base->eagerLoad = [];

        $row = (clone $base)->selectRaw("
            COUNT(*) as total_count,
            SUM(CASE WHEN invoices.status = 'paid' THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN invoices.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN invoices.status = 'failed' THEN 1 ELSE 0 END) as failed_count,
            SUM(CASE WHEN invoices.status = 'paid' THEN invoices.amount ELSE 0 END) as paid_sum,
            SUM(CASE WHEN invoices.status = 'paid' AND invoices.payment_method = 'online' THEN invoices.amount ELSE 0 END) as online_sum,
            SUM(CASE WHEN invoices.status = 'paid' AND invoices.payment_method = 'manual' THEN invoices.amount ELSE 0 END) as manual_sum,
            COUNT(DISTINCT CASE WHEN invoices.status = 'paid' THEN invoices.salon_id END) as paying_salons
        ")->first();

        $byPlan = (clone $base)->where('invoices.status', 'paid')
            ->selectRaw('invoices.subscription_type, COUNT(*) as cnt, SUM(invoices.amount) as total')
            ->groupBy('invoices.subscription_type')
            ->get()
            ->keyBy('subscription_type');

        return [
            'total_count' => (int) $row->total_count,
            'paid_count' => (int) $row->paid_count,
            'pending_count' => (int) $row->pending_count,
            'failed_count' => (int) $row->failed_count,
            'paid_sum' => (int) $row->paid_sum,
            'online_sum' => (int) $row->online_sum,
            'manual_sum' => (int) $row->manual_sum,
            'paying_salons' => (int) $row->paying_salons,
            'by_plan' => collect(['1m', '3m', '6m', '12m'])->mapWithKeys(fn ($type) => [$type => [
                'count' => (int) ($byPlan[$type]->cnt ?? 0),
                'total' => (int) ($byPlan[$type]->total ?? 0),
            ]])->all(),
        ];
    }

    /** کارت‌های کلی بالای صفحه — مستقل از فیلترها. */
    public function overview(): array
    {
        $paid = fn () => DB::table('invoices')->where('status', 'paid');

        return [
            'all_time' => (int) $paid()->sum('amount'),
            'this_month' => (int) $paid()->where('paid_at', '>=', JalaliDateInput::startOfCurrentJalaliMonth())->sum('amount'),
            'today' => (int) $paid()->where('paid_at', '>=', now()->startOfDay())->sum('amount'),
            'pending_now' => DB::table('invoices')->where('status', 'pending')->count(),
        ];
    }
}
